<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "We don't get overtime until we hit 40 hours, except for special things."
 *
 * OT is now calculated against a weekly 40-hour threshold (Mon–Sun), not a
 * daily 8-hour one. For the "special" cases (holidays, weekend premium,
 * client-approved OT) the user can tick a per-timesheet `force_overtime`
 * flag which makes those hours land in the OT bucket regardless of
 * where they fall in the week.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->boolean('force_overtime')->default(false)->after('double_time_hours');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('force_overtime');
        });
    }
};
