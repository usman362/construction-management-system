<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-23 (KH WBS sheet): each estimate line needs separate
 * Quote / Freight / Tax columns so the cost build-up matches her
 * spreadsheet exactly. Cost ($) = Quote + Freight + Tax (vs the
 * existing unit_cost field which subsumed all three into one number).
 *
 * Both paths kept live:
 *   - Legacy lines on unit_cost × quantity continue to recalculate
 *     the same way.
 *   - New lines with quote/freight/tax populated → Cost = sum of the
 *     three; quantity stays at 1; unit_cost mirrors Cost for legacy
 *     report compatibility (handled in EstimateLine::recalculate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->decimal('quote_amount',   12, 2)->nullable()->after('unit_cost');
            $table->decimal('freight_amount', 12, 2)->nullable()->after('quote_amount');
            $table->decimal('tax_amount',     12, 2)->nullable()->after('freight_amount');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropColumn(['quote_amount', 'freight_amount', 'tax_amount']);
        });
    }
};
