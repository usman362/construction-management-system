<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estimate Refactor Phase 1 — T&M Estimate Template layout.
 *
 * Labor lines gain a category (direct / indirect / field_staff), crew-based
 * scheduling fields, and a third pay tier (premium). Estimates gain project-
 * level duration defaults. Equipment lines gain a duration_uom for
 * daily/weekly/monthly pricing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Estimate-level duration/schedule defaults ──
        Schema::table('estimates', function (Blueprint $table) {
            $table->unsignedSmallInteger('project_duration_weeks')->nullable()->after('job_number');
            $table->string('work_schedule', 10)->nullable()->after('project_duration_weeks');
            $table->unsignedSmallInteger('field_staff_duration_weeks')->nullable()->after('work_schedule');
        });

        // ── Estimate line: labor category + crew fields + premium pay + equipment duration ──
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->string('labor_category', 30)->nullable()->after('line_type');
            $table->string('work_schedule', 10)->nullable()->after('labor_category');
            $table->string('role', 100)->nullable()->after('work_schedule');
            $table->unsignedSmallInteger('crew_size')->nullable()->after('role');
            $table->decimal('weeks', 8, 2)->nullable()->after('crew_size');
            $table->unsignedSmallInteger('days_per_week')->nullable()->after('weeks');
            $table->decimal('hours_per_day', 5, 2)->nullable()->after('days_per_week');

            // Premium (3rd pay tier — double-time / premium)
            $table->decimal('premium_hours', 12, 2)->nullable()->after('ot_hourly_billable_rate');
            $table->decimal('premium_hourly_cost_rate', 12, 4)->nullable()->after('premium_hours');
            $table->decimal('premium_hourly_billable_rate', 12, 4)->nullable()->after('premium_hourly_cost_rate');

            // Equipment: duration UOM (daily / weekly / monthly)
            $table->string('duration_uom', 10)->nullable()->after('equipment_id');
            $table->decimal('equipment_duration', 8, 2)->nullable()->after('duration_uom');
            $table->decimal('fuel_cost', 12, 2)->nullable()->after('equipment_duration');

            // Vendor name for materials (free-text, not FK — matches template)
            $table->string('vendor_name', 255)->nullable()->after('material_id');

            // Equipment sub-type: 3rd_party vs company_owned
            $table->string('equipment_category', 20)->nullable()->after('equipment_id');

            // Subcontractor fields
            $table->string('subcontractor_name', 255)->nullable()->after('vendor_name');
            $table->string('discipline', 255)->nullable()->after('subcontractor_name');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropColumn([
                'labor_category', 'work_schedule', 'role', 'crew_size',
                'weeks', 'days_per_week', 'hours_per_day',
                'premium_hours', 'premium_hourly_cost_rate', 'premium_hourly_billable_rate',
                'duration_uom', 'equipment_duration', 'fuel_cost',
                'vendor_name', 'equipment_category',
                'subcontractor_name', 'discipline',
            ]);
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['project_duration_weeks', 'work_schedule', 'field_staff_duration_weeks']);
        });
    }
};
