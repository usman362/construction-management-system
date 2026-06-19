<?php

namespace App\Console\Commands;

use App\Models\Craft;
use App\Models\EstimateLine;
use Illuminate\Console\Command;

/**
 * Brenda 2026-06-19: T&M builder labor lines had billable rates but no cost
 * rates, so estimate margins looked artificially high (60% phantom margin).
 * The bug is fixed forward, but existing labor lines need a one-off backfill.
 *
 *   php artisan estimates:backfill-labor-cost-rates
 */
class BackfillEstimateLaborCostRates extends Command
{
    protected $signature = 'estimates:backfill-labor-cost-rates {--dry-run : Show what would change without writing}';
    protected $description = 'Fill in hourly_cost_rate on labor estimate lines that have a craft but no cost rate (so margin is computed correctly)';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $rows = EstimateLine::where('line_type', 'labor')
            ->whereNotNull('craft_id')
            ->where(function ($q) {
                $q->whereNull('hourly_cost_rate')->orWhere('hourly_cost_rate', 0);
            })
            ->get();

        $this->info("Found {$rows->count()} labor lines missing cost rates.");
        $patched = 0; $skipped = 0;

        foreach ($rows as $line) {
            $craft = Craft::find($line->craft_id);
            if (!$craft || !$craft->base_hourly_rate) { $skipped++; continue; }

            $otMult = $craft->overtime_multiplier ?? 1.5;
            $otBase = $craft->base_ot_hourly_rate ?? ($craft->base_hourly_rate * $otMult);

            $this->line("  Line #{$line->id} (est {$line->estimate_id}, {$craft->name}): ST cost \${$craft->base_hourly_rate}, OT cost \${$otBase}");

            if (!$dry) {
                $line->hourly_cost_rate    = $craft->base_hourly_rate;
                if (empty($line->ot_hourly_cost_rate)) $line->ot_hourly_cost_rate = $otBase;
                $line->save();  // triggers observer → recalculate → roll-up
            }
            $patched++;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "Patched {$patched} lines, skipped {$skipped} (no craft/no rate).");
        return self::SUCCESS;
    }
}
