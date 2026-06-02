<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EstimateLine;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use App\Models\Timesheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recompute snapshot rates on a project's existing labor lines + timesheets
 * against the CURRENT project_billable_rates table.
 *
 * 2026-05-31 (Brenda): "Yes, please add a recompute rates for Admin only."
 *
 * Background: when a labor estimate line or a timesheet is created, the
 * system captures the rate at that moment and stores it on the row itself
 * (hourly_cost_rate / hourly_billable_rate / regular_rate / overtime_rate
 * / billable_rate). That snapshot is deliberate — rates revised later
 * shouldn't silently rewrite history. But sometimes (and this is Brenda's
 * case) the admin loaded the wrong rates first, fixed them in the rate
 * sheet, and now wants the historical entries to pick up the corrected
 * numbers. This action does that in one shot.
 *
 * Admin-only. Gated by User::isAdmin() in the route.
 */
class ProjectRecomputeRatesController extends Controller
{
    public function __invoke(Request $request, Project $project): RedirectResponse
    {
        if (! optional($request->user())->isAdmin()) {
            abort(403, 'Recompute Rates is admin-only.');
        }

        $stats = [
            'estimate_lines_touched' => 0,
            'estimate_lines_unchanged' => 0,
            'timesheets_touched' => 0,
            'timesheets_unchanged' => 0,
            'estimate_lines_skipped_no_pbr' => 0,
            'timesheets_skipped_no_pbr' => 0,
        ];

        DB::transaction(function () use ($project, &$stats) {
            $this->recomputeEstimateLines($project, $stats);
            $this->recomputeTimesheets($project, $stats);
        });

        $msg = sprintf(
            'Rates recomputed. Estimate labor lines: %d updated, %d unchanged, %d skipped (no rate). Timesheets: %d updated, %d unchanged, %d skipped (no rate).',
            $stats['estimate_lines_touched'],
            $stats['estimate_lines_unchanged'],
            $stats['estimate_lines_skipped_no_pbr'],
            $stats['timesheets_touched'],
            $stats['timesheets_unchanged'],
            $stats['timesheets_skipped_no_pbr'],
        );

        Log::info('Project rates recomputed', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'by_user_id' => $request->user()->id,
            'stats' => $stats,
        ]);

        return back()->with('success', $msg);
    }

    /**
     * Walk every labor line on every estimate (including CO-linked estimates)
     * for this project and rewrite its hourly_cost_rate / hourly_billable_rate
     * (and OT companions) from the matching ProjectBillableRate. Then call
     * recalculate() so cost_amount / price_amount fall in line.
     */
    private function recomputeEstimateLines(Project $project, array &$stats): void
    {
        $lines = EstimateLine::query()
            ->where('line_type', 'labor')
            ->whereHas('estimate', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->get();

        foreach ($lines as $line) {
            if (! $line->craft_id) {
                $stats['estimate_lines_skipped_no_pbr']++;
                continue;
            }

            // Match the lookup order EstimateController uses when adding
            // a labor bundle: project × craft, employee NULL (craft-level
            // override), latest effective date wins.
            $pbr = ProjectBillableRate::query()
                ->where('project_id', $project->id)
                ->where('craft_id', $line->craft_id)
                ->whereNull('employee_id')
                ->orderByDesc('effective_date')
                ->first();

            if (! $pbr) {
                $stats['estimate_lines_skipped_no_pbr']++;
                continue;
            }

            $craft  = $line->craft;
            $otMult = (float) ($craft?->overtime_multiplier ?? 1.5);

            $newStCost = (float) ($pbr->base_hourly_rate ?? 0);
            $newOtCost = (float) ($pbr->base_ot_hourly_rate ?: $newStCost * $otMult);
            $newStBill = (float) ($pbr->straight_time_rate ?? 0);
            $newOtBill = (float) ($pbr->overtime_rate ?: $newStBill * $otMult);

            $oldStCost = (float) $line->hourly_cost_rate;
            $oldOtCost = (float) $line->ot_hourly_cost_rate;
            $oldStBill = (float) $line->hourly_billable_rate;
            $oldOtBill = (float) $line->ot_hourly_billable_rate;

            $changed = abs($newStCost - $oldStCost) > 0.001
                || abs($newOtCost - $oldOtCost) > 0.001
                || abs($newStBill - $oldStBill) > 0.001
                || abs($newOtBill - $oldOtBill) > 0.001;

            if (! $changed) {
                $stats['estimate_lines_unchanged']++;
                continue;
            }

            $line->hourly_cost_rate        = $newStCost;
            $line->ot_hourly_cost_rate     = $newOtCost;
            $line->hourly_billable_rate    = $newStBill;
            $line->ot_hourly_billable_rate = $newOtBill;
            $line->recalculate();
            $line->save();

            $stats['estimate_lines_touched']++;
        }
    }

    /**
     * Walk every non-rejected timesheet on this project and rewrite the
     * snapshot rates from the current PBR + employee rates. Mirrors
     * TimesheetController::computeLaborTotals (the canonical math).
     */
    private function recomputeTimesheets(Project $project, array &$stats): void
    {
        $timesheets = Timesheet::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', 'rejected')
            ->with('employee')
            ->get();

        foreach ($timesheets as $t) {
            $employee = $t->employee;
            if (! $employee) {
                $stats['timesheets_skipped_no_pbr']++;
                continue;
            }

            $date = optional($t->date)->toDateString() ?? now()->toDateString();

            // Re-run the same lookup TimesheetController uses for live entry.
            $pbr = $this->findPbr($project->id, $employee, $date);

            // ST/OT cost — wage + burden from employee (matches computeLaborTotals).
            $stWage   = (float) ($employee->hourly_rate ?? 0);
            $otWage   = (float) ($employee->overtime_rate ?? ($stWage * 1.5));
            $stBurden = (float) ($employee->st_burden_rate ?? 0);
            $otBurden = (float) ($employee->ot_burden_rate ?? 0);

            $reg = (float) ($t->regular_hours ?? 0);
            $ot  = (float) ($t->overtime_hours ?? 0);
            $dt  = (float) ($t->double_time_hours ?? 0);

            $newTotalCost = ($reg * ($stWage + $stBurden))
                + ($ot * ($otWage + $otBurden))
                + ($dt * (($stWage * 2) + $otBurden));

            // Billable: PBR rate × hours if available, else employee
            // billable_rate × multipliers.
            if ($pbr) {
                $newStBill = (float) $pbr->straight_time_rate;
                $newOtBill = (float) $pbr->overtime_rate;
                $newDtBill = (float) $pbr->double_time_rate;
                $newBillable = ($reg * $newStBill) + ($ot * $newOtBill) + ($dt * $newDtBill);
                $newPbrId    = $pbr->id;
            } else {
                $bRate = (float) ($employee->billable_rate ?? $stWage);
                $newStBill = $bRate;
                $newOtBill = $bRate * 1.5;
                $newBillable = ($reg * $bRate) + ($ot * $bRate * 1.5) + ($dt * $bRate * 2);
                $newPbrId    = null;
                $stats['timesheets_skipped_no_pbr']++;
            }

            $changed = abs($newTotalCost - (float) $t->total_cost) > 0.001
                || abs($newBillable - (float) $t->billable_amount) > 0.001
                || abs($newStBill - (float) $t->billable_rate) > 0.001;

            if (! $changed) {
                $stats['timesheets_unchanged']++;
                continue;
            }

            $t->regular_rate             = $stWage;
            $t->overtime_rate            = $otWage;
            $t->total_cost               = $newTotalCost;
            $t->billable_rate            = $newStBill;
            $t->billable_amount          = $newBillable;
            $t->project_billable_rate_id = $newPbrId;
            $t->save();

            $stats['timesheets_touched']++;
        }
    }

    private function findPbr(int $projectId, Employee $employee, string $date): ?ProjectBillableRate
    {
        // Employee-specific overrides win over craft rates.
        $rate = ProjectBillableRate::forProject($projectId)
            ->forEmployee($employee->id)
            ->effectiveOn($date)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate) {
            return $rate;
        }

        if ($employee->craft_id) {
            return ProjectBillableRate::forProject($projectId)
                ->forCraft($employee->craft_id)
                ->whereNull('employee_id')
                ->effectiveOn($date)
                ->orderByDesc('effective_date')
                ->first();
        }

        return null;
    }
}
