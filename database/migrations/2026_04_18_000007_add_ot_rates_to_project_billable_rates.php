<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "We will also need ST and OT slots on the billable rates for
 * the insurance, taxes, burdens, etc."
 *
 * The existing `payroll_tax_rate`, `burden_rate`, `insurance_rate` columns
 * stay as the straight-time (ST) rates for backwards compatibility. This
 * migration adds matching OT variants. Other markup fields (job_expenses,
 * consumables, overhead, profit) are typically the same % for ST and OT,
 * but we add OT variants for those too so the client can override if the
 * rate sheet calls for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->decimal('payroll_tax_ot_rate', 6, 4)->default(0)->after('payroll_tax_rate');
            $table->decimal('burden_ot_rate',       6, 4)->default(0)->after('burden_rate');
            $table->decimal('insurance_ot_rate',    6, 4)->default(0)->after('insurance_rate');
            $table->decimal('job_expenses_ot_rate', 6, 4)->default(0)->after('job_expenses_rate');
            $table->decimal('consumables_ot_rate',  6, 4)->default(0)->after('consumables_rate');
            $table->decimal('overhead_ot_rate',     6, 4)->default(0)->after('overhead_rate');
            $table->decimal('profit_ot_rate',       6, 4)->default(0)->after('profit_rate');
        });
    }

    public function down(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_tax_ot_rate', 'burden_ot_rate', 'insurance_ot_rate',
                'job_expenses_ot_rate', 'consumables_ot_rate',
                'overhead_ot_rate', 'profit_ot_rate',
            ]);
        });
    }
};
