<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill legacy estimate lines (Brenda 2026-04-30):
 *
 *   "The estimate page is showing all zero's also"
 *
 * Pre-Phase-1 estimates were created with line rows that only filled the
 * legacy `amount` column. The new typed-line builder added `cost_amount`,
 * `markup_amount`, and `price_amount` columns and the totals UI reads from
 * those — so any old estimate where lines have `amount > 0` but
 * `cost_amount = 0` ends up showing $0.00 totals.
 *
 * Strategy:
 *   1) For every estimate_lines row where cost_amount is 0/NULL but
 *      amount > 0, copy `amount` into both cost_amount and price_amount.
 *      Legacy rows had no separate markup so we leave markup_amount = 0
 *      and let the price equal the cost (no margin) — the office can
 *      adjust if needed.
 *   2) After the column backfill, walk every estimate and call
 *      recalculateTotals() on the model so total_cost / total_price /
 *      margin_percent get rolled up correctly.
 *
 * Reversible-ish: the down() doesn't try to zero things back out because
 * doing so would lose user edits made between this migration and a
 * rollback. Cost_amount/price_amount being populated is desired state.
 */
return new class extends Migration {
    public function up(): void
    {
        // Step 1: backfill the columns
        $updated = DB::table('estimate_lines')
            ->where(function ($q) {
                $q->whereNull('cost_amount')->orWhere('cost_amount', 0);
            })
            ->where('amount', '>', 0)
            ->update([
                'cost_amount'  => DB::raw('amount'),
                'price_amount' => DB::raw('amount'),
                // markup stays at 0 — legacy lines had no markup concept
            ]);

        echo "  Backfilled {$updated} legacy estimate lines.\n";

        // Step 2: roll up totals on every affected estimate
        $estimateIds = DB::table('estimate_lines')
            ->select('estimate_id')
            ->distinct()
            ->whereNotNull('estimate_id')
            ->pluck('estimate_id');

        $rolledUp = 0;
        foreach ($estimateIds as $id) {
            $est = \App\Models\Estimate::find($id);
            if ($est) {
                $est->recalculateTotals();
                $rolledUp++;
            }
        }

        echo "  Recalculated totals on {$rolledUp} estimate(s).\n";
    }

    public function down(): void
    {
        // No-op: backfilled values represent desired state; rolling back
        // would zero out user-visible totals on legacy estimates which is
        // strictly worse than leaving them populated.
    }
};
