<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-12 (Brenda — shop crew enhancement).
 *
 * Shop crew rotates across multiple jobs during the day without badging
 * in/out at each one. They clock in ONCE in the morning and out ONCE in
 * the evening. The foreman then allocates the day's total hours across
 * the jobs they actually worked.
 *
 * Each allocation row = (Project, Cost Code, Hours). At conversion time,
 * one Timesheet is created per allocation. If a punch has zero allocations
 * the old single-timesheet behavior kicks in (covers crews that only work
 * one job all day — the common case).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_clock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_clock_entry_id')->constrained('time_clock_entries')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('cost_code_id')->nullable()->constrained('cost_codes')->nullOnDelete();
            $table->decimal('hours', 6, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('time_clock_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_clock_allocations');
    }
};
