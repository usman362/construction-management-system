<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-06-04 (Brenda): "I also need to add a section for me to put the
 * location and a job number" + "I also do not see where to put the
 * start and stop rent date for the equipment. It is not in the set
 * up, view, or edit tabs"
 *
 * - estimates.location              — free-text job site location.
 * - estimates.job_number            — Brenda's internal job # on the
 *                                     estimate (separate from project_number
 *                                     since the same job can have multiple
 *                                     estimates / revisions).
 * - equipment.rent_start_date       — when the rental window starts.
 * - equipment.rent_end_date         — when it ends. Pair drives the
 *                                     daily/weekly/monthly cost on the
 *                                     equipment line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (! Schema::hasColumn('estimates', 'location')) {
                $table->string('location', 255)->nullable()->after('description');
            }
            if (! Schema::hasColumn('estimates', 'job_number')) {
                $table->string('job_number', 100)->nullable()->after('location');
            }
        });

        Schema::table('equipment', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment', 'rent_start_date')) {
                $table->date('rent_start_date')->nullable()->after('monthly_rate');
            }
            if (! Schema::hasColumn('equipment', 'rent_end_date')) {
                $table->date('rent_end_date')->nullable()->after('rent_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (Schema::hasColumn('estimates', 'job_number')) $table->dropColumn('job_number');
            if (Schema::hasColumn('estimates', 'location'))   $table->dropColumn('location');
        });
        Schema::table('equipment', function (Blueprint $table) {
            if (Schema::hasColumn('equipment', 'rent_end_date'))   $table->dropColumn('rent_end_date');
            if (Schema::hasColumn('equipment', 'rent_start_date')) $table->dropColumn('rent_start_date');
        });
    }
};
