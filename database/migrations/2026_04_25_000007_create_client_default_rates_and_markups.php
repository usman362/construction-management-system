<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estimating Phase 1 — multi-client billable rate + markup defaults.
 *
 * Right now ProjectBillableRate only exists per-project. When we estimate a
 * brand-new job, no Project record exists yet, so we have nothing to look
 * billable rates up against. These two tables hold the *defaults* per client
 * — the estimator picks Client + Craft, and the system pre-fills cost rate,
 * billable rate, and markup % from these tables.
 *
 * On accept-and-convert-to-project, the estimate copies these into actual
 * ProjectBillableRate rows so the new project starts pre-rated for timesheets.
 *
 * Schema mirrors ProjectBillableRate's markup pipeline (ST + OT pairs for
 * every burden component) so the conversion is a 1:1 column copy.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_default_billable_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('craft_id')->nullable()->constrained('crafts')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // Base rates
            $table->decimal('base_hourly_rate', 10, 2)->default(0);
            $table->decimal('base_ot_hourly_rate', 10, 4)->nullable();

            // ST markup %s — match ProjectBillableRate field names exactly.
            $table->decimal('payroll_tax_rate', 6, 4)->default(0);
            $table->decimal('burden_rate', 6, 4)->default(0);
            $table->decimal('insurance_rate', 6, 4)->default(0);
            $table->decimal('job_expenses_rate', 6, 4)->default(0);
            $table->decimal('consumables_rate', 6, 4)->default(0);
            $table->decimal('overhead_rate', 6, 4)->default(0);
            $table->decimal('profit_rate', 6, 4)->default(0);

            // OT markup %s (often differ from ST due to WC modifiers, blended overhead)
            $table->decimal('payroll_tax_ot_rate', 6, 4)->default(0);
            $table->decimal('burden_ot_rate', 6, 4)->default(0);
            $table->decimal('insurance_ot_rate', 6, 4)->default(0);
            $table->decimal('job_expenses_ot_rate', 6, 4)->default(0);
            $table->decimal('consumables_ot_rate', 6, 4)->default(0);
            $table->decimal('overhead_ot_rate', 6, 4)->default(0);
            $table->decimal('profit_ot_rate', 6, 4)->default(0);

            // Calculated, populated by model booted() hook (same as ProjectBillableRate)
            $table->decimal('straight_time_rate', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->decimal('double_time_rate', 12, 2)->default(0);

            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One default rate per (client, craft, employee, effective_date) — same
            // shape as ProjectBillableRate so accidental duplicates don't crash.
            $table->unique(
                ['client_id', 'craft_id', 'employee_id', 'effective_date'],
                'client_default_rates_unique'
            );
        });

        Schema::create('client_default_markups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            // Optional cost-type filter (e.g. only labor markups for this client).
            // NULL = applies to every cost type as a fallback.
            $table->foreignId('cost_type_id')->nullable()->constrained('cost_types')->nullOnDelete();

            // Per-line-type markup defaults — used when an estimator adds a line
            // without a manual markup, the system reads these.
            $table->decimal('labor_markup_percent', 6, 4)->default(0);
            $table->decimal('material_markup_percent', 6, 4)->default(0);
            $table->decimal('equipment_markup_percent', 6, 4)->default(0);
            $table->decimal('subcontractor_markup_percent', 6, 4)->default(0);
            $table->decimal('other_markup_percent', 6, 4)->default(0);

            $table->timestamps();

            $table->unique(['client_id', 'cost_type_id'], 'client_default_markups_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_default_markups');
        Schema::dropIfExists('client_default_billable_rates');
    }
};
