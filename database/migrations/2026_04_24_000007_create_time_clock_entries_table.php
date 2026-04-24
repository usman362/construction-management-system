<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual clock-in / clock-out punches captured from the mobile UI.
     * Distinct from `timesheets` (which are day-aggregated, approvable
     * records). A supervisor later converts one or more punches into a
     * timesheet row — that's the point at which payroll sees them.
     */
    public function up(): void
    {
        Schema::create('time_clock_entries', function (Blueprint $table) {
            $table->id();

            // The human who pressed "Clock In" on their phone.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // The employee record the hours are booked to. Users sometimes
            // punch in as a helper (e.g. a foreman logging crew time), so
            // user_id and employee_id are not always the same person.
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Optional cost code capture at clock-in time — keeps the punch
            // ready to convert to a timesheet without further data entry.
            $table->foreignId('cost_code_id')->nullable()->constrained('cost_codes')->nullOnDelete();

            $table->timestamp('clock_in_at');
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            // Browser-reported GPS accuracy in meters — kept for audit.
            $table->unsignedSmallInteger('clock_in_accuracy_m')->nullable();

            $table->timestamp('clock_out_at')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('clock_out_accuracy_m')->nullable();

            // Derived at clock-in: was the device inside the project's
            // configured geofence? Null = no geofence set or no GPS.
            $table->boolean('within_geofence')->nullable();
            // Distance from geofence center at clock-in, meters.
            $table->unsignedInteger('distance_m')->nullable();

            // Computed on clock-out. Decimal for partial-hour precision.
            $table->decimal('hours', 6, 2)->nullable();

            $table->text('notes')->nullable();

            // Once supervisor rolls this punch into a daily timesheet,
            // the resulting timesheet row is linked here to prevent
            // double-billing.
            $table->foreignId('timesheet_id')->nullable()->constrained('timesheets')->nullOnDelete();

            $table->enum('status', ['open', 'closed', 'converted', 'voided'])->default('open')->index();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'clock_in_at']);
            $table->index(['employee_id', 'clock_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_clock_entries');
    }
};
