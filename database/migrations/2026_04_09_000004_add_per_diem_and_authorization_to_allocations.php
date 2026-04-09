<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two fields to timesheet_cost_allocations mirroring the per-cost-code
 * tracking observed in the Excel superintendent templates:
 *
 *   - per_diem_amount
 *     Daily flat-rate allowance earned by the employee on that day for that
 *     cost code. Atalco templates (BM-5403) track this per cost-code block
 *     (Per Diem, Per Diem 2, Per Diem 3, Per Diem 4) because an employee
 *     splitting time across 2 cost codes may earn separate per diems.
 *     Separate from the per_diem_rates table which holds *project rates* only.
 *
 *   - work_authorization
 *     Reference number for FCO (Field Change Order), VOR (Variance Order
 *     Request), or IWP (Install Work Package). Free-text for now — future
 *     work can FK it to change_orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheet_cost_allocations', function (Blueprint $table) {
            $table->decimal('per_diem_amount', 10, 2)->default(0)->after('cost');
            $table->string('work_authorization', 100)->nullable()->after('per_diem_amount');
        });
    }

    public function down(): void
    {
        Schema::table('timesheet_cost_allocations', function (Blueprint $table) {
            $table->dropColumn(['per_diem_amount', 'work_authorization']);
        });
    }
};
