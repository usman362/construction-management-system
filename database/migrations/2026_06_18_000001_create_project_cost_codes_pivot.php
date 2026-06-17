<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brenda 2026-06-17: "Can we make the phase codes job specific? I just
 * keyed two days worth of timesheets to the incorrect phase code…"
 *
 * Pivot to subset the global cost_codes library down to the codes that
 * apply to a given project. Pickers (timesheet, invoice, estimate, CO,
 * daily log, etc.) filter by this list when a project is in scope.
 *
 * Fallback rule: when a project has ZERO rows in this pivot, the picker
 * still shows the full global list — so legacy projects don't break
 * until they're explicitly set up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_cost_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_code_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'cost_code_id']);
            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_cost_codes');
    }
};
