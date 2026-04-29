<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUG FIX (reported by Brenda 04.29.2026 6:38 AM):
 *
 *   "this happend when I clicked enter" — screenshot shows
 *   SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
 *   '26-11-2026-01-05-89' for key 'timesheets_emp_proj_date_code_unique'
 *
 * The constraint was relaxed once on 04.25.2026 to include cost_code_id,
 * but the Foundation-style bulk batch entry needs to be more permissive
 * still — a payroll clerk routinely keys multiple lines for the same
 * (employee, project, date, cost_code) combination when:
 *
 *   - splitting ST and OT into separate rows
 *   - same person, same day, same job, but two different shifts
 *     (day shift premium + night shift premium)
 *   - same day worked + holiday hours (HE + HO earnings categories)
 *   - different cost types within the same cost code (labor / labor burden)
 *
 * Foundation Software / ComputerEase / and most legacy payroll systems
 * treat each timesheet row as a labor distribution line, not a daily
 * aggregate, and do NOT impose a uniqueness constraint at this level.
 *
 * Decision: drop the unique constraint entirely. Keep a non-unique
 * composite index so look-ups by (employee, project, date) stay fast.
 *
 * Risk acknowledged: the application no longer rejects accidental
 * exact-duplicate entries at the DB level. The bulk-create UI's running
 * list shows everything keyed in the current session so the clerk can
 * spot a fat-finger duplicate before submitting; admins can also de-dup
 * via the timesheet list filters if needed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            // Drop the previous unique constraint (added 04.25.2026).
            $table->dropUnique('timesheets_emp_proj_date_code_unique');
        });

        // Replace with a non-unique composite index covering the same
        // four columns. Same lookup speed, no rejection. Wrapped in its
        // own Schema::table() call because dropUnique + addIndex on the
        // same blueprint can confuse some MySQL versions.
        Schema::table('timesheets', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'project_id', 'date', 'cost_code_id'],
                'timesheets_emp_proj_date_code_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropIndex('timesheets_emp_proj_date_code_idx');
        });

        // Restore the previous unique constraint. NOTE: if duplicate
        // rows have been entered since this migration ran, the down()
        // will fail until those duplicates are resolved manually.
        Schema::table('timesheets', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'project_id', 'date', 'cost_code_id'],
                'timesheets_emp_proj_date_code_unique'
            );
        });
    }
};
