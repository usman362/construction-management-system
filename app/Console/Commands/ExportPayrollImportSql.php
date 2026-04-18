<?php

namespace App\Console\Commands;

use App\Models\CostCode;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimesheetCostAllocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Emits a production-safe SQL file that replays the payroll import on a
 * different database. Uses natural keys (employee_number, project_number)
 * inside subqueries so the receiving DB's auto-increment IDs don't have
 * to match ours.
 */
class ExportPayrollImportSql extends Command
{
    protected $signature = 'export:payroll-import-sql
        {--out=/tmp/payroll-import.sql : Output file path}
        {--since= : Only export rows whose created_at >= this date (e.g. 2026-04-18)}';

    protected $description = 'Export the just-imported payroll timesheets as SQL INSERTs keyed by natural keys';

    public function handle(): int
    {
        $out = $this->option('out');
        $since = $this->option('since');

        $fh = fopen($out, 'w');
        if (!$fh) {
            $this->error("Cannot write to {$out}");
            return self::FAILURE;
        }

        fwrite($fh, "-- Payroll import replay SQL\n");
        fwrite($fh, "-- Generated: " . now()->toDateTimeString() . "\n");
        fwrite($fh, "-- Runs idempotently on any MySQL DB that already has the\n");
        fwrite($fh, "-- same schema; uses natural keys so IDs don't need to match.\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");

        // The timesheet set is what --since filters. Employees and Projects are
        // then derived from that set (NOT filtered by created_at) so production
        // gets every employee_number/project_number the timesheets reference,
        // even ones that already existed locally before the import.
        $tsQuery = Timesheet::query()->with(['employee:id,employee_number', 'project:id,project_number']);
        $allocQuery = TimesheetCostAllocation::query();
        if ($since) {
            $tsQuery->where('created_at', '>=', $since);
            $allocQuery->whereHas('timesheet', fn ($q) => $q->where('created_at', '>=', $since));
        }

        // Resolve referenced employee/project IDs from the filtered timesheet set
        $referencedEmployeeIds = (clone $tsQuery)->pluck('employee_id')->unique();
        $referencedProjectIds  = (clone $tsQuery)->pluck('project_id')->unique();
        $empQuery = Employee::whereIn('id', $referencedEmployeeIds);
        $projQuery = Project::whereIn('id', $referencedProjectIds);

        // ========= Cost Codes (just LABOR) =========
        fwrite($fh, "-- Default LABOR cost code used by the importer\n");
        fwrite($fh, "INSERT IGNORE INTO `cost_codes` (`code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES\n");
        fwrite($fh, "  ('LABOR', 'General Labor (import default)', 1, NOW(), NOW());\n\n");

        // ========= Employees =========
        $employees = $empQuery->get();
        $this->line("  Exporting {$employees->count()} employees");
        if ($employees->isNotEmpty()) {
            fwrite($fh, "-- Employees (matched on employee_number UNIQUE)\n");
            fwrite($fh, "INSERT IGNORE INTO `employees` (`employee_number`, `first_name`, `middle_name`, `last_name`, `hourly_rate`, `overtime_rate`, `billable_rate`, `status`, `created_at`, `updated_at`) VALUES\n");
            $vals = [];
            foreach ($employees as $e) {
                $vals[] = sprintf(
                    "  (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    $this->q($e->employee_number),
                    $this->q($e->first_name),
                    $this->q($e->middle_name),
                    $this->q($e->last_name),
                    $this->n($e->hourly_rate ?? 0),
                    $this->n($e->overtime_rate ?? 0),
                    $this->n($e->billable_rate ?? 0),
                    $this->q($e->status ?? 'active'),
                    $this->q((string) $e->created_at),
                    $this->q((string) $e->updated_at)
                );
            }
            fwrite($fh, implode(",\n", $vals) . ";\n\n");
        }

        // ========= Projects =========
        $projects = $projQuery->get();
        $this->line("  Exporting {$projects->count()} projects");
        if ($projects->isNotEmpty()) {
            fwrite($fh, "-- Projects (matched on project_number UNIQUE)\n");
            fwrite($fh, "INSERT IGNORE INTO `projects` (`project_number`, `name`, `status`, `start_date`, `created_at`, `updated_at`) VALUES\n");
            $vals = [];
            foreach ($projects as $p) {
                $vals[] = sprintf(
                    "  (%s, %s, %s, %s, %s, %s)",
                    $this->q($p->project_number),
                    $this->q($p->name),
                    $this->q($p->status ?? 'active'),
                    $this->q($p->start_date ? $p->start_date->toDateString() : null),
                    $this->q((string) $p->created_at),
                    $this->q((string) $p->updated_at)
                );
            }
            fwrite($fh, implode(",\n", $vals) . ";\n\n");
        }

        // ========= Timesheets =========
        // We use a subquery to resolve employee_id / project_id from natural keys
        // so production's auto-increment IDs don't have to match ours.
        $timesheets = $tsQuery->get();
        $this->line("  Exporting {$timesheets->count()} timesheets");
        if ($timesheets->isNotEmpty()) {
            fwrite($fh, "-- Timesheets — employee_id/project_id resolved by natural keys\n");
            fwrite($fh, "-- Uniqueness on (employee_id, project_id, date) prevents duplicates on re-run.\n");

            // Batch in chunks of 200 to keep single statements reasonable.
            $chunks = $timesheets->chunk(200);
            foreach ($chunks as $chunk) {
                fwrite($fh, "INSERT IGNORE INTO `timesheets`\n");
                fwrite($fh, "  (`employee_id`, `project_id`, `date`, `regular_hours`, `overtime_hours`, `double_time_hours`, `total_hours`, `regular_rate`, `overtime_rate`, `total_cost`, `is_billable`, `status`, `notes`, `created_at`, `updated_at`)\n");
                fwrite($fh, "SELECT * FROM (\n");
                $parts = [];
                foreach ($chunk as $t) {
                    if (!$t->employee || !$t->project) continue;
                    $parts[] = sprintf(
                        "  SELECT (SELECT id FROM employees WHERE employee_number=%s LIMIT 1) AS employee_id,\n         (SELECT id FROM projects WHERE project_number=%s LIMIT 1) AS project_id,\n         %s AS `date`, %s AS regular_hours, %s AS overtime_hours, %s AS double_time_hours, %s AS total_hours,\n         %s AS regular_rate, %s AS overtime_rate, %s AS total_cost, %s AS is_billable, %s AS `status`, %s AS notes,\n         %s AS created_at, %s AS updated_at",
                        $this->q($t->employee->employee_number),
                        $this->q($t->project->project_number),
                        $this->q($t->date->toDateString()),
                        $this->n($t->regular_hours),
                        $this->n($t->overtime_hours),
                        $this->n($t->double_time_hours),
                        $this->n($t->total_hours),
                        $this->n($t->regular_rate),
                        $this->n($t->overtime_rate),
                        $this->n($t->total_cost),
                        $t->is_billable ? '1' : '0',
                        $this->q($t->status),
                        $this->q($t->notes),
                        $this->q((string) $t->created_at),
                        $this->q((string) $t->updated_at)
                    );
                }
                fwrite($fh, implode("\n  UNION ALL\n", $parts));
                fwrite($fh, "\n) AS src\nWHERE src.employee_id IS NOT NULL AND src.project_id IS NOT NULL;\n\n");
            }
        }

        // ========= Allocations =========
        // Resolve timesheet_id by (employee_number, project_number, date), and
        // cost_code_id by code='LABOR'.
        $allocs = $allocQuery->with('timesheet.employee:id,employee_number', 'timesheet.project:id,project_number', 'costCode:id,code')->get();
        $this->line("  Exporting {$allocs->count()} allocations");
        if ($allocs->isNotEmpty()) {
            fwrite($fh, "-- Timesheet cost allocations — timesheet_id + cost_code_id resolved by natural keys\n");

            // Allocations have no unique constraint, so re-running would duplicate
            // them. Before inserting, wipe any existing allocations for the exact
            // timesheets this file is about to (re)create. Safe because the
            // allocation rows are derived data, not user-entered.
            fwrite($fh, "-- Wipe any prior allocations for the timesheets in this batch so re-runs stay idempotent\n");
            fwrite($fh, "DELETE a FROM `timesheet_cost_allocations` a\n");
            fwrite($fh, "JOIN `timesheets` t ON t.id = a.timesheet_id\n");
            fwrite($fh, "JOIN `employees` e ON e.id = t.employee_id\n");
            fwrite($fh, "JOIN `projects` p ON p.id = t.project_id\n");
            fwrite($fh, "WHERE (e.employee_number, p.project_number, t.date) IN (\n");
            $tuples = [];
            foreach ($allocs as $a) {
                $ts = $a->timesheet;
                if (!$ts || !$ts->employee || !$ts->project) continue;
                $tuples[] = sprintf("(%s, %s, %s)",
                    $this->q($ts->employee->employee_number),
                    $this->q($ts->project->project_number),
                    $this->q($ts->date->toDateString())
                );
            }
            // Chunk the IN-tuples in case the list is huge
            $tupleChunks = array_chunk($tuples, 500);
            foreach ($tupleChunks as $i => $chunk) {
                if ($i === 0) {
                    fwrite($fh, "  " . implode(",\n  ", $chunk) . "\n");
                } else {
                    // Continue the IN list across chunks
                    fwrite($fh, ",\n  " . implode(",\n  ", $chunk) . "\n");
                }
            }
            fwrite($fh, ");\n\n");
            $chunks = $allocs->chunk(200);
            foreach ($chunks as $chunk) {
                fwrite($fh, "INSERT IGNORE INTO `timesheet_cost_allocations`\n");
                fwrite($fh, "  (`timesheet_id`, `cost_code_id`, `cost_type_id`, `hours`, `cost`, `per_diem_amount`, `created_at`, `updated_at`)\n");
                fwrite($fh, "SELECT * FROM (\n");
                $parts = [];
                foreach ($chunk as $a) {
                    $ts = $a->timesheet;
                    if (!$ts || !$ts->employee || !$ts->project) continue;
                    $parts[] = sprintf(
                        "  SELECT (SELECT t.id FROM timesheets t JOIN employees e ON e.id=t.employee_id JOIN projects p ON p.id=t.project_id WHERE e.employee_number=%s AND p.project_number=%s AND t.date=%s LIMIT 1) AS timesheet_id,\n         (SELECT id FROM cost_codes WHERE code=%s LIMIT 1) AS cost_code_id,\n         NULL AS cost_type_id, %s AS hours, %s AS cost, %s AS per_diem_amount, %s AS created_at, %s AS updated_at",
                        $this->q($ts->employee->employee_number),
                        $this->q($ts->project->project_number),
                        $this->q($ts->date->toDateString()),
                        $this->q($a->costCode?->code ?? 'LABOR'),
                        $this->n($a->hours),
                        $this->n($a->cost),
                        $this->n($a->per_diem_amount),
                        $this->q((string) $a->created_at),
                        $this->q((string) $a->updated_at)
                    );
                }
                fwrite($fh, implode("\n  UNION ALL\n", $parts));
                fwrite($fh, "\n) AS src\nWHERE src.timesheet_id IS NOT NULL AND src.cost_code_id IS NOT NULL;\n\n");
            }
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);

        $size = number_format(filesize($out) / 1024, 1);
        $this->info("Wrote {$out} ({$size} KB)");
        return self::SUCCESS;
    }

    /** Single-quote + escape for SQL, or NULL.
     *  Collapses real newlines/CRs to " | " so phpMyAdmin's SQL parser doesn't
     *  choke on backslash-n escapes (it treats "\\n" as an unexpected char).
     *  Escapes single quotes with SQL-standard doubling ('' -> ').
     *  Escapes backslashes so MySQL doesn't interpret them. */
    private function q($v): string
    {
        if ($v === null || $v === '') return 'NULL';
        $s = (string) $v;
        $s = str_replace(["\r\n", "\r", "\n"], ' | ', $s);
        $s = str_replace(['\\', "'"], ['\\\\', "''"], $s);
        return "'" . $s . "'";
    }

    /** Numeric with sensible fallback. */
    private function n($v): string
    {
        if ($v === null || $v === '') return '0';
        return (string) (float) $v;
    }
}
