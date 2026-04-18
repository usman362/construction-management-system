<?php

namespace App\Console\Commands;

use App\Models\CostCode;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimesheetCostAllocation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports timesheet rows from the client's "Payroll Pre-processor Status"
 * xlsx export. Each xlsx row represents one employee-day-job allocation.
 *
 * Header columns expected:
 *   LaborPayrollID | Work Date | Employee Name | Job | Work Order | Unit Code
 *   Customer | Jobsite | Labor Hours | Approval | Processed | EmpID
 *   GroupNumb | YardCode | YardName | ClassCode | Classification
 *   CompanyCode | CompanyName | ApprovedBy | ProcessedBy
 *
 * Import strategy:
 *   - Rows are grouped by (EmpID, Job, Work Date) so multiple same-day rows
 *     (e.g. Direct-Job + Per Diem) collapse into one Timesheet.
 *   - Employees are matched by employee_number = EmpID. Missing ones are
 *     created using "Last, First" split.
 *   - Projects are matched by project_number = Job. Missing ones are
 *     created with Customer/Jobsite as name.
 *   - Hours are summed for non-"Per Diem" classifications then split:
 *       ≤8 → regular, 8–16 → overtime, >16 → double-time.
 *   - "Per Diem" classification rows contribute per_diem_amount =
 *     (sum of Labor Hours) × project.default_per_diem_rate.
 *   - Existing timesheets (same employee_id + project_id + date) are
 *     UPDATED in place, not duplicated (idempotent re-runs).
 */
class ImportPayrollPreprocessor extends Command
{
    protected $signature = 'import:payroll-preprocessor
        {file : Absolute path to the xlsx file}
        {--dry-run : Parse and summarize without writing to the database}
        {--per-diem-default=50 : Fallback $/day for projects that have no default_per_diem_rate set}';

    protected $description = 'Import timesheet data from a Payroll Pre-processor Status xlsx export';

    private int $rowsRead = 0;
    private int $groupsProcessed = 0;
    private int $timesheetsCreated = 0;
    private int $timesheetsUpdated = 0;
    private int $employeesCreated = 0;
    private int $projectsCreated = 0;
    private int $rowsSkipped = 0;
    private array $skippedReasons = [];

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info("Reading {$path} ...");
        $rows = $this->readAllRows($path);
        $this->rowsRead = count($rows);
        $this->info("  Read {$this->rowsRead} data rows from the workbook.");

        // Group rows by (EmpID, Job, Date). Rows without a Job or EmpID are skipped.
        $groups = [];
        foreach ($rows as $r) {
            $empId   = trim((string) ($r['EmpID'] ?? ''));
            $job     = trim((string) ($r['Job'] ?? ''));
            $dateStr = $r['Work Date'] ?? null;
            $date    = $this->parseDate($dateStr);

            if ($empId === '' || $job === '' || !$date) {
                $this->rowsSkipped++;
                $why = $empId === '' ? 'no EmpID' : ($job === '' ? 'no Job' : 'bad Work Date');
                $this->skippedReasons[$why] = ($this->skippedReasons[$why] ?? 0) + 1;
                continue;
            }

            $key = $empId . '|' . $job . '|' . $date->toDateString();
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'emp_id'        => $empId,
                    'employee_name' => trim((string) ($r['Employee Name'] ?? '')),
                    'job'           => $job,
                    'date'          => $date->toDateString(),
                    'customer'      => trim((string) ($r['Customer'] ?? '')),
                    'jobsite'       => trim((string) ($r['Jobsite'] ?? '')),
                    'hours'         => 0.0,    // non-per-diem hours
                    'per_diem_days' => 0.0,    // per-diem classification rows
                    'source_ids'    => [],
                    'approved'      => false,
                    'classifications' => [],
                ];
            }

            $hours = (float) ($r['Labor Hours'] ?? 0);
            $class = trim((string) ($r['Classification'] ?? ''));
            $groups[$key]['source_ids'][]     = (string) ($r['LaborPayrollID'] ?? '');
            $groups[$key]['classifications'][] = $class;
            if (strcasecmp($class, 'Per Diem') === 0) {
                $groups[$key]['per_diem_days'] += $hours;
            } else {
                $groups[$key]['hours'] += $hours;
            }
            if (($r['Approval'] ?? false) === true || strtolower((string) ($r['Approval'] ?? '')) === 'true') {
                $groups[$key]['approved'] = true;
            }
        }

        $this->groupsProcessed = count($groups);
        $this->info("  Collapsed into {$this->groupsProcessed} (employee, job, date) groups.");

        if ($this->rowsSkipped > 0) {
            $this->warn("  Skipped {$this->rowsSkipped} rows: " . json_encode($this->skippedReasons));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes written.');
            $this->summarize($groups);
            return self::SUCCESS;
        }

        // Prewarm caches so we don't hit the DB once per row.
        $employeesByNumber = Employee::whereNotNull('employee_number')->get()->keyBy(fn ($e) => strtoupper((string) $e->employee_number));
        $projectsByNumber  = Project::whereNotNull('project_number')->get()->keyBy(fn ($p) => strtoupper((string) $p->project_number));

        // Resolve a default Phase Code for the single allocation we create per
        // timesheet. The allocations table requires cost_code_id, and the client's
        // export doesn't carry phase codes, so we bucket everything under one
        // general labor code that the office can re-allocate later.
        $defaultCostCode = CostCode::where('code', 'LABOR')->first()
            ?? CostCode::firstOrCreate(
                ['code' => 'LABOR'],
                ['name' => 'General Labor (import default)', 'is_active' => true]
            );

        $perDiemFallback = (float) $this->option('per-diem-default');

        DB::beginTransaction();
        try {
            foreach ($groups as $g) {
                // Employee lookup / create
                $empKey = strtoupper($g['emp_id']);
                if (!isset($employeesByNumber[$empKey])) {
                    [$first, $middle, $last] = $this->splitName($g['employee_name']);
                    $emp = Employee::create([
                        'employee_number' => $g['emp_id'],
                        'first_name'      => $first ?: 'Unknown',
                        'middle_name'     => $middle,
                        'last_name'       => $last ?: $g['emp_id'],
                        'hourly_rate'     => 0,
                        'overtime_rate'   => 0,
                        'billable_rate'   => 0,
                        'status'          => 'active',
                    ]);
                    $employeesByNumber[$empKey] = $emp;
                    $this->employeesCreated++;
                    $this->line("  + Created employee {$g['emp_id']}: {$emp->first_name} {$emp->last_name}");
                }
                $employee = $employeesByNumber[$empKey];

                // Project lookup / create
                $projKey = strtoupper($g['job']);
                if (!isset($projectsByNumber[$projKey])) {
                    $name = $g['customer'] ?: $g['jobsite'] ?: $g['job'];
                    $proj = Project::create([
                        'project_number' => $g['job'],
                        'name'           => $name,
                        'status'         => 'active',
                        'start_date'     => $g['date'],
                    ]);
                    $projectsByNumber[$projKey] = $proj;
                    $this->projectsCreated++;
                    $this->line("  + Created project {$g['job']}: {$name}");
                }
                $project = $projectsByNumber[$projKey];

                // Split hours: ≤8 reg / 8-16 OT / >16 DT
                $total = (float) $g['hours'];
                $reg = min($total, 8);
                $ot  = max(0, min($total, 16) - 8);
                $dt  = max(0, $total - 16);

                // Per-diem dollars
                $rate = (float) ($project->default_per_diem_rate ?? 0);
                if ($rate <= 0) $rate = $perDiemFallback;
                $perDiemAmount = round($g['per_diem_days'] * $rate, 2);

                $regRate = (float) ($employee->hourly_rate ?? 0);
                $otRate  = (float) ($employee->overtime_rate ?? ($regRate * 1.5));
                $totalCost = round($reg * $regRate + $ot * $otRate + $dt * $otRate, 2);

                $noteLines = [];
                $sourceIds = array_filter($g['source_ids']);
                if ($sourceIds) $noteLines[] = 'Source LaborPayrollID: ' . implode(',', $sourceIds);
                $classSet = array_unique(array_filter($g['classifications']));
                if ($classSet) $noteLines[] = 'Classifications: ' . implode(', ', $classSet);
                $note = $noteLines ? implode("\n", $noteLines) : null;

                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'project_id'  => $project->id,
                        'date'        => $g['date'],
                    ],
                    [
                        'regular_hours'     => $reg,
                        'overtime_hours'    => $ot,
                        'double_time_hours' => $dt,
                        'total_hours'       => $total,
                        'regular_rate'      => $regRate,
                        'overtime_rate'     => $otRate,
                        'total_cost'        => $totalCost,
                        'is_billable'       => true,
                        'status'            => $g['approved'] ? 'approved' : 'submitted',
                        'notes'             => $note,
                    ]
                );
                if ($timesheet->wasRecentlyCreated) $this->timesheetsCreated++;
                else $this->timesheetsUpdated++;

                // One allocation per timesheet capturing hours + per-diem for the day.
                // Keyed only by timesheet_id so re-runs refresh the single row.
                TimesheetCostAllocation::updateOrCreate(
                    ['timesheet_id' => $timesheet->id],
                    [
                        'cost_code_id'    => $defaultCostCode->id,
                        'cost_type_id'   => null,
                        'hours'           => $total,
                        'cost'            => $totalCost,
                        'per_diem_amount' => $perDiemAmount,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }

        $this->info('');
        $this->info('=== Import complete ===');
        $this->line("  Timesheets created:  {$this->timesheetsCreated}");
        $this->line("  Timesheets updated:  {$this->timesheetsUpdated}");
        $this->line("  Employees created:   {$this->employeesCreated}");
        $this->line("  Projects created:    {$this->projectsCreated}");
        $this->line("  Rows skipped:        {$this->rowsSkipped}");
        return self::SUCCESS;
    }

    /**
     * Reads every row from every sheet. Assumes row 1 is the header row and
     * that headers are identical across sheets (they are for this export).
     */
    private function readAllRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $all = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $data = $sheet->toArray(null, true, true, false);
            if (count($data) < 2) continue;
            $headers = array_map(fn ($h) => trim((string) $h), $data[0]);
            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                // Skip fully empty rows
                if (!array_filter($row, fn ($v) => $v !== null && $v !== '')) continue;
                $assoc = [];
                foreach ($headers as $idx => $h) {
                    $assoc[$h] = $row[$idx] ?? null;
                }
                $all[] = $assoc;
            }
        }
        return $all;
    }

    private function parseDate($raw): ?Carbon
    {
        if ($raw === null || $raw === '') return null;
        if ($raw instanceof \DateTimeInterface) return Carbon::instance($raw);
        // PhpSpreadsheet may give us a numeric Excel serial
        if (is_numeric($raw)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw));
            } catch (\Throwable) {
                // fall through
            }
        }
        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Splits "Last, First M" into [first, middle, last]. Handles suffixes
     * like "Jr.", "Sr.", "III" that appear on the last-name side.
     */
    private function splitName(string $full): array
    {
        $full = trim($full);
        if ($full === '') return ['', null, ''];

        if (str_contains($full, ',')) {
            [$lastPart, $firstPart] = array_map('trim', explode(',', $full, 2));
        } else {
            $parts = preg_split('/\s+/', $full);
            $lastPart  = array_pop($parts);
            $firstPart = implode(' ', $parts);
        }

        $firstTokens = preg_split('/\s+/', trim($firstPart));
        $first = array_shift($firstTokens) ?? '';
        $middle = $firstTokens ? implode(' ', $firstTokens) : null;

        return [$first, $middle, $lastPart];
    }

    private function summarize(array $groups): void
    {
        $employees = [];
        $projects  = [];
        $totalHours = 0.0;
        $totalPerDiem = 0.0;
        foreach ($groups as $g) {
            $employees[$g['emp_id']] = true;
            $projects[$g['job']] = true;
            $totalHours += $g['hours'];
            $totalPerDiem += $g['per_diem_days'];
        }
        $this->line("  Unique employees:  " . count($employees));
        $this->line("  Unique projects:   " . count($projects));
        $this->line("  Total work hours:  " . number_format($totalHours, 2));
        $this->line("  Total per-diem days: " . number_format($totalPerDiem, 2));
    }
}
