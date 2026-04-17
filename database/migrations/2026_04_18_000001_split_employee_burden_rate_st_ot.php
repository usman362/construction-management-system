<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: split the single `burden_rate` on employees into
 * `st_burden_rate` and `ot_burden_rate` so the system can compute fully
 * burdened cost (wage + SUTA + FICA + WC + benefits + overhead) on every
 * timesheet automatically — separately for straight time and overtime.
 *
 * These values are $/hr, pooled — not percentages.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rename existing burden_rate to st_burden_rate
        // (values carry over as the ST rate).
        DB::statement('ALTER TABLE `employees` CHANGE `burden_rate` `st_burden_rate` DECIMAL(10,4) NULL');

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('ot_burden_rate', 10, 4)->nullable()->after('st_burden_rate');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('ot_burden_rate');
        });

        DB::statement('ALTER TABLE `employees` CHANGE `st_burden_rate` `burden_rate` DECIMAL(5,2) NULL');
    }
};
