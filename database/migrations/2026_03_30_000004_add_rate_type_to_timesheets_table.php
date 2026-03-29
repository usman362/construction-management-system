<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            // Add rate_type enum column with default 'standard'
            $table->enum('rate_type', ['standard', 'loaded'])->default('standard')->after('billable_amount');

            // Add foreign key to project_billable_rates
            $table->foreignId('project_billable_rate_id')
                ->nullable()
                ->constrained('project_billable_rates')
                ->nullOnDelete()
                ->after('rate_type');

            // Add index for common queries
            $table->index('project_billable_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropForeignIdFor('project_billable_rates', 'project_billable_rate_id');
            $table->dropIndex(['project_billable_rate_id']);
            $table->dropColumn(['rate_type', 'project_billable_rate_id']);
        });
    }
};
