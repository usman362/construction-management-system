<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-23 (Brenda): "Can we add ST and OT to this entry process,
 * so I do not have to make two separate entries." Add ot_hours +
 * ot_hourly_cost_rate + ot_hourly_billable_rate to estimate_lines
 * so one labor line carries BOTH straight-time and overtime values.
 *
 * Existing labor lines (single-rate) still work — `hours` /
 * `hourly_cost_rate` / `hourly_billable_rate` continue to mean the
 * straight-time portion. The new OT columns are nullable; when
 * present they add to cost and billable totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->decimal('ot_hours',                12, 2)->nullable()->after('hours');
            $table->decimal('ot_hourly_cost_rate',     12, 4)->nullable()->after('hourly_cost_rate');
            $table->decimal('ot_hourly_billable_rate', 12, 4)->nullable()->after('hourly_billable_rate');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropColumn(['ot_hours', 'ot_hourly_cost_rate', 'ot_hourly_billable_rate']);
        });
    }
};
