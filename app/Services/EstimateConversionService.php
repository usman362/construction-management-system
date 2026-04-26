<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\ClientDefaultBillableRate;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use Illuminate\Support\Facades\DB;

/**
 * Estimating Phase 2 — accept an estimate and turn it into a real project.
 *
 * What happens:
 *   1. Update the estimate's status → 'converted_to_project'
 *   2. Either create a new Project (if the estimate isn't tied to one yet) or
 *      reuse the existing project the estimate already lives under
 *   3. Copy every estimate line that has a cost_code into a BudgetLine row
 *      (cost_amount → budget_amount, price_amount → revised_amount). Lines
 *      without a cost code are skipped — they can't be tracked as budget.
 *   4. Copy every ClientDefaultBillableRate for the estimate's client into
 *      ProjectBillableRate rows on the new project. Skips rows that would
 *      collide with the project_billable_rates unique constraint.
 *   5. Stamp the estimate.converted_to_project_id so the audit trail links
 *      the bid back to the project it became.
 *
 * Wrapped in a single DB transaction so half-converted state is impossible.
 */
class EstimateConversionService
{
    /**
     * Convert and return the project that ended up holding the budget.
     */
    public function convert(Estimate $estimate, ?array $newProjectData = null): Project
    {
        return DB::transaction(function () use ($estimate, $newProjectData) {
            // 1) Resolve target project — reuse if attached, else create.
            $project = $estimate->project_id
                ? $estimate->project()->first()
                : $this->createProjectFromEstimate($estimate, $newProjectData ?? []);

            // 2) Wipe any existing budget lines on the project to avoid double-
            //    counting if the user re-converts after editing the estimate.
            //    Only do this when re-converting; first-time conversion is a no-op
            //    since fresh projects have no budget lines.
            if ($estimate->converted_to_project_id === $project->id) {
                BudgetLine::where('project_id', $project->id)
                    ->where('description', 'like', '[Estimate] %')
                    ->delete();
            }

            // 3) Copy estimate lines → budget lines. Group by (cost_code, cost_type)
            //    so a 50-line estimate doesn't produce 50 budget rows — instead the
            //    PM sees one row per code/type with summed amounts and hours.
            $estimate->loadMissing('lines.costCode', 'lines.costType');
            $linesByBucket = $estimate->lines
                ->filter(fn ($l) => $l->cost_code_id)   // skip uncoded lines
                ->groupBy(fn ($l) => $l->cost_code_id . '|' . ($l->cost_type_id ?? 'x'));

            $budgetLineCount = 0;
            foreach ($linesByBucket as $bucket) {
                $first = $bucket->first();
                $cost  = (float) $bucket->sum('cost_amount');
                $price = (float) $bucket->sum('price_amount');
                $hours = (float) $bucket->sum(fn ($l) => (float) ($l->hours ?? $l->labor_hours ?? 0));

                BudgetLine::create([
                    'project_id'    => $project->id,
                    'cost_code_id'  => $first->cost_code_id,
                    'cost_type_id'  => $first->cost_type_id,
                    'description'   => '[Estimate] ' . ($first->costCode->name ?? 'Imported from estimate'),
                    'budget_amount' => $cost,
                    // revised_amount holds the priced/billable side so the cost
                    // report can compare what we'll bill vs. what we expect to spend.
                    'revised_amount' => $price,
                    'labor_hours'   => $hours,
                ]);
                $budgetLineCount++;
            }

            // 4) Copy client default billable rates onto this project.
            $rateCount = 0;
            if ($estimate->client_id) {
                $defaultRates = ClientDefaultBillableRate::where('client_id', $estimate->client_id)->get();
                foreach ($defaultRates as $defRate) {
                    // Skip if a rate already exists for this (project, craft, employee, effective_date)
                    // — happens on a re-convert.
                    $exists = ProjectBillableRate::where('project_id', $project->id)
                        ->where('craft_id', $defRate->craft_id)
                        ->where('employee_id', $defRate->employee_id)
                        ->where('effective_date', $defRate->effective_date)
                        ->exists();
                    if ($exists) continue;

                    ProjectBillableRate::create([
                        'project_id'     => $project->id,
                        'craft_id'       => $defRate->craft_id,
                        'employee_id'    => $defRate->employee_id,
                        'base_hourly_rate'    => $defRate->base_hourly_rate,
                        'base_ot_hourly_rate' => $defRate->base_ot_hourly_rate,

                        'payroll_tax_rate'    => $defRate->payroll_tax_rate,
                        'burden_rate'         => $defRate->burden_rate,
                        'insurance_rate'      => $defRate->insurance_rate,
                        'job_expenses_rate'   => $defRate->job_expenses_rate,
                        'consumables_rate'    => $defRate->consumables_rate,
                        'overhead_rate'       => $defRate->overhead_rate,
                        'profit_rate'         => $defRate->profit_rate,

                        'payroll_tax_ot_rate'   => $defRate->payroll_tax_ot_rate,
                        'burden_ot_rate'        => $defRate->burden_ot_rate,
                        'insurance_ot_rate'     => $defRate->insurance_ot_rate,
                        'job_expenses_ot_rate'  => $defRate->job_expenses_ot_rate,
                        'consumables_ot_rate'   => $defRate->consumables_ot_rate,
                        'overhead_ot_rate'      => $defRate->overhead_ot_rate,
                        'profit_ot_rate'        => $defRate->profit_ot_rate,

                        'effective_date' => $defRate->effective_date ?? $project->start_date ?? now(),
                        'notes'          => 'Copied from client default at estimate acceptance.',
                    ]);
                    $rateCount++;
                }
            }

            // 5) Stamp the estimate's lifecycle fields.
            $estimate->update([
                'status'                  => 'converted_to_project',
                'client_response_date'    => $estimate->client_response_date ?? now(),
                'converted_to_project_id' => $project->id,
                // Also adopt the project_id so subsequent edits stay in sync.
                'project_id'              => $project->id,
            ]);

            // Stash counts so the controller can show "created N budget lines, M billable rates".
            $project->_conversion_summary = [
                'budget_lines_created' => $budgetLineCount,
                'billable_rates_copied' => $rateCount,
            ];

            return $project;
        });
    }

    /**
     * Build a Project from the estimate when no existing project is attached.
     * Caller can override fields via $overrides.
     */
    private function createProjectFromEstimate(Estimate $estimate, array $overrides = []): Project
    {
        // Generate a project number from the estimate number if possible
        // (EST-0001 → P-EST-0001) so the PM can recognize the lineage.
        $projNumber = $overrides['project_number']
            ?? 'P-' . ($estimate->estimate_number ?? ('EST-' . $estimate->id));

        return Project::create(array_merge([
            'project_number'  => $projNumber,
            'name'            => $estimate->name ?: 'Project from estimate ' . $estimate->id,
            'client_id'       => $estimate->client_id,
            'status'          => 'awarded',
            'start_date'      => $estimate->start_date ?? now()->toDateString(),
            'end_date'        => $estimate->end_date ?? now()->addMonths(3)->toDateString(),
            'original_budget' => $estimate->total_cost,
            'current_budget'  => $estimate->total_cost,
            'estimate'        => $estimate->total_price,
            'contract_value'  => $estimate->total_price,
            'description'     => $estimate->description,
        ], $overrides));
    }
}
