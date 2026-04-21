<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client request: "On the billable rates we need Base OT Hourly Rate* that is
 * where the markup rates need to build the OT Billable Rate."
 *
 * Today the `base_hourly_rate` column is used as the ST base, and the OT
 * billable rate is derived by adding `*_ot_rate` markup components on top.
 * The client wants the OT base to be separately editable (because OT wage
 * isn't always 1.5× the ST wage — union rules, prevailing wage schedules,
 * etc.). Adding `base_ot_hourly_rate` alongside `base_hourly_rate` so the
 * rate sheet has a true dedicated OT base to feed the markup math.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_billable_rates') && !Schema::hasColumn('project_billable_rates', 'base_ot_hourly_rate')) {
            Schema::table('project_billable_rates', function (Blueprint $table) {
                $table->decimal('base_ot_hourly_rate', 10, 4)
                    ->nullable()
                    ->after('base_hourly_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_billable_rates', 'base_ot_hourly_rate')) {
            Schema::table('project_billable_rates', function (Blueprint $table) {
                $table->dropColumn('base_ot_hourly_rate');
            });
        }
    }
};
