<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-17 (Brenda): FRC = Fire Retardant Clothing ("uniforms"). It's one
 * of the billable markups built into each craft's rate, alongside job
 * expenses / consumables / overhead / profit. Add its columns so the billable
 * rate can include it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->decimal('frc_rate', 8, 4)->nullable()->after('benefits_rate');
            $table->decimal('frc_ot_rate', 8, 4)->nullable()->after('benefits_ot_rate');
        });
    }

    public function down(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->dropColumn(['frc_rate', 'frc_ot_rate']);
        });
    }
};
