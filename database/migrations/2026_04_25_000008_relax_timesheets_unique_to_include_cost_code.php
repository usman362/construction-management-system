<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BUG FIX (reported by Brenda 04.25.2026):
 *
 *   "I am trying to key hours to a person on the same day, same project,
 *    different phase code and it will not let me."
 *
 * The original timesheets table had a UNIQUE constraint on
 *   (employee_id, project_id, date)
 *
 * That prevented a worker from splitting hours across cost codes on the
 * same day — but in construction that's a normal pattern (4 hrs excavation,
 * 4 hrs concrete on the same day).
 *
 * This migration drops that constraint and replaces it with one that
 * includes cost_code_id, so the same worker/project/day combo is allowed
 * as long as each row has a different cost code.
 *
 * Additional safety: the new index uses an index name we control so the
 * down() migration can cleanly reverse without guessing.
 */
return new class extends Migration {
    public function up(): void
    {
        // Drop the existing unique constraint by its auto-generated name.
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropUnique('timesheets_employee_id_project_id_date_unique');
        });

        // Replace with a constraint that includes cost_code_id. We use a
        // composite KEY (not UNIQUE) on the simple 3-tuple so look-ups on
        // (employee, project, date) without cost_code are still fast.
        Schema::table('timesheets', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'project_id', 'date', 'cost_code_id'],
                'timesheets_emp_proj_date_code_unique'
            );

            // Plain index for the legacy 3-column lookup pattern (used by
            // TimeClockController::convertToTimesheet, payroll aggregation, etc.)
            $table->index(['employee_id', 'project_id', 'date'], 'timesheets_emp_proj_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropUnique('timesheets_emp_proj_date_code_unique');
            $table->dropIndex('timesheets_emp_proj_date_idx');
            $table->unique(['employee_id', 'project_id', 'date'], 'timesheets_employee_id_project_id_date_unique');
        });
    }
};
