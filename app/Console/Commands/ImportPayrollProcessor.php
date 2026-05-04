<?php

namespace App\Console\Commands;

use App\Models\CostCode;
use App\Models\CostType;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Timesheet;
use App\Models\TimesheetCostAllocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * One-off import of the legacy Payroll Pre-processor xlsx.
 *
 * Brenda 2026-05-04: "can you import this data for me?" — 2,593 rows of
 * already-processed payroll spanning 9 projects, 70 employees, 26 distinct
 * phase codes (some are change-order codes like BM-5286-018).
 *
 * Approach:
 *   1. DRY-RUN first (`--dry-run`) — resolves every FK, surfaces unmapped
 *      rows so we know what to fix in the source data BEFORE touching
 *      the live timesheets table.
 *   2. Real run wraps in a DB transaction; rows land with status='approved'
 *      (the xlsx Processed flag is true on all rows).
 *
 * Usage:
 *   php artisan timesheets:import-payroll path/to/file.xlsx --dry-run
 *   php artisan timesheets:import-payroll path/to/file.xlsx
 */
class ImportPayrollProcessor extends Command
{
    protected $signature = 'timesheets:import-payroll
                            {file : Path to the xlsx file}
                            {--dry-run : Resolve and report only, do not insert}
                            {--limit=0 : Cap the number of rows imported (0 = all)}';

    protected $description = 'Import the Payroll Pre-processor xlsx into the timesheets table';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) $this->option('limit');

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Reading {$file} …");

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheet(0);
        $rows  = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            $this->error('No data rows.');
            return self::FAILURE;
        }

        // Pre-cache lookups for speed
        $employeeByNumber = Employee::pluck('id', 'employee_number')->all();
        $costTypeByCode   = CostType::pluck('id', 'code')->all();
        $shiftByName      = Shift::pluck('id', 'name')->all();
        $costCodeByCode   = CostCode::pluck('id', 'code')->all();

        // Project lookup with BM-/SS-/JS-/GC- prefix tolerance because the
        // xlsx writes "5286" while our DB carries "BM-5286".
        $projects = Project::all(['id', 'project_number'])->keyBy('project_number');
        $resolveProject = function (string $raw) use ($projects) {
            $raw = trim($raw);
            if (isset($projects[$raw])) return $projects[$raw]->id;
            foreach (['BM-', 'SS-', 'JS-', 'GC-'] as $prefix) {
                if (isset($projects[$prefix . $raw])) return $projects[$prefix . $raw]->id;
            }
            return null;
        };

        // Phase-code resolver — tries the value as-is, then a few format
        // shapes the xlsx uses. Returns null if no DB row matches.
        $resolveCostCode = function ($raw) use ($costCodeByCode) {
            $candidates = [(string) $raw];
            if (is_numeric($raw)) {
                $candidates[] = sprintf('%05.2f', (float) $raw);
                $candidates[] = (string) intval($raw);
            }
            $s = trim((string) $raw);
            $candidates[] = ltrim($s, '0');
            foreach ($candidates as $c) {
                if (isset($costCodeByCode[$c])) return $costCodeByCode[$c];
            }
            return null;
        };

        $resolveShift = function ($raw) use ($shiftByName) {
            $raw = trim((string) $raw);
            foreach ([$raw, $raw . ' Shift', ucwords($raw) . ' Shift'] as $v) {
                if (isset($shiftByName[$v])) return $shiftByName[$v];
            }
            return null;
        };

        $stats = [
            'total_rows'         => 0,
            'imported'           => 0,
            'skipped_unmapped'   => 0,
            'skipped_zero_hours' => 0,
        ];
        $unmapped = [
            'projects'   => [],
            'employees'  => [],
            'cost_codes' => [],
            'cost_types' => [],
            'shifts'     => [],
        ];

        $createdIds = [];

        $tx = $dryRun ? function ($cb) { $cb(); } : function ($cb) { DB::transaction($cb); };
        $tx(function () use (
            $rows, $dryRun, $limit,
            $employeeByNumber, $costTypeByCode,
            $resolveProject, $resolveCostCode, $resolveShift,
            &$stats, &$unmapped, &$createdIds
        ) {
            // Skip header row (index 0)
            for ($i = 1; $i < count($rows); $i++) {
                if ($limit > 0 && $stats['imported'] >= $limit) break;

                $row = $rows[$i];
                $stats['total_rows']++;

                // Column indices (0-based): 0 Work Date, 1 EmpID, 4 Project,
                // 6 Phase Code, 7 Cost Type, 8 Work Order, 9 Reg, 10 OT,
                // 11 DT, 12 Shift, 13 Billable, 14 Pay Per Diem, 15 Per Diem
                [
                    $workDate, $empId, , , $proj, , $phaseCode, $costTypeCode, $workOrder,
                    $regHrs, $otHrs, $dtHrs, $shift, $billable, $payPerDiem, $perDiem,
                ] = array_pad($row, 16, null);

                $reg = (float) ($regHrs ?? 0);
                $ot  = (float) ($otHrs  ?? 0);
                $dt  = (float) ($dtHrs  ?? 0);
                if ($reg + $ot + $dt <= 0) {
                    $stats['skipped_zero_hours']++;
                    continue;
                }

                $employeeId = $employeeByNumber[trim((string) $empId)] ?? null;
                $projectId  = $proj !== null ? $resolveProject((string) $proj) : null;
                $costCodeId = $phaseCode !== null ? $resolveCostCode($phaseCode) : null;
                $costTypeId = $costTypeCode !== null ? ($costTypeByCode[(string) $costTypeCode] ?? null) : null;
                $shiftId    = $shift !== null ? $resolveShift($shift) : null;

                $missing = [];
                if (! $employeeId) { $missing[] = 'employee';  $unmapped['employees'][trim((string) $empId)]      = ($unmapped['employees'][trim((string) $empId)]      ?? 0) + 1; }
                if (! $projectId)  { $missing[] = 'project';   $unmapped['projects'][trim((string) $proj)]        = ($unmapped['projects'][trim((string) $proj)]        ?? 0) + 1; }
                if (! $costCodeId) { $missing[] = 'cost_code'; $unmapped['cost_codes'][trim((string) $phaseCode)] = ($unmapped['cost_codes'][trim((string) $phaseCode)] ?? 0) + 1; }
                if (! $costTypeId) { $missing[] = 'cost_type'; $unmapped['cost_types'][trim((string) $costTypeCode)] = ($unmapped['cost_types'][trim((string) $costTypeCode)] ?? 0) + 1; }
                if (! $shiftId)    { $missing[] = 'shift';     $unmapped['shifts'][trim((string) $shift)]        = ($unmapped['shifts'][trim((string) $shift)]        ?? 0) + 1; }

                if (! empty($missing)) {
                    $stats['skipped_unmapped']++;
                    continue;
                }

                $totalHours = $reg + $ot + $dt;

                if (! $dryRun) {
                    $ts = Timesheet::create([
                        'employee_id'       => $employeeId,
                        'project_id'        => $projectId,
                        'cost_code_id'      => $costCodeId,
                        'cost_type_id'      => $costTypeId,
                        'shift_id'          => $shiftId,
                        'date'              => is_numeric($workDate)
                            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($workDate)->format('Y-m-d')
                            : \Carbon\Carbon::parse($workDate)->format('Y-m-d'),
                        'work_order_number' => $workOrder ? trim((string) $workOrder) : null,
                        'regular_hours'     => $reg,
                        'overtime_hours'    => $ot,
                        'double_time_hours' => $dt,
                        'force_overtime'    => false,
                        'total_hours'       => $totalHours,
                        'is_billable'       => (bool) $billable,
                        'earnings_category' => 'HE',
                        'status'            => 'approved',
                        'notes'             => 'Imported from Payroll Pre-processor xlsx',
                    ]);
                    $createdIds[] = $ts->id;

                    if ($payPerDiem && $perDiem) {
                        TimesheetCostAllocation::create([
                            'timesheet_id'    => $ts->id,
                            'cost_code_id'    => $costCodeId,
                            'cost_type_id'    => CostType::where('code', '07')->value('id') ?: $costTypeId,
                            'per_diem_amount' => (float) $perDiem,
                            'hours'           => 0,
                            'cost'            => 0,
                        ]);
                    }
                }
                $stats['imported']++;
            }
        });

        $this->newLine();
        $this->line('────────────────────────────────────────');
        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Done.');
        $this->line(sprintf('  Total rows:               %d', $stats['total_rows']));
        $this->line(sprintf('  ' . ($dryRun ? 'Would import' : 'Imported') . ':            %d', $stats['imported']));
        $this->line(sprintf('  Skipped (zero hours):     %d', $stats['skipped_zero_hours']));
        $this->line(sprintf('  Skipped (unmapped FK):    %d', $stats['skipped_unmapped']));

        $this->newLine();
        foreach ($unmapped as $bucket => $values) {
            if (empty($values)) continue;
            $this->warn(strtoupper($bucket) . ' — unmapped (need DB rows or mapping):');
            arsort($values);
            foreach ($values as $val => $cnt) {
                $this->line(sprintf('    %-25s → %d row(s)', $val !== '' ? $val : '<blank>', $cnt));
            }
        }

        if (! $dryRun && ! empty($createdIds)) {
            $this->newLine();
            $this->info('First created timesheet ID: ' . $createdIds[0]);
            $this->info('Last created timesheet ID:  ' . end($createdIds));
        }

        return self::SUCCESS;
    }
}
