<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split FICA, SUTA, WC, and Benefits rates on crafts into separate ST and OT
 * columns, per client's rate-analysis workbook (each craft has different
 * percentages/amounts for straight time vs. overtime).
 *
 * Strategy: rename existing single rate to *_st_rate, then add *_ot_rate.
 * Any existing values carry over as the ST rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rename existing single-rate columns to their ST variant
        DB::statement('ALTER TABLE `crafts` CHANGE `fica_rate`     `fica_st_rate`     DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `suta_rate`     `suta_st_rate`     DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `wc_rate`       `wc_st_rate`       DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `benefits_rate` `benefits_st_rate` DECIMAL(10,2) NULL');

        // Add corresponding OT columns right after their ST counterparts
        Schema::table('crafts', function (Blueprint $table) {
            $table->decimal('fica_ot_rate',     8, 4)->nullable()->after('fica_st_rate');
            $table->decimal('suta_ot_rate',     8, 4)->nullable()->after('suta_st_rate');
            $table->decimal('wc_ot_rate',       8, 4)->nullable()->after('wc_st_rate');
            $table->decimal('benefits_ot_rate', 10, 2)->nullable()->after('benefits_st_rate');
        });
    }

    public function down(): void
    {
        Schema::table('crafts', function (Blueprint $table) {
            $table->dropColumn(['fica_ot_rate', 'suta_ot_rate', 'wc_ot_rate', 'benefits_ot_rate']);
        });

        DB::statement('ALTER TABLE `crafts` CHANGE `fica_st_rate`     `fica_rate`     DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `suta_st_rate`     `suta_rate`     DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `wc_st_rate`       `wc_rate`       DECIMAL(8,4) NULL');
        DB::statement('ALTER TABLE `crafts` CHANGE `benefits_st_rate` `benefits_rate` DECIMAL(10,2) NULL');
    }
};
