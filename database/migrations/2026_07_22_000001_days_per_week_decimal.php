<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-22 (Brenda, BM-5462): crews work partial days — 1.5 days for the
 * fitter/welder, supervisor 1.5 days @ 5.33 hrs. days_per_week was a whole
 * number; make it a decimal so fractional days are allowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MODIFY in place to preserve existing values.
        DB::statement('ALTER TABLE estimate_lines MODIFY days_per_week DECIMAL(5,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE estimate_lines MODIFY days_per_week SMALLINT UNSIGNED NULL');
    }
};
