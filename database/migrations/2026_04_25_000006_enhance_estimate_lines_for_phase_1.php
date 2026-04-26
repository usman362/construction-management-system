<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estimating Phase 1 — type-aware estimate lines.
 *
 * Original `estimate_lines` was generic: cost_code + qty + unit_cost.
 * That can't represent a labor line (where you want craft + hours +
 * loaded billable rate) cleanly, and it doesn't track markup or final
 * billable price separately from cost.
 *
 * This migration adds:
 *   - line_type        labor | material | equipment | subcontractor | other
 *   - craft_id, hours, hourly_cost_rate, hourly_billable_rate (labor lines)
 *   - material_id, equipment_id (catalog references)
 *   - cost_amount, markup_percent, markup_amount, price_amount (pricing)
 *   - section_id, sort_order (sections + ordering)
 *   - notes
 *
 * The legacy `unit_cost`, `quantity`, `amount`, `labor_hours` columns are
 * preserved so old estimates keep rendering. New code uses the new fields.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->string('line_type', 24)->default('other')->after('cost_type_id');
            $table->foreignId('section_id')->nullable()->after('line_type')
                ->constrained('estimate_sections')->nullOnDelete();
            $table->integer('sort_order')->default(0)->after('section_id');

            // Labor specifics
            $table->foreignId('craft_id')->nullable()->after('sort_order')
                ->constrained('crafts')->nullOnDelete();
            $table->decimal('hours', 10, 2)->nullable()->after('craft_id');
            $table->decimal('hourly_cost_rate', 10, 2)->nullable()->after('hours');
            $table->decimal('hourly_billable_rate', 10, 2)->nullable()->after('hourly_cost_rate');

            // Material / Equipment catalog references
            $table->foreignId('material_id')->nullable()->after('hourly_billable_rate')
                ->constrained('materials')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->after('material_id')
                ->constrained('equipment')->nullOnDelete();

            // Pricing pipeline — cost is what we pay, price is what we charge.
            // Markup % is multiplicative: price_amount = cost_amount * (1 + markup_percent).
            $table->decimal('cost_amount', 15, 2)->default(0)->after('equipment_id');
            $table->decimal('markup_percent', 6, 4)->default(0)->after('cost_amount');
            $table->decimal('markup_amount', 15, 2)->default(0)->after('markup_percent');
            $table->decimal('price_amount', 15, 2)->default(0)->after('markup_amount');

            $table->text('notes')->nullable()->after('price_amount');

            $table->index(['estimate_id', 'section_id', 'sort_order'], 'estimate_lines_grouping_idx');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropIndex('estimate_lines_grouping_idx');
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('craft_id');
            $table->dropConstrainedForeignId('material_id');
            $table->dropConstrainedForeignId('equipment_id');
            $table->dropColumn([
                'line_type', 'sort_order',
                'hours', 'hourly_cost_rate', 'hourly_billable_rate',
                'cost_amount', 'markup_percent', 'markup_amount', 'price_amount',
                'notes',
            ]);
        });
    }
};
