<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds operational tracking fields observed in the legacy superintendent Excel
 * timesheets (BM-5286 Nucor Maintenance and others):
 *
 *   - timesheets.gate_log_hours
 *     Actual badge-in time on site from the gate reader. This is independent of
 *     ST/OT hours (payable) — it's a verification metric. Example from the Excel:
 *     ST=10.5, Gate Log=10.42.
 *
 *   - employees.contract_weekly_hours
 *     Baseline expected hours per week for variance reporting. Excel Total Sheet
 *     shows "Contract Hours = 40" on every employee row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->decimal('gate_log_hours', 6, 2)->nullable()->after('total_hours');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('contract_weekly_hours', 5, 2)->nullable()->default(40)->after('billable_rate');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('gate_log_hours');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('contract_weekly_hours');
        });
    }
};
