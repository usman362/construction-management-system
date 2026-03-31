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

        $budgetLines = $project->budgetLines()
            ->with(['costCode', 'commitments', 'invoices'])
            ->get();

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
                    'percentage_complete' => 0,
                ];
            }

            $committed = $line->commitments->sum('amount');
            $invoiced = $line->invoices->sum('amount');
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

        return view('reports.cost-report', [
            'project' => $project,
            'costData' => collect($costCodeData)->values(),
            'costCodeData' => $costCodeData,
            'changeOrders' => $changeOrders,
            'manhourData' => $manhourData,
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

        $budgetLines = $project->budgetLines()
            ->with(['costCode', 'commitments', 'invoices'])
            ->get();

        $originalBudgetTotal = $budgetLines->sum('amount');
        $approvedCoTotal = $project->changeOrders()
            ->where('status', 'approved')
            ->sum('amount');
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

            $committed = $line->commitments->sum('amount');
            $invoiced = $line->invoices->sum('amount');

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
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Timesheet::where('project_id', $project->id)
            ->where('status', 'approved')
            ->with(['employee.craft']);

        if ($validated['date_from'] ?? null) {
            $query->where('date', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('date', '<=', $validated['date_to']);
        }

        $timesheets = $query->get()->groupBy('employee_id');

        $manhourData = [];

        foreach ($timesheets as $employeeId => $employeeTimesheets) {
            $employee = $employeeTimesheets->first()->employee;

            $regularHours = $employeeTimesheets->sum('regular_hours');
            $otHours = $employeeTimesheets->sum('ot_hours');
            $dtHours = $employeeTimesheets->sum('dt_hours');
            $totalHours = $regularHours + $otHours + $dtHours;
            $totalCost = $employeeTimesheets->sum('total_cost');

            $manhourData[] = [
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'craft' => $employee->craft?->name ?? 'N/A',
                'project' => $project->name,
                'regular_hours' => $regularHours,
                'ot_hours' => $otHours,
                'dt_hours' => $dtHours,
                'total_hours' => $totalHours,
                'labor_cost' => $totalCost,
            ];
        }

        return view('reports.manhours', [
            'project' => $project,
            'manhourData' => $manhourData,
            'export' => $request->get('export'),
        ]);
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
        $billableRate = 1.5;
        $totalBillable = $totalCost * $billableRate;

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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
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
            ->where('status', 'paid')
            ->get();

        $totalRevenue = $billingInvoices->sum('total_amount');

        $commitments = $project->commitments()->get();
        $invoices = $project->invoices()->get();

        $totalCosts = $commitments->sum('amount') + $invoices->sum('amount');

        $margin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? round(($margin / $totalRevenue) * 100, 2) : 0;

        $budgetLines = $project->budgetLines()
            ->with(['costCode', 'commitments', 'invoices'])
            ->get();

        $byCodeData = [];

        foreach ($budgetLines as $line) {
            $code = $line->costCode?->code ?? 'Unassigned';

            if (!isset($byCodeData[$code])) {
                $byCodeData[$code] = [
                    'code' => $code,
                    'name' => $line->costCode?->name ?? 'Unassigned',
                    'cost' => 0,
                ];
            }

            $byCodeData[$code]['cost'] += $line->commitments->sum('amount') + $line->invoices->sum('amount');
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

        $manhourBudgets = $project->manhourBudgets()->get();
        $timesheets = $this->getTimesheetsForPeriod($project, $validated);

        $productivityData = [];

        foreach ($manhourBudgets as $budget) {
            $earnedHours = $budget->estimated_hours;

            $actualHours = $timesheets
                ->where('cost_code_id', $budget->cost_code_id)
                ->sum('total_hours');

            $productivity = $actualHours > 0
                ? round(($earnedHours / $actualHours) * 100, 2)
                : 0;

            $variance = $earnedHours - $actualHours;

            $productivityData[] = [
                'cost_code' => $budget->costCode?->code ?? 'Unassigned',
                'earned_hours' => $earnedHours,
                'actual_hours' => $actualHours,
                'productivity' => $productivity,
                'variance' => $variance,
            ];
        }

        return view('reports.productivity', [
            'project' => $project,
            'productivityData' => $productivityData,
            'export' => $request->get('export'),
        ]);
    }

    protected function getManHourData(Project $project, array $filters): array
    {
        $query = Timesheet::where('project_id', $project->id)
            ->where('status', 'approved');

        if ($filters['date_from'] ?? null) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $timesheets = $query->get();

        $totalRegularHours = $timesheets->sum('regular_hours');
        $totalOtHours = $timesheets->sum('ot_hours');
        $totalDtHours = $timesheets->sum('dt_hours');
        $totalHours = $totalRegularHours + $totalOtHours + $totalDtHours;

        $budgetHours = $project->manhourBudgets()->sum('estimated_hours');

        return [
            'total_regular_hours' => $totalRegularHours,
            'total_ot_hours' => $totalOtHours,
            'total_dt_hours' => $totalDtHours,
            'total_hours' => $totalHours,
            'budget_hours' => $budgetHours,
            'hours_variance' => $budgetHours - $totalHours,
        ];
    }

    protected function getManHourForecastData(Project $project, array $filters): array
    {
        $timesheets = $this->getTimesheetsForPeriod($project, $filters);

        $totalRegularHours = $timesheets->sum('regular_hours');
        $totalOtHours = $timesheets->sum('ot_hours');
        $totalDtHours = $timesheets->sum('dt_hours');
        $totalActualHours = $totalRegularHours + $totalOtHours + $totalDtHours;

        $budgetHours = $project->manhourBudgets()->sum('estimated_hours');

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
            ->where('status', 'approved');

        if ($filters['date_from'] ?? null) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('date', '<=', $filters['date_to']);
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

        $pdf = Pdf::loadView('pdf.cost-report', compact('project', 'costCodeData', 'changeOrders', 'manhourData'));
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
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Timesheet::where('project_id', $project->id)->where('status', 'approved')->with(['employee.craft']);
        if ($validated['date_from'] ?? null) $query->where('date', '>=', $validated['date_from']);
        if ($validated['date_to'] ?? null) $query->where('date', '<=', $validated['date_to']);

        $timesheets = $query->get()->groupBy('employee_id');
        $manhourData = [];

        foreach ($timesheets as $employeeId => $employeeTimesheets) {
            $employee = $employeeTimesheets->first()->employee;
            $regularHours = $employeeTimesheets->sum('regular_hours');
            $otHours = $employeeTimesheets->sum('ot_hours');
            $dtHours = $employeeTimesheets->sum('dt_hours');
            $manhourData[] = [
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'craft' => $employee->craft?->name ?? 'N/A',
                'regular_hours' => $regularHours,
                'ot_hours' => $otHours,
                'dt_hours' => $dtHours,
                'total_hours' => $regularHours + $otHours + $dtHours,
                'labor_cost' => $employeeTimesheets->sum('total_cost'),
            ];
        }

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
        $totalCosts = $commitments->sum('amount') + $invoices->sum('amount');
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
            $earnedHours = $budget->estimated_hours;
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
        $billableRate = 1.5;
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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
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
                        'billable_amount' => ($t->total_cost ?? 0) * $billableRate,
                    ]);
                }
            }
        }

        $pdf = Pdf::loadView('pdf.timesheet-report', compact('groupedData', 'groupBy'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('timesheet-report.pdf');
    }
}
