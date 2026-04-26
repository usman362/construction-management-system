<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsToExcel;
use App\Models\BillingInvoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Work-in-Progress (WIP) Report — required by banks, sureties, and most
 * owner-side accountants. Shows for every active project:
 *
 *   - Contract value (what we'll be paid)
 *   - Costs to date (committed vendor + labor + invoices)
 *   - % complete (cost-to-date / total estimated cost)
 *   - Earned revenue (% complete × contract value)
 *   - Billed to date (sum of billing invoices)
 *   - Over/under billed (earned − billed)
 *
 * "Over billed" means we billed faster than we earned (cash advance).
 * "Under billed" means we earned more than we billed (revenue at risk).
 * Both metrics are CRITICAL on a WIP schedule.
 */
class WipReportController extends Controller
{
    use ExportsToExcel;

    public function index(Request $request): View|BinaryFileResponse
    {
        $rows = $this->buildRows();

        if ($request->boolean('export')) {
            return $this->exportExcel($rows);
        }

        // Roll up totals for the summary strip at the top of the page.
        $totals = [
            'contract_value' => $rows->sum('contract_value'),
            'cost_to_date'   => $rows->sum('cost_to_date'),
            'earned_revenue' => $rows->sum('earned_revenue'),
            'billed_to_date' => $rows->sum('billed_to_date'),
            'over_billed'    => $rows->where('over_under', '>', 0)->sum('over_under'),
            'under_billed'   => abs($rows->where('over_under', '<', 0)->sum('over_under')),
        ];

        return view('reports.wip', [
            'rows'        => $rows,
            'totals'      => $totals,
            'generatedAt' => now(),
        ]);
    }

    /**
     * Build one row per active project. Returns a Collection of stdClass.
     */
    private function buildRows(): \Illuminate\Support\Collection
    {
        // Eager-load EVERY relation we touch in the loop below — without
        // billingInvoices preloaded, each project triggers a separate SUM query
        // (39 projects → 40+ queries). This pulls the count down to ~5 fixed
        // queries regardless of how many projects exist.
        $projects = Project::with([
                'client:id,name',
                'commitments',
                'invoices',
                'timesheets' => fn ($q) => $q->where('status', '!=', 'rejected'),
                'billingInvoices' => fn ($q) => $q->whereNotIn('status', ['draft', 'voided']),
            ])
            ->whereNotIn('status', ['closed'])     // active + bidding + completed (post-close)
            ->orderBy('project_number')
            ->get();

        return $projects->map(function ($p) {
            $contract  = (float) ($p->contract_value ?: $p->estimate ?: 0);
            $vendorCost = (float) $p->commitments->sum('amount')
                        + (float) $p->invoices->sum('amount');
            $laborCost  = (float) $p->timesheets->sum('total_cost');
            $costToDate = round($vendorCost + $laborCost, 2);

            // Cost-based percent complete; falls back to % committed against
            // the original budget when contract_value is 0 (early-stage projects).
            $estimatedCost = (float) ($p->original_budget ?: $p->current_budget ?: 0);
            $pctComplete = $estimatedCost > 0
                ? min(round(($costToDate / $estimatedCost) * 100, 1), 100)
                : 0;
            $earnedRevenue = round($contract * ($pctComplete / 100), 2);

            // Use the eager-loaded collection — no extra DB hit per project.
            $billedToDate = (float) $p->billingInvoices->sum('total_amount');

            // Positive = over-billed (billed more than earned)
            // Negative = under-billed
            $overUnder = round($billedToDate - $earnedRevenue, 2);

            return (object) [
                'project_id'      => $p->id,
                'project_number'  => $p->project_number,
                'name'            => $p->name,
                'client'          => $p->client?->name ?? '—',
                'status'          => $p->status,
                'contract_value'  => $contract,
                'estimated_cost'  => $estimatedCost,
                'cost_to_date'    => $costToDate,
                'percent_complete'=> $pctComplete,
                'earned_revenue'  => $earnedRevenue,
                'billed_to_date'  => $billedToDate,
                'over_under'      => $overUnder,
                // gross profit projection: contract − estimated total cost
                'projected_profit'=> round($contract - $estimatedCost, 2),
            ];
        });
    }

    /**
     * Excel download — same shape as the on-screen table.
     */
    private function exportExcel(\Illuminate\Support\Collection $rows): BinaryFileResponse
    {
        return $this->streamExcel(
            filename:  'wip-report-' . now()->format('Y-m-d') . '.xlsx',
            sheetName: 'WIP',
            rows:      $rows,
            columns: [
                ['header' => 'Project #',     'value' => 'project_number',                  'width' => 14],
                ['header' => 'Name',          'value' => 'name',                            'width' => 32],
                ['header' => 'Client',        'value' => 'client',                          'width' => 22],
                ['header' => 'Status',        'value' => fn ($r) => ucfirst($r->status ?? '—'), 'width' => 12],
                ['header' => 'Contract',      'value' => 'contract_value',  'format' => 'currency', 'width' => 16],
                ['header' => 'Est. Cost',     'value' => 'estimated_cost',  'format' => 'currency', 'width' => 16],
                ['header' => 'Cost to Date',  'value' => 'cost_to_date',    'format' => 'currency', 'width' => 16],
                ['header' => '% Complete',    'value' => fn ($r) => $r->percent_complete / 100, 'format' => 'percent', 'width' => 11],
                ['header' => 'Earned',        'value' => 'earned_revenue',  'format' => 'currency', 'width' => 16],
                ['header' => 'Billed',        'value' => 'billed_to_date',  'format' => 'currency', 'width' => 16],
                ['header' => 'Over/Under',    'value' => 'over_under',      'format' => 'currency', 'width' => 16],
                ['header' => 'Proj. Profit',  'value' => 'projected_profit','format' => 'currency', 'width' => 16],
            ],
            title: 'Work-in-Progress Report — ' . now()->format('M j, Y'),
        );
    }
}
