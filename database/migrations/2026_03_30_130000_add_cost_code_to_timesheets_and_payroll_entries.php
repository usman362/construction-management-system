<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->foreignId('cost_code_id')->nullable()->after('project_id')->constrained('cost_codes')->nullOnDelete();
        });

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->foreignId('cost_code_id')->nullable()->after('project_id')->constrained('cost_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_code_id');
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_code_id');
        });
    }
};
