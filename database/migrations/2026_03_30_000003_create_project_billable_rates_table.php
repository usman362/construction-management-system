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
        Schema::create('project_billable_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('craft_id')->nullable()->constrained('crafts')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // Base rate
            $table->decimal('base_hourly_rate', 10, 2);

            // Markup rates (percentages)
            $table->decimal('payroll_tax_rate', 6, 4)->default(0);
            $table->decimal('burden_rate', 6, 4)->default(0);
            $table->decimal('insurance_rate', 6, 4)->default(0);
            $table->decimal('job_expenses_rate', 6, 4)->default(0);
            $table->decimal('consumables_rate', 6, 4)->default(0);
            $table->decimal('overhead_rate', 6, 4)->default(0);
            $table->decimal('profit_rate', 6, 4)->default(0);

            // Calculated loaded rates
            $table->decimal('straight_time_rate', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->decimal('double_time_rate', 12, 2)->default(0);

            // Effective date for rate versioning
            $table->date('effective_date');

            // Additional details
            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint ensures only one rate per combination per date
            $table->unique(['project_id', 'craft_id', 'employee_id', 'effective_date'], 'pbr_proj_craft_emp_date_unique');

            // Index for date-based lookups
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_billable_rates');
    }
};
