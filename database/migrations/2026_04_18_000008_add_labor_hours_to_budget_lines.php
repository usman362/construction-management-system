<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "Do you think we need to have the manhours on the budget line also?"
 * Adds an optional `labor_hours` column so each budget line can track how
 * many manhours were budgeted for that phase/cost-type combination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->decimal('labor_hours', 10, 2)->default(0)->after('revised_amount');
        });
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropColumn('labor_hours');
        });
    }
};
