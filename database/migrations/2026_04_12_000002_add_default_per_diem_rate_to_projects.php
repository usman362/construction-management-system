<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "The per diem amount can also vary by project."
 * Adds a single default per-diem rate to each project so timesheet cost
 * allocations can auto-fill from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('default_per_diem_rate', 10, 2)->nullable()->after('contract_value');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('default_per_diem_rate');
        });
    }
};
