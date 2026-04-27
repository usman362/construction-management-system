<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuel tracking per equipment per project.
 *
 * Every fill-up gets one row: which equipment, which project (the cost
 * center), how many gallons, $/gallon, total cost. Costs roll up in the
 * cost report under whatever cost code the user picks (typically the
 * "Fuel" or "Equipment Operating" code).
 *
 * Optional odometer / hour-meter reading so the team can spot:
 *   - fuel theft (cost per hour way out of normal range)
 *   - maintenance due (X hours since last service)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('cost_code_id')->nullable()->constrained('cost_codes')->nullOnDelete();
            $table->date('fuel_date');
            $table->string('fuel_type', 30)->default('diesel');  // diesel, unleaded, premium
            $table->decimal('gallons', 10, 3);
            $table->decimal('price_per_gallon', 8, 4);
            $table->decimal('total_cost', 12, 2);                // gallons × ppg, persisted for fast reports
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->unsignedInteger('hour_meter_reading')->nullable();
            $table->string('vendor_name', 150)->nullable();      // free-text, not a vendor FK (gas stations rarely match the vendor list)
            $table->string('receipt_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['equipment_id', 'fuel_date']);
            $table->index(['project_id', 'fuel_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
