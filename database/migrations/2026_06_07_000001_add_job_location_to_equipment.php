<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-06-07 (Brenda): "Did you add the job number and location to the
 * equipment rental section?" Followed up with a yes-please on adding
 * them to the equipment side too.
 *
 * Pairs the existing rent_start_date / rent_end_date columns so the
 * office can record "this rental is for job BM-5413 at Gramercy, LA
 * from 6/1 → 6/30" in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment', 'job_number')) {
                $table->string('job_number', 100)->nullable()->after('rent_end_date');
            }
            if (! Schema::hasColumn('equipment', 'location')) {
                $table->string('location', 255)->nullable()->after('job_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (Schema::hasColumn('equipment', 'location'))   $table->dropColumn('location');
            if (Schema::hasColumn('equipment', 'job_number')) $table->dropColumn('job_number');
        });
    }
};
