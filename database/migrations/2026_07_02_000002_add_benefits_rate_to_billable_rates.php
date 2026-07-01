<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-02 (Brenda): "The cost to pay an employee should only include
 * futa/suta/fica/workmans comp/benefits."
 *
 * Cost already loads Payroll Tax (FICA/FUTA) + Burden (SUTA) + Insurance (WC).
 * Benefits had no field — add benefits_rate / benefits_ot_rate so it can be
 * counted on the COST side (not markup).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->decimal('benefits_rate', 8, 4)->nullable()->after('insurance_rate');
            $table->decimal('benefits_ot_rate', 8, 4)->nullable()->after('insurance_ot_rate');
        });
    }

    public function down(): void
    {
        Schema::table('project_billable_rates', function (Blueprint $table) {
            $table->dropColumn(['benefits_rate', 'benefits_ot_rate']);
        });
    }
};
