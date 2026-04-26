<?php

namespace App\Observers;

use App\Models\EstimateLine;

/**
 * Keeps line-level cost / markup / price in sync with whatever the user typed
 * (hours, qty, unit cost, markup %), and bubbles the change up to the parent
 * section + estimate so totals always match the data.
 *
 * Logic:
 *   - saving:  recompute this line's cost / markup / price columns
 *   - saved:   roll up this line's section subtotals + estimate grand totals
 *   - deleted: same roll-up so removing a line shrinks the totals
 *
 * Both saved() and deleted() guard against infinite recursion via
 * saveQuietly() in the Section/Estimate `recalculateTotals()` methods.
 */
class EstimateLineObserver
{
    public function saving(EstimateLine $line): void
    {
        $line->recalculate();
    }

    public function saved(EstimateLine $line): void
    {
        $this->rollUp($line);
    }

    public function deleted(EstimateLine $line): void
    {
        $this->rollUp($line);
    }

    private function rollUp(EstimateLine $line): void
    {
        // Roll up the parent section first (if any), then the estimate.
        if ($line->section_id) {
            $section = $line->section()->first();
            $section?->recalculateTotals();
        }
        $estimate = $line->estimate()->first();
        $estimate?->recalculateTotals();
    }
}
