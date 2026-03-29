<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\Commitment;
use App\Models\Invoice;
use App\Models\ChangeOrder;
use App\Models\Timesheet;
use App\Models\ManhourBudget;
use App\Models\BillingInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function costReport(Request $request, Project $project): View
    {
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
            'costCodeData' => $costCodeData,
            'changeOrders' => $changeOrders,
            'manhourData' => $manhourData,
            'export' => $request->get('export'),
        ]);
    }

    public function forecast(Request $request, Project $project): View
    {
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

        return view('reports.forecast', [
            'project' => $project,
            'costCodeData' => $costCodeData,
            'originalBudgetTotal' => $originalBudgetTotal,
            'forecastBudgetTotal' => $forecastBudgetTotal,
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
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:employee,project',
        ]);

        $groupBy = $validated['group_by'] ?? 'employee';

        $query = Timesheet::where('status', 'approved')
            ->with(['employee', 'project']);

        if ($validated['employee_id'] ?? null) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        if ($validated['date_from'] ?? null) {
            $query->where('date', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('date', '<=', $validated['date_to']);
        }

        $timesheets = $query->get();
        $groupedData = [];

        if ($groupBy === 'employee') {
            $groupedData = $timesheets->groupBy('employee_id')->map(function ($group) {
                return [
                    'name' => $group->first()->employee->first_name . ' ' . $group->first()->employee->last_name,
                    'type' => 'employee',
                    'total_hours' => $group->sum('total_hours'),
                    'total_cost' => $group->sum('total_cost'),
                    'entries' => $group->map(fn ($t) => [
                        'date' => $t->date,
                        'project' => $t->project->name,
                        'hours' => $t->total_hours,
                        'cost' => $t->total_cost,
                    ]),
                ];
            });
        } else {
            $groupedData = $timesheets->groupBy('project_id')->map(function ($group) {
                return [
                    'name' => $group->first()->project->name,
                    'type' => 'project',
                    'total_hours' => $group->sum('total_hours'),
                    'total_cost' => $group->sum('total_cost'),
                    'entries' => $group->map(fn ($t) => [
                        'date' => $t->date,
                        'employee' => $t->employee->first_name . ' ' . $t->employee->last_name,
                        'hours' => $t->total_hours,
                        'cost' => $t->total_cost,
                    ]),
                ];
            });
        }

        return view('reports.timesheet-report', [
            'groupedData' => $groupedData,
            'groupBy' => $groupBy,
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
}
