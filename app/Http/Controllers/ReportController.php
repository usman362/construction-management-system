<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\Commitment;
use App\Models\Invoice;
use App\Models\ChangeOrder;
use App\Models\Timesheet;
use App\Models\Employee;
use App\Models\ManhourBudget;
use App\Models\BillingInvoice;
use App\Models\CostCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function costReport(Request $request, Project $project): View
    {
        $project->load('client');
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with('costCode')->get();
        $commitmentsByCostCode = $project->commitments()->get()->groupBy('cost_code_id');
        $invoicesByCostCode = $project->invoices()->get()->groupBy('cost_code_id');

        $costCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            $ccId = $line->cost_code_id;

            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'budget' => 0,
                    'committed' => 0,
                    'invoiced' => 0,
                    'balance' => 0,
                    'percentage_complete' => 0,
                ];
            }

            $committed = ($commitmentsByCostCode[$ccId] ?? collect())->sum('amount');
            $invoiced = ($invoicesByCostCode[$ccId] ?? collect())->sum('amount');
            $budget = $line->amount;

            $costCodeData[$code]['budget'] += $budget;
            $costCodeData[$code]['committed'] += $committed;
            $costCodeData[$code]['invoiced'] += $invoiced;
            $costCodeData[$code]['balance'] += $budget - $committed;
            $costCodeData[$code]['percentage_complete'] = $costCodeData[$code]['budget'] > 0
                ? round(($costCodeData[$code]['committed'] / $costCodeData[$code]['budget']) * 100, 2)
                : 0;
        }

        $changeOrders = $project->changeOrders()
            ->where('status', 'approved')
            ->get();

        $manhourData = $this->getManHourData($project, $validated);

        // Composite (blended) labor rate = total labor cost / total actual hours.
        $totalLaborCost = array_sum(array_column($manhourData, 'labor_cost'));
        $totalLaborHours = array_sum(array_column($manhourData, 'actual_hours'));
        $compositeRate = $totalLaborHours > 0 ? round($totalLaborCost / $totalLaborHours, 2) : 0;

        return view('reports.cost-report', [
            'project' => $project,
            'costData' => collect($costCodeData)->values(),
            'costCodeData' => $costCodeData,
            'changeOrders' => $changeOrders,
            'manhourData' => $manhourData,
            'compositeRate' => $compositeRate,
            'totalLaborCost' => $totalLaborCost,
            'totalLaborHours' => $totalLaborHours,
            'export' => $request->get('export'),
        ]);
    }

    public function forecast(Request $request, Project $project): View
    {
        $project->load('client');
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with('costCode')->get();
        $commitmentsByCostCode = $project->commitments()->get()->groupBy('cost_code_id');
        $invoicesByCostCode = $project->invoices()->get()->groupBy('cost_code_id');

        $originalBudgetTotal = $budgetLines->sum('amount');
        $approvedCoTotal = $project->changeOrders()
            ->where('status', 'approved')
            ->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        $costCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            $ccId = $line->cost_code_id;

            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'original_budget' => 0,
                    'forecast_budget' => 0,
                    'committed' => 0,
                    'invoiced' => 0,
                    'balance' => 0,
                ];
            }

            $committed = ($commitmentsByCostCode[$ccId] ?? collect())->sum('amount');
            $invoiced = ($invoicesByCostCode[$ccId] ?? collect())->sum('amount');

            $costCodeData[$code]['original_budget'] += $line->amount;
            $costCodeData[$code]['forecast_budget'] += $line->amount;
            $costCodeData[$code]['committed'] += $committed;
            $costCodeData[$code]['invoiced'] += $invoiced;
            $costCodeData[$code]['balance'] += $line->amount - $committed;
        }

        $manhourData = $this->getManHourForecastData($project, $validated);
        $changeOrders = $project->changeOrders()->where('status', 'approved')->get();

        return view('reports.forecast', [
            'project' => $project,
            'costCodeData' => $costCodeData,
            'costData' => collect($costCodeData)->values(),
            'forecastData' => collect($costCodeData)->values(),
            'originalBudgetTotal' => $originalBudgetTotal,
            'forecastBudgetTotal' => $forecastBudgetTotal,
            'originalBudgetTotals' => ['budget' => $originalBudgetTotal],
            'forecastTotals' => ['budget' => $forecastBudgetTotal],
            'changeOrders' => $changeOrders,
            'manhourData' => $manhourData,
            'export' => $request->get('export'),
        ]);
    }

    public function manhourReport(Request $request, Project $project): View
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:employee,craft,cost_code',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
        ]);

        $payload = $this->buildManhourReportPayload($project, $validated);

        return view('reports.manhours', [
            'project' => $project,
            'manhourData' => $payload['manhourData'],
            'groupBy' => $payload['groupBy'],
            'costCodesForFilter' => $payload['costCodesForFilter'],
            'export' => $request->get('export'),
        ]);
    }

    /**
     * @return array{manhourData: array<int, array<string, mixed>>, groupBy: string, costCodesForFilter: \Illuminate\Support\Collection<int, CostCode>}
     */
    protected function buildManhourReportPayload(Project $project, array $validated): array
    {
        $dateFrom = $validated['start_date'] ?? $validated['date_from'] ?? null;
        $dateTo = $validated['end_date'] ?? $validated['date_to'] ?? null;
        $groupBy = $validated['group_by'] ?? 'employee';

        $query = Timesheet::where('project_id', $project->id)
            ->whereIn('status', ['submitted', 'approved'])
            ->with(['employee.craft', 'costCode']);

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if (! empty($validated['cost_code_id'])) {
            $query->where('cost_code_id', $validated['cost_code_id']);
        }

        $timesheets = $query->get();

        $manhourData = [];

        if ($groupBy === 'craft') {
            $byCraft = $timesheets->groupBy(fn ($t) => $t->employee?->craft_id ?? 0);
            foreach ($byCraft as $craftTimesheets) {
                $craft = $craftTimesheets->first()->employee?->craft;
                $employeeIds = $craftTimesheets->pluck('employee_id')->unique();
                $regular = (float) $craftTimesheets->sum('regular_hours');
                $ot = (float) $craftTimesheets->sum('overtime_hours');
                $dt = (float) $craftTimesheets->sum('double_time_hours');
                $manhourData[] = [
                    'craft_code' => $craft?->code ?? 'N/A',
                    'craft_name' => $craft?->name ?? 'N/A',
                    'employee_count' => $employeeIds->count(),
                    'total_hours' => $regular + $ot + $dt,
                    'total_cost' => $craftTimesheets->sum('total_cost'),
                ];
            }
        } elseif ($groupBy === 'cost_code') {
            $manhourBudgets = $project->manhourBudgets()->with('costCode')->get();
            foreach ($manhourBudgets as $budget) {
                if (! empty($validated['cost_code_id']) && (int) $budget->cost_code_id !== (int) $validated['cost_code_id']) {
                    continue;
                }
                $actualHours = (float) $timesheets
                    ->where('cost_code_id', $budget->cost_code_id)
                    ->sum('total_hours');
                $cc = $budget->costCode;
                $manhourData[] = [
                    'cost_code' => $cc?->code ?? 'N/A',
                    'name' => $cc?->name ?? 'N/A',
                    'budget_hours' => (float) $budget->budget_hours,
                    'actual_hours' => $actualHours,
                ];
            }
        } else {
            foreach ($timesheets->groupBy('employee_id') as $employeeTimesheets) {
                $employee = $employeeTimesheets->first()->employee;

                $regularHours = (float) $employeeTimesheets->sum('regular_hours');
                $otHours = (float) $employeeTimesheets->sum('overtime_hours');
                $dtHours = (float) $employeeTimesheets->sum('double_time_hours');
                $totalHours = $regularHours + $otHours + $dtHours;
                $totalCost = $employeeTimesheets->sum('total_cost');

                $manhourData[] = [
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'craft' => $employee->craft?->name ?? 'N/A',
                    'cost_code' => $employeeTimesheets->first()->costCode?->code ?? 'N/A',
                    'project' => $project->name,
                    'regular_hours' => $regularHours,
                    'ot_hours' => $otHours,
                    'dt_hours' => $dtHours,
                    'total_hours' => $totalHours,
                    'labor_cost' => $totalCost,
                ];
            }
        }

        $costCodeIds = Timesheet::where('project_id', $project->id)
            ->whereNotNull('cost_code_id')
            ->distinct()
            ->pluck('cost_code_id')
            ->merge($project->budgetLines()->pluck('cost_code_id'))
            ->unique()
            ->filter();

        $costCodesForFilter = CostCode::whereIn('id', $costCodeIds)->orderBy('code')->get();

        return [
            'manhourData' => $manhourData,
            'groupBy' => $groupBy,
            'costCodesForFilter' => $costCodesForFilter,
        ];
    }

    public function timesheetReport(Request $request): View
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:employee,project,date',
        ]);

        $groupBy = $validated['group_by'] ?? 'employee';

        $query = Timesheet::whereIn('status', ['submitted', 'approved'])
            ->with(['employee', 'project']);

        if ($validated['employee_id'] ?? null) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('date', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('date', '<=', $validated['end_date']);
        }

        $timesheets = $query->get();

        $totalHours = $timesheets->sum('total_hours');
        $totalCost = $timesheets->sum('total_cost');
        $uniqueDays = $timesheets->pluck('date')->unique()->count();
        $totalBillable = (float) $timesheets->sum('billable_amount');

        $summary = [
            'total_hours' => $totalHours,
            'total_cost' => $totalCost,
            'total_billable' => $totalBillable,
            'avg_hours_per_day' => $uniqueDays > 0 ? round($totalHours / $uniqueDays, 1) : 0,
        ];

        $groupedData = collect();

        if ($groupBy === 'employee') {
            foreach ($timesheets->groupBy('employee_id') as $group) {
                $emp = $group->first()->employee;
                $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                foreach ($group as $t) {
                    $groupedData->push([
                        'group_name' => $empName,
                        'detail' => ($t->project->name ?? 'N/A') . ' — ' . ($t->date?->format('M j, Y') ?? ''),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        } elseif ($groupBy === 'project') {
            foreach ($timesheets->groupBy('project_id') as $group) {
                $projName = $group->first()->project->name ?? 'Unknown';
                foreach ($group as $t) {
                    $emp = $t->employee;
                    $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                    $groupedData->push([
                        'group_name' => $projName,
                        'detail' => $empName . ' — ' . ($t->date?->format('M j, Y') ?? ''),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        } else {
            foreach ($timesheets->sortBy('date')->groupBy(fn ($t) => $t->date?->format('Y-m-d')) as $dateStr => $group) {
                foreach ($group as $t) {
                    $emp = $t->employee;
                    $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                    $groupedData->push([
                        'group_name' => \Carbon\Carbon::parse($dateStr)->format('M j, Y'),
                        'detail' => $empName . ' — ' . ($t->project->name ?? 'N/A'),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        }

        return view('reports.timesheet-report', [
            'groupedData' => $groupedData,
            'summary' => $summary,
            'groupBy' => $groupBy,
            'employees' => Employee::orderBy('first_name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'export' => $request->get('export'),
        ]);
    }

    public function profitLoss(Request $request, Project $project): View
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $billingInvoices = BillingInvoice::where('project_id', $project->id)
            ->whereIn('status', ['sent', 'paid', 'partial'])
            ->get();

        $totalRevenue = (float) $billingInvoices->sum('total_amount');

        $commitments = $project->commitments()->get();
        $invoices = $project->invoices()->get();

        // Avoid double-counting: invoices are billed against commitments.
        // Actual cost = sum of invoices + any commitments that have no invoices yet.
        $invoicedCommitmentIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uninvoicedCommitments = $commitments->whereNotIn('id', $invoicedCommitmentIds);
        $totalCosts = (float) $invoices->sum('amount') + (float) $uninvoicedCommitments->sum('amount');

        $margin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? round(($margin / $totalRevenue) * 100, 2) : 0;

        $budgetLines = $project->budgetLines()->with('costCode')->get();
        $commitmentsByCostCode = $project->commitments()->get()->groupBy('cost_code_id');
        $invoicesByCostCode = $project->invoices()->get()->groupBy('cost_code_id');

        $byCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            $ccId = $line->cost_code_id;

            if (!isset($byCodeData[$code])) {
                $byCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'cost' => 0,
                ];
            }

            $byCodeData[$code]['cost'] += ($commitmentsByCostCode[$ccId] ?? collect())->sum('amount')
                + ($invoicesByCostCode[$ccId] ?? collect())->sum('amount');
        }

        return view('reports.profit-loss', [
            'project' => $project,
            'totalRevenue' => $totalRevenue,
            'totalCosts' => $totalCosts,
            'margin' => $margin,
            'marginPercentage' => $marginPercentage,
            'byCodeData' => $byCodeData,
            'plData' => collect($byCodeData)->values()->map(function ($item) use ($totalRevenue, $totalCosts) {
                return [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'revenue' => ($item['cost'] > 0 && $totalCosts > 0) ? ($item['cost'] / $totalCosts) * $totalRevenue : 0,
                    'cost' => $item['cost'],
                ];
            }),
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCosts,
            ],
            'export' => $request->get('export'),
        ]);
    }

    public function productivityReport(Request $request, Project $project): View
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $manhourBudgets = $project->manhourBudgets()->with('costCode')->get();
        $timesheets = $this->getTimesheetsForPeriod($project, $validated);

        // Build merged list of cost codes: from manhour budgets + any cost codes that appear in timesheets
        $budgetByCc = $manhourBudgets->keyBy('cost_code_id');
        $tsCcIds = $timesheets->pluck('cost_code_id')->unique();
        $allCcIds = $budgetByCc->keys()->merge($tsCcIds)->unique()->values();

        $productivityData = [];
        $sumBudgetHours = 0;
        $sumActualHours = 0;
        $sumEarnedHours = 0;
        $sumForecast = 0;

        foreach ($allCcIds as $ccId) {
            $budget = $budgetByCc->get($ccId);
            $budgetHours = $budget ? (float) $budget->budget_hours : 0;
            $costCodeLabel = $budget?->costCode?->code
                ?? optional($timesheets->firstWhere('cost_code_id', $ccId))->costCode?->code
                ?? 'Unassigned';

            $ccTimesheets = $timesheets->where('cost_code_id', $ccId);

            // Actual hours = regular + overtime + double time (use total_hours if set, else compute)
            $actualHours = (float) $ccTimesheets->sum(function ($t) {
                if ($t->total_hours !== null && $t->total_hours > 0) {
                    return (float) $t->total_hours;
                }
                return (float) $t->regular_hours + (float) $t->overtime_hours + (float) $t->double_time_hours;
            });

            // Earned hours = budget hours * % complete (progress ratio)
            // If commitments/invoices exist, use cost-based % complete; otherwise fall back to actual/budget ratio capped at 100%
            $percentComplete = $budgetHours > 0
                ? min(($actualHours / $budgetHours), 1)
                : 0;
            $earnedHours = $budgetHours * $percentComplete;

            $productivity = $actualHours > 0
                ? round(($earnedHours / $actualHours) * 100, 2)
                : 0;

            // Forecast at completion = actual hours / % complete (if still working) or actual (if done)
            $forecast = $percentComplete > 0 && $percentComplete < 1
                ? round($actualHours / $percentComplete, 1)
                : ($percentComplete >= 1 ? $actualHours : $budgetHours);

            $productivityData[] = [
                'code' => $costCodeLabel,
                'budget_hours' => $budgetHours,
                'actual_hours' => $actualHours,
                'earned_hours' => $earnedHours,
                'forecast' => $forecast,
            ];

            $sumBudgetHours += $budgetHours;
            $sumActualHours += $actualHours;
            $sumEarnedHours += $earnedHours;
            $sumForecast += $forecast;
        }

        $summary = [
            'budget_hours' => $sumBudgetHours,
            'actual_hours' => $sumActualHours,
            'earned_hours' => $sumEarnedHours,
            'forecast_at_completion' => $sumForecast,
        ];

        return view('reports.productivity', [
            'project' => $project,
            'summary' => $summary,
            'productivityData' => $productivityData,
            'export' => $request->get('export'),
        ]);
    }

    protected function getManHourData(Project $project, array $filters): array
    {
        $normalizeCcKey = static fn ($id): string => $id === null ? '_null_' : (string) $id;

        $query = Timesheet::where('project_id', $project->id)
            ->whereIn('status', ['submitted', 'approved'])
            ->with('costCode');

        if ($filters['date_from'] ?? null) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $timesheets = $query->get();
        $byCostCode = $timesheets->groupBy(fn ($t) => $normalizeCcKey($t->cost_code_id));

        $manhourBudgets = $project->manhourBudgets()->with('costCode')->get();

        // Build cost-based % complete by cost code (committed / budgeted dollars).
        $budgetLines = $project->budgetLines()->get()->groupBy('cost_code_id');
        $commitments = $project->commitments()->get()->groupBy('cost_code_id');
        $invoices = $project->invoices()->get()->groupBy('cost_code_id');

        $keys = $manhourBudgets->map(fn ($b) => $normalizeCcKey($b->cost_code_id))
            ->merge($timesheets->map(fn ($t) => $normalizeCcKey($t->cost_code_id)))
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        foreach ($keys as $key) {
            $ccId = $key === '_null_' ? null : (int) $key;

            $group = $byCostCode->get($key, collect());
            $actualHours = (float) $group->sum('total_hours');
            $laborCost = (float) $group->sum('total_cost');

            $budgetHours = (float) $manhourBudgets
                ->where('cost_code_id', $ccId)
                ->sum('budget_hours');

            // % complete: prefer cost-based (committed/budget $$). Fall back to actual/budget hours.
            $budgetDollars = (float) ($budgetLines->get($ccId)?->sum('amount') ?? 0);
            $committedDollars = (float) ($commitments->get($ccId)?->sum('amount') ?? 0)
                + (float) ($invoices->get($ccId)?->sum('amount') ?? 0);

            if ($budgetDollars > 0) {
                $percentComplete = min($committedDollars / $budgetDollars, 1);
            } elseif ($budgetHours > 0) {
                $percentComplete = min($actualHours / $budgetHours, 1);
            } else {
                $percentComplete = 0;
            }

            $earnedHours = $budgetHours * $percentComplete;

            $budget = $manhourBudgets->firstWhere('cost_code_id', $ccId);
            $firstTs = $group->first();
            $label = $budget?->costCode?->name
                ?? $firstTs?->costCode?->name
                ?? ($ccId === null ? 'Unassigned' : 'Unknown');

            $rows[] = [
                'date' => $label,
                'actual_hours' => $actualHours,
                'budget_hours' => $budgetHours,
                'earned_hours' => $earnedHours,
                'percent_complete' => round($percentComplete * 100, 1),
                'labor_cost' => $laborCost,
            ];
        }

        return $rows;
    }

    protected function getManHourForecastData(Project $project, array $filters): array
    {
        $timesheets = $this->getTimesheetsForPeriod($project, $filters);

        $totalRegularHours = (float) $timesheets->sum('regular_hours');
        $totalOtHours = (float) $timesheets->sum('overtime_hours');
        $totalDtHours = (float) $timesheets->sum('double_time_hours');
        $totalActualHours = $totalRegularHours + $totalOtHours + $totalDtHours;

        $budgetHours = $project->manhourBudgets()->sum('budget_hours');

        $productivity = $totalActualHours > 0
            ? round(($budgetHours / $totalActualHours) * 100, 2)
            : 0;

        $forecastHours = $totalActualHours;
        $variance = $budgetHours - $totalActualHours;

        return [
            'earned_hours' => $budgetHours,
            'actual_hours' => $totalActualHours,
            'productivity' => $productivity,
            'forecast_hours' => $forecastHours,
            'variance' => $variance,
        ];
    }

    protected function getTimesheetsForPeriod(Project $project, array $filters): \Illuminate\Support\Collection
    {
        $query = Timesheet::where('project_id', $project->id)
            ->whereIn('status', ['submitted', 'approved']);

        if ($filters['date_from'] ?? null) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    // ──────────────────────────────────────────
    // PDF Export Methods
    // ──────────────────────────────────────────

    public function costReportPdf(Request $request, Project $project)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with(['costCode', 'commitments', 'invoices'])->get();
        $costCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = ['code' => $code, 'name' => $line->costCode?->name ?? 'Unassigned', 'budget' => 0, 'committed' => 0, 'invoiced' => 0, 'balance' => 0, 'percentage_complete' => 0];
            }
            $committed = $line->commitments->sum('amount');
            $invoiced = $line->invoices->sum('amount');
            $budget = $line->amount;
            $costCodeData[$code]['budget'] += $budget;
            $costCodeData[$code]['committed'] += $committed;
            $costCodeData[$code]['invoiced'] += $invoiced;
            $costCodeData[$code]['balance'] += $budget - $committed;
            $costCodeData[$code]['percentage_complete'] = $costCodeData[$code]['budget'] > 0
                ? round(($costCodeData[$code]['committed'] / $costCodeData[$code]['budget']) * 100, 2) : 0;
        }

        $changeOrders = $project->changeOrders()->where('status', 'approved')->get();
        $manhourData = $this->getManHourData($project, $validated);

        $totalLaborCost = array_sum(array_column($manhourData, 'labor_cost'));
        $totalLaborHours = array_sum(array_column($manhourData, 'actual_hours'));
        $compositeRate = $totalLaborHours > 0 ? round($totalLaborCost / $totalLaborHours, 2) : 0;

        $pdf = Pdf::loadView('pdf.cost-report', compact('project', 'costCodeData', 'changeOrders', 'manhourData', 'compositeRate', 'totalLaborCost', 'totalLaborHours'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("cost-report-{$project->project_number}.pdf");
    }

    public function forecastPdf(Request $request, Project $project)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with(['costCode', 'commitments', 'invoices'])->get();
        $originalBudgetTotal = $budgetLines->sum('amount');
        $approvedCoTotal = $project->changeOrders()->where('status', 'approved')->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        $costCodeData = [];
        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = ['code' => $code, 'name' => $line->costCode?->name ?? 'Unassigned', 'original_budget' => 0, 'forecast_budget' => 0, 'committed' => 0, 'invoiced' => 0, 'balance' => 0];
            }
            $committed = $line->commitments->sum('amount');
            $invoiced = $line->invoices->sum('amount');
            $costCodeData[$code]['original_budget'] += $line->amount;
            $costCodeData[$code]['forecast_budget'] += $line->amount;
            $costCodeData[$code]['committed'] += $committed;
            $costCodeData[$code]['invoiced'] += $invoiced;
            $costCodeData[$code]['balance'] += $line->amount - $committed;
        }

        $manhourData = $this->getManHourForecastData($project, $validated);
        $pdf = Pdf::loadView('pdf.forecast', compact('project', 'costCodeData', 'originalBudgetTotal', 'forecastBudgetTotal', 'manhourData'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("forecast-{$project->project_number}.pdf");
    }

    public function manhourReportPdf(Request $request, Project $project)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:employee,craft,cost_code',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
        ]);

        $validated['group_by'] = 'employee';
        $payload = $this->buildManhourReportPayload($project, $validated);
        $manhourData = $payload['manhourData'];

        $pdf = Pdf::loadView('pdf.manhours', compact('project', 'manhourData'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("manhour-report-{$project->project_number}.pdf");
    }

    public function profitLossPdf(Request $request, Project $project)
    {
        $billingInvoices = BillingInvoice::where('project_id', $project->id)->where('status', 'paid')->get();
        $totalRevenue = $billingInvoices->sum('total_amount');
        $commitments = $project->commitments()->get();
        $invoices = $project->invoices()->get();
        $invoicedCommitmentIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uninvoicedCommitments = $commitments->whereNotIn('id', $invoicedCommitmentIds);
        $totalCosts = (float) $invoices->sum('amount') + (float) $uninvoicedCommitments->sum('amount');
        $margin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? round(($margin / $totalRevenue) * 100, 2) : 0;

        $budgetLines = $project->budgetLines()->with(['costCode', 'commitments', 'invoices'])->get();
        $byCodeData = [];
        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            if (!isset($byCodeData[$code])) {
                $byCodeData[$code] = ['code' => $code, 'name' => $line->costCode?->name ?? 'Unassigned', 'cost' => 0];
            }
            $byCodeData[$code]['cost'] += $line->commitments->sum('amount') + $line->invoices->sum('amount');
        }

        $pdf = Pdf::loadView('pdf.profit-loss', compact('project', 'totalRevenue', 'totalCosts', 'margin', 'marginPercentage', 'byCodeData'));

        return $pdf->download("profit-loss-{$project->project_number}.pdf");
    }

    public function productivityReportPdf(Request $request, Project $project)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $manhourBudgets = $project->manhourBudgets()->get();
        $timesheets = $this->getTimesheetsForPeriod($project, $validated);
        $productivityData = [];

        foreach ($manhourBudgets as $budget) {
            $earnedHours = $budget->budget_hours;
            $actualHours = $timesheets->where('cost_code_id', $budget->cost_code_id)->sum('total_hours');
            $productivity = $actualHours > 0 ? round(($earnedHours / $actualHours) * 100, 2) : 0;
            $productivityData[] = [
                'cost_code' => $budget->costCode?->code ?? 'Unassigned',
                'earned_hours' => $earnedHours,
                'actual_hours' => $actualHours,
                'productivity' => $productivity,
                'variance' => $earnedHours - $actualHours,
            ];
        }

        $pdf = Pdf::loadView('pdf.productivity', compact('project', 'productivityData'));

        return $pdf->download("productivity-{$project->project_number}.pdf");
    }

    public function timesheetReportPdf(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:employee,project,date',
        ]);

        $groupBy = $validated['group_by'] ?? 'employee';
        $query = Timesheet::whereIn('status', ['submitted', 'approved'])->with(['employee', 'project']);

        if ($validated['employee_id'] ?? null) $query->where('employee_id', $validated['employee_id']);
        if ($validated['project_id'] ?? null) $query->where('project_id', $validated['project_id']);
        if ($validated['start_date'] ?? null) $query->where('date', '>=', $validated['start_date']);
        if ($validated['end_date'] ?? null) $query->where('date', '<=', $validated['end_date']);

        $timesheets = $query->get();
        $groupedData = collect();

        if ($groupBy === 'employee') {
            foreach ($timesheets->groupBy('employee_id') as $group) {
                $emp = $group->first()->employee;
                $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                foreach ($group as $t) {
                    $groupedData->push([
                        'group_name' => $empName,
                        'detail' => ($t->project->name ?? 'N/A') . ' — ' . ($t->date?->format('M j, Y') ?? ''),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        } elseif ($groupBy === 'project') {
            foreach ($timesheets->groupBy('project_id') as $group) {
                $projName = $group->first()->project->name ?? 'Unknown';
                foreach ($group as $t) {
                    $emp = $t->employee;
                    $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                    $groupedData->push([
                        'group_name' => $projName,
                        'detail' => $empName . ' — ' . ($t->date?->format('M j, Y') ?? ''),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        } else {
            foreach ($timesheets->sortBy('date')->groupBy(fn ($t) => $t->date?->format('Y-m-d')) as $dateStr => $group) {
                foreach ($group as $t) {
                    $emp = $t->employee;
                    $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                    $groupedData->push([
                        'group_name' => \Carbon\Carbon::parse($dateStr)->format('M j, Y'),
                        'detail' => $empName . ' — ' . ($t->project->name ?? 'N/A'),
                        'hours' => $t->total_hours,
                        'labor_cost' => $t->total_cost,
                        'billable_amount' => (float) ($t->billable_amount ?? 0),
                    ]);
                }
            }
        }

        $pdf = Pdf::loadView('pdf.timesheet-report', compact('groupedData', 'groupBy'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('timesheet-report.pdf');
    }

    // ──────────────────────────────────────────
    // EXCEL / CSV EXPORTS
    // ──────────────────────────────────────────

    /**
     * Stream a CSV file that Excel will open natively. UTF-8 BOM is prepended so
     * foreign characters and currency symbols render correctly, and the file is
     * named with a .csv extension so double-clicking opens it in Excel.
     */
    protected function streamCsv(string $filename, array $header, iterable $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function costReportExcel(Request $request, Project $project)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with(['costCode', 'commitments', 'invoices'])->get();
        $costCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'budget' => 0,
                    'committed' => 0,
                    'invoiced' => 0,
                    'balance' => 0,
                ];
            }
            $committed = $line->commitments->sum('amount');
            $invoiced = $line->invoices->sum('amount');
            $costCodeData[$code]['budget']    += (float) $line->amount;
            $costCodeData[$code]['committed'] += (float) $committed;
            $costCodeData[$code]['invoiced']  += (float) $invoiced;
            $costCodeData[$code]['balance']   += (float) $line->amount - (float) $committed;
        }

        $totals = ['budget' => 0, 'committed' => 0, 'invoiced' => 0, 'balance' => 0];
        foreach ($costCodeData as $row) {
            foreach ($totals as $k => $_) {
                $totals[$k] += $row[$k];
            }
        }

        $header = ['Cost Code', 'Name', 'Budget', 'Committed', 'Invoiced', 'Balance', '% Complete'];
        $rows = [];
        foreach ($costCodeData as $row) {
            $pct = $row['budget'] > 0 ? round(($row['committed'] / $row['budget']) * 100, 2) : 0;
            $rows[] = [
                $row['code'],
                $row['name'],
                number_format($row['budget'], 2, '.', ''),
                number_format($row['committed'], 2, '.', ''),
                number_format($row['invoiced'], 2, '.', ''),
                number_format($row['balance'], 2, '.', ''),
                $pct,
            ];
        }
        // Totals row
        $totalPct = $totals['budget'] > 0 ? round(($totals['committed'] / $totals['budget']) * 100, 2) : 0;
        $rows[] = [
            'TOTAL',
            '',
            number_format($totals['budget'], 2, '.', ''),
            number_format($totals['committed'], 2, '.', ''),
            number_format($totals['invoiced'], 2, '.', ''),
            number_format($totals['balance'], 2, '.', ''),
            $totalPct,
        ];

        $filename = "cost-report-{$project->project_number}-" . now()->format('Ymd') . '.csv';
        return $this->streamCsv($filename, $header, $rows);
    }

    public function forecastExcel(Request $request, Project $project)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $budgetLines = $project->budgetLines()->with(['costCode', 'commitments', 'invoices'])->get();
        $originalBudgetTotal = (float) $budgetLines->sum('amount');
        $approvedCoTotal = (float) $project->changeOrders()->where('status', 'approved')->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        $costCodeData = [];
        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';
            if (!isset($costCodeData[$code])) {
                $costCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'original_budget' => 0,
                    'forecast_budget' => 0,
                    'committed' => 0,
                    'invoiced' => 0,
                    'balance' => 0,
                ];
            }
            $committed = (float) $line->commitments->sum('amount');
            $invoiced = (float) $line->invoices->sum('amount');
            $costCodeData[$code]['original_budget'] += (float) $line->amount;
            $costCodeData[$code]['forecast_budget'] += (float) $line->amount;
            $costCodeData[$code]['committed']       += $committed;
            $costCodeData[$code]['invoiced']        += $invoiced;
            $costCodeData[$code]['balance']         += (float) $line->amount - $committed;
        }

        $totals = ['original_budget' => 0, 'forecast_budget' => 0, 'committed' => 0, 'invoiced' => 0, 'balance' => 0];
        foreach ($costCodeData as $row) {
            foreach ($totals as $k => $_) {
                $totals[$k] += $row[$k];
            }
        }

        $header = ['Cost Code', 'Name', 'Original Budget', 'Forecast Budget', 'Committed', 'Invoiced', 'Variance (Budget - Committed)'];
        $rows = [];
        foreach ($costCodeData as $row) {
            $rows[] = [
                $row['code'],
                $row['name'],
                number_format($row['original_budget'], 2, '.', ''),
                number_format($row['forecast_budget'], 2, '.', ''),
                number_format($row['committed'], 2, '.', ''),
                number_format($row['invoiced'], 2, '.', ''),
                number_format($row['balance'], 2, '.', ''),
            ];
        }
        // Summary rows
        $rows[] = [
            'TOTAL',
            '',
            number_format($totals['original_budget'], 2, '.', ''),
            number_format($totals['forecast_budget'], 2, '.', ''),
            number_format($totals['committed'], 2, '.', ''),
            number_format($totals['invoiced'], 2, '.', ''),
            number_format($totals['balance'], 2, '.', ''),
        ];
        $rows[] = []; // blank
        $rows[] = ['Approved Change Orders', '', '', number_format($approvedCoTotal, 2, '.', ''), '', '', ''];
        $rows[] = ['Forecast Total', '', number_format($originalBudgetTotal, 2, '.', ''), number_format($forecastBudgetTotal, 2, '.', ''), '', '', ''];

        $filename = "forecast-{$project->project_number}-" . now()->format('Ymd') . '.csv';
        return $this->streamCsv($filename, $header, $rows);
    }
}
