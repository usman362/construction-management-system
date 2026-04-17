<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "Need to be able to pay an employee multiple pay rates on
 * different jobs."
 *
 * A single employee can be paid differently on each project. When a
 * timesheet is saved, the system looks here first for a project-specific
 * hourly/overtime rate before falling back to the employee's default
 * rate on their profile.
 *
 * A row is matched by project_id + employee_id, with an optional
 * effective_date if the client wants to change the rate mid-job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_project_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('overtime_rate', 10, 2)->nullable();
            $table->decimal('double_time_rate', 10, 2)->nullable();
            $table->decimal('billable_rate', 10, 2)->nullable();
            $table->decimal('st_burden_rate', 10, 4)->nullable();
            $table->decimal('ot_burden_rate', 10, 4)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'employee_id', 'effective_date'], 'epr_proj_emp_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_project_rates');
    }
};
