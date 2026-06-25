<?php

namespace App\Console\Commands;

use App\Models\Craft;
use App\Models\EstimateLine;
use App\Models\ProjectBillableRate;
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
    protected $signature = 'estimates:backfill-labor-cost-rates {--dry-run : Show what would change without writing} {--force : Also re-lookup lines that already have a cost rate (use after burden formula change)}';
    protected $description = 'Fill in hourly_cost_rate on labor estimate lines that have a craft but no cost rate (so margin is computed correctly)';

    public function handle(): int
    {
        $dry   = $this->option('dry-run');
        $force = $this->option('force');
        $q = EstimateLine::where('line_type', 'labor')
            ->whereNotNull('craft_id')
            ->with('estimate:id,project_id');
        if (!$force) {
            $q->where(function ($w) {
                $w->whereNull('hourly_cost_rate')->orWhere('hourly_cost_rate', 0);
            });
        }
        $rows = $q->get();

        $this->info("Found {$rows->count()} labor lines missing cost rates.");
        $patched = 0; $skippedNoRate = []; $skippedNoCraft = 0;

        foreach ($rows as $line) {
            $craft = Craft::find($line->craft_id);
            if (!$craft) { $skippedNoCraft++; continue; }

            // 2026-06-19 (Brenda — second pass): check ProjectBillableRate first
            // since cost rates often live there per-project (Setup tab → Base ST),
            // not on the global craft master.
            $projectId = $line->estimate?->project_id;
            $stCost = 0; $otCost = 0;

            if ($projectId) {
                $pbr = ProjectBillableRate::where('project_id', $projectId)
                    ->where('craft_id', $line->craft_id)
                    ->whereNull('employee_id')
                    ->orderByDesc('effective_date')
                    ->first();
                if ($pbr) {
                    // 2026-06-20 (Brenda): burden-loaded (base + FICA + SUTA + WC),
                    // not bare wage. Matches her Excel SOV cost column.
                    $stCost = $pbr->loadedCostRate();
                    $otCost = $pbr->loadedOtCostRate();
                }
            }
            // Fall back to craft master if PBR didn't cover it.
            if ($stCost <= 0 && $craft->base_hourly_rate) $stCost = (float) $craft->base_hourly_rate;
            if ($otCost <= 0) {
                $otMult = $craft->overtime_multiplier ?? 1.5;
                if ($craft->base_ot_hourly_rate)      $otCost = (float) $craft->base_ot_hourly_rate;
                elseif ($stCost > 0)                  $otCost = $stCost * $otMult;
            }

            if ($stCost <= 0) {
                $skippedNoRate[] = "  est {$line->estimate_id} · {$craft->name} (craft #{$craft->id})";
                continue;
            }

            $this->line("  Line #{$line->id} (est {$line->estimate_id}, {$craft->name}): ST cost \$" . number_format($stCost, 2) . ", OT cost \$" . number_format($otCost, 2));

            if (!$dry) {
                $line->hourly_cost_rate = $stCost;
                if ($force || empty($line->ot_hourly_cost_rate)) $line->ot_hourly_cost_rate = $otCost;
                $line->save();  // triggers observer → recalculate → roll-up
            }
            $patched++;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "Patched {$patched} lines.");
        if (count($skippedNoRate) > 0) {
            $this->warn(count($skippedNoRate) . " line(s) skipped — no cost rate found on craft master OR project billable rates:");
            foreach (array_unique($skippedNoRate) as $msg) $this->line($msg);
            $this->newLine();
            $this->warn("Fix: open the project → Setup → Billable Rates and enter the \"Base ST\" rate (cost) for each craft, then re-run this command.");
        }
        if ($skippedNoCraft > 0) $this->warn("$skippedNoCraft line(s) skipped (craft no longer exists).");

        return self::SUCCESS;
    }
}
