<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-04 (Brenda EST-BM-5751): labor lines were forcing OT after 8 hrs.
 * Her 5-10 schedule should stay straight-time through 10 hrs. Add a per-line
 * OT daily threshold so the ST/OT split is configurable (default handled in
 * code: falls back to the line's scheduled hours_per_day, i.e. no phantom OT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->decimal('ot_daily_threshold', 5, 2)->nullable()->after('hours_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropColumn('ot_daily_threshold');
        });
    }
};
