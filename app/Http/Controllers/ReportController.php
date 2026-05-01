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
use App\Models\CostType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Build a cost-type-grouped cost breakdown for a project.
     *
     * Hierarchy per the client's spec:
     *   Budget → Cost Type → Phase Code
     *
     * Commitments/invoices/timesheets all live at the (cost_code_id, cost_type_id)
     * level. We aggregate per composite key so the same phase code split across
     * Direct Labor + Indirect Labor (or Equipment + Materials) shows as separate
     * rows and can't double-count shared cost-code-level totals.
     *
     * Invoices don't carry a cost_type_id directly — we resolve it via the
     * invoice's parent commitment, or fall back to the cost code's default type.
     *
     * Returns a flat array of rows. Header rows (`is_header = true`) separate
     * each cost type; detail rows (`indent = true`) list phase codes under the
     * header. The view renders the indent/header flags natively.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildCostTypeBreakdown(Project $project): array
    {
        $budgetLines = $project->budgetLines()->with(['costCode', 'costType'])->get();
        $commitments = $project->commitments()->with(['costCode', 'costType'])->get();
        $invoices    = $project->invoices()->with(['commitment', 'costCode'])->get();
        $timesheets  = $project->timesheets()
            ->where('status', '!=', 'rejected')
            ->with(['costCode', 'costType'])
            ->get();

        $keyFor = static fn ($ccId, $ctId): string =>
            (string) ($ccId ?? 'x') . '|' . (string) ($ctId ?? 'x');

        // Preload cost types for invoice fallback (when invoice has no commitment
        // and we need to look up the cost code's default cost type).
        $costTypesById = CostType::all()->keyBy('id');

        $makeBucket = function ($ccId, $ctId, $costCode, $costType) {
            return [
                'cost_code_id'        => $ccId,
                'cost_type_id'        => $ctId,
                'code'                => $costCode?->code ?? 'Unassigned',
                'name'                => $costCode?->name ?? 'Unassigned',
                'cost_type'           => $costType?->name ?? 'Unassigned',
                'cost_type_sort'      => $costType?->sort_order ?? 9999,
                'budget'              => 0.0,
                'committed_vendor'    => 0.0,
                'committed_labor'     => 0.0,
                'invoiced'            => 0.0,
            ];
        };

        $buckets = [];

        // Budget lines — authoritative for the budget column.
        foreach ($budgetLines as $line) {
            $k = $keyFor($line->cost_code_id, $line->cost_type_id);
            $buckets[$k] ??= $makeBucket($line->cost_code_id, $line->cost_type_id, $line->costCode, $line->costType);
            $buckets[$k]['budget'] += (float) $line->amount;
        }

        // Vendor commitments.
        foreach ($commitments as $c) {
            $k = $keyFor($c->cost_code_id, $c->cost_type_id);
            $buckets[$k] ??= $makeBucket($c->cost_code_id, $c->cost_type_id, $c->costCode, $c->costType);
            $buckets[$k]['committed_vendor'] += (float) $c->amount;
        }

        // Labor timesheets (non-rejected) — fold into committed_labor.
        foreach ($timesheets as $t) {
            $k = $keyFor($t->cost_code_id, $t->cost_type_id);
            $buckets[$k] ??= $makeBucket($t->cost_code_id, $t->cost_type_id, $t->costCode, $t->costType);
            $buckets[$k]['committed_labor'] += (float) $t->total_cost;
        }

        // Per diem dollars — route under their OWN cost type (PER DIEM, code 07
        // by default) instead of the labor cost type the rest of the allocation
        // sits under. This is what lets the cost tracker see per diem as a
        // separate line in the cost report rather than being absorbed into
        // Direct Labor.
        $perDiemAllocations = \App\Models\TimesheetCostAllocation::query()
            ->whereHas('timesheet', function ($q) use ($project) {
                $q->where('project_id', $project->id)->where('status', '!=', 'rejected');
            })
            ->where('per_diem_amount', '>', 0)
            ->with(['costCode', 'perDiemCostType', 'costType'])
            ->get();

        foreach ($perDiemAllocations as $alloc) {
            // Prefer the explicit per-diem cost type; fall back to the labor
            // cost type for legacy rows that haven't been re-saved since the
            // backfill (rare, but keeps reports from dropping dollars).
            $ctId     = $alloc->per_diem_cost_type_id ?? $alloc->cost_type_id;
            $costType = $alloc->perDiemCostType ?? $alloc->costType ?? ($ctId ? $costTypesById->get($ctId) : null);
            $k        = $keyFor($alloc->cost_code_id, $ctId);
            $buckets[$k] ??= $makeBucket($alloc->cost_code_id, $ctId, $alloc->costCode, $costType);
            $buckets[$k]['committed_labor'] += (float) $alloc->per_diem_amount;
        }

        // Vendor invoices — infer cost_type_id from commitment, then cost code default.
        foreach ($invoices as $inv) {
            $ctId = $inv->commitment?->cost_type_id ?? $inv->costCode?->cost_type_id;
            $k = $keyFor($inv->cost_code_id, $ctId);
            if (!isset($buckets[$k])) {
                $buckets[$k] = $makeBucket(
                    $inv->cost_code_id,
                    $ctId,
                    $inv->costCode,
                    $ctId ? $costTypesById->get($ctId) : null
                );
            }
            $buckets[$k]['invoiced'] += (float) $inv->amount;
        }

        // Finalize derived fields. `cost` is an alias for `committed` so the P&L
        // view/PDF (which reads `cost`) can consume the same row shape.
        foreach ($buckets as $k => $b) {
            $committed = $b['committed_vendor'] + $b['committed_labor'];
            $buckets[$k]['committed'] = $committed;
            $buckets[$k]['cost']      = $committed;
            $buckets[$k]['balance']   = $b['budget'] - $committed;
            $buckets[$k]['percentage_complete'] = $b['budget'] > 0
                ? round(($committed / $b['budget']) * 100, 2)
                : 0.0;
        }

        // Sort by cost type sort_order (Direct Labor first, Indirect Labor next, etc.),
        // then by phase code within each type.
        $rows = array_values($buckets);
        usort($rows, function ($a, $b) {
            return [$a['cost_type_sort'], $a['cost_type'], $a['code']]
                <=> [$b['cost_type_sort'], $b['cost_type'], $b['code']];
        });

        // Emit with header rows so the view can render the hierarchy
        // (Budget → Cost Type → Phase Code) without needing nested loops.
        $out = [];
        $currentType = '__unset__';
        $groupTotals = null;
        $emitGroupTotal = function () use (&$groupTotals, &$out, &$currentType) {
            if ($groupTotals !== null) {
                $groupTotals['is_group_total'] = true;
                $groupTotals['code'] = '';
                $groupTotals['name'] = 'Subtotal — ' . $currentType;
                $groupTotals['cost_type'] = $currentType;
                $groupTotals['cost'] = $groupTotals['committed']; // P&L alias
                $groupTotals['percentage_complete'] = $groupTotals['budget'] > 0
                    ? round(($groupTotals['committed'] / $groupTotals['budget']) * 100, 2)
                    : 0.0;
                $out[] = $groupTotals;
            }
        };
        foreach ($rows as $r) {
            if ($r['cost_type'] !== $currentType) {
                $emitGroupTotal();
                $currentType = $r['cost_type'];
                $groupTotals = [
                    'budget' => 0, 'committed' => 0, 'committed_vendor' => 0,
                    'committed_labor' => 0, 'invoiced' => 0, 'balance' => 0,
                ];
                $out[] = [
                    'is_header'   => true,
                    'code'        => '',
                    'name'        => strtoupper($currentType),
                    'cost_type'   => $currentType,
                    'budget'      => 0,
                    'committed'   => 0,
                    'committed_vendor' => 0,
                    'committed_labor'  => 0,
                    'invoiced'    => 0,
                    'balance'     => 0,
                    'percentage_complete' => 0,
                ];
            }
            $r['indent'] = true;
            $out[] = $r;
            foreach (['budget', 'committed', 'committed_vendor', 'committed_labor', 'invoiced', 'balance'] as $k) {
                $groupTotals[$k] += $r[$k];
            }
        }
        $emitGroupTotal();

        return $out;
    }

    public function costReport(Request $request, Project $project): View
    {
        $project->load('client');
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Breakdown rows are grouped by cost type then phase code
        // (Budget → Cost Type → Phase Code), with header + subtotal rows.
        $rows = $this->buildCostTypeBreakdown($project);

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
            'costData' => collect($rows),
            'costCodeData' => $rows,
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

        // Totals for the header cards (Original Budget + Forecast Budget).
        $originalBudgetTotal = (float) $project->budgetLines()->sum('budget_amount');
        $approvedCoTotal = (float) $project->changeOrders()
            ->where('status', 'approved')
            ->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        // Build cost-type-grouped breakdown, then add the forecast-specific
        // `original_budget` / `forecast_budget` aliases the view expects.
        $rows = $this->buildCostTypeBreakdown($project);
        foreach ($rows as $i => $r) {
            $rows[$i]['original_budget'] = $r['budget'];
            $rows[$i]['forecast_budget'] = $r['budget']; // no per-line forecast override yet
        }

        $manhourData = $this->getManHourForecastData($project, $validated);

        // Populate the "Manhours Summary" table. The view reads:
        //   $forecastTotals['earned'|'productivity'|'forecast'|'total_hours'|'budget']
        // All four labor-summary keys must be present or the section stays blank.
        $totalActualHours   = (float) array_sum(array_column($manhourData, 'actual_hours'));
        $totalBudgetHours   = (float) array_sum(array_column($manhourData, 'budget_hours'));
        $totalForecastHours = (float) array_sum(array_column($manhourData, 'forecast_hours'));
        $earnedHours = 0.0;
        foreach ($manhourData as $mh) {
            $bh = (float) ($mh['budget_hours'] ?? 0);
            $ah = (float) ($mh['actual_hours'] ?? 0);
            $pc = $bh > 0 ? min($ah / $bh, 1) : 0;
            $earnedHours += $bh * $pc;
        }
        $productivity = $totalActualHours > 0
            ? round(($earnedHours / $totalActualHours) * 100, 2)
            : 0;

        $changeOrders = $project->changeOrders()->where('status', 'approved')->get();

        return view('reports.forecast', [
            'project' => $project,
            'costCodeData' => $rows,
            'costData' => collect($rows),
            'forecastData' => collect($rows),
            'originalBudgetTotal' => $originalBudgetTotal,
            'forecastBudgetTotal' => $forecastBudgetTotal,
            'originalBudgetTotals' => ['budget' => $originalBudgetTotal],
            'forecastTotals' => [
                'budget'       => $forecastBudgetTotal,
                'earned'       => round($earnedHours, 1),
                'productivity' => $productivity,
                'forecast'     => round($totalForecastHours, 1),
                'total_hours'  => round($totalActualHours, 1),
            ],
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

        // Include all timesheets (drafts included) — reports should reflect
        // actual entered work, not just approved rows.
        $query = Timesheet::where('project_id', $project->id)
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
            'group_by' => 'nullable|in:employee,project,date,weekly',
            'week_ending' => 'nullable|date',
        ]);

        $groupBy = $validated['group_by'] ?? 'employee';

        // ── Weekly matrix view (WEEKLY TIMESHEET spreadsheet layout) ──
        // Pivots a project's timesheets over Mon..Sun ending on week_ending,
        // grouped by shift, rows per employee with ST/OT/Per Diem per day.
        if ($groupBy === 'weekly') {
            $weekly = $this->buildWeeklyTimesheetMatrix(
                $validated['project_id'] ?? null,
                $validated['week_ending'] ?? null
            );

            return view('reports.timesheet-report', [
                'groupedData' => collect(),
                'summary' => [
                    'total_hours' => $weekly['grand_st'] + $weekly['grand_ot'],
                    'total_cost' => $weekly['grand_cost'],
                    'total_billable' => $weekly['grand_billable'],
                    'avg_hours_per_day' => 0,
                ],
                'groupBy' => $groupBy,
                'weekly' => $weekly,
                'employees' => Employee::orderBy('first_name')->get(),
                'projects' => Project::orderBy('name')->get(),
                'export' => $request->get('export'),
            ]);
        }

        // Include all timesheets (including drafts) — client needs to see
        // everything entered, regardless of approval status.
        $query = Timesheet::with(['employee', 'project']);

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
            'weekly' => null,
            'employees' => Employee::orderBy('first_name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'export' => $request->get('export'),
        ]);
    }

    /**
     * Build a weekly timesheet pivot matrix for the WEEKLY TIMESHEET layout.
     *
     * Returns a structure the blade view can render directly:
     *   [
     *     'project'      => Project|null,
     *     'week_ending'  => Carbon,
     *     'days'         => [Carbon x 7],   // Mon..Sun ending on week_ending
     *     'shifts'       => [
     *        shift_name => [
     *           'multiplier' => float,
     *           'employees'  => [
     *               [id, name, classification, craft, days=>[iso=>[st,ot,pd]], st_total, ot_total, pd_total, cost, billable],
     *           ],
     *           'day_totals' => [iso => [st, ot, pd]],
     *           'shift_st' => .., 'shift_ot' => .., 'shift_pd' => ..,
     *           'shift_cost' => .., 'shift_billable' => ..,
     *        ],
     *     ],
     *     'grand_st', 'grand_ot', 'grand_pd', 'grand_cost', 'grand_billable',
     *     'day_totals' => [iso => [st, ot, pd]],
     *   ]
     */
    protected function buildWeeklyTimesheetMatrix(?int $projectId, ?string $weekEndingInput): array
    {
        // Default week ending = upcoming Sunday (or today if today is Sunday).
        $weekEnding = $weekEndingInput
            ? \Carbon\Carbon::parse($weekEndingInput)->startOfDay()
            : \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SUNDAY)->startOfDay();

        // Build Mon..Sun ending on the chosen Sunday.
        $weekStart = $weekEnding->copy()->subDays(6);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->copy()->addDays($i);
        }

        $project = $projectId ? Project::with('client')->find($projectId) : null;

        // Zero-initialized day totals template keyed by ISO date.
        $zeroDays = [];
        foreach ($days as $d) {
            $zeroDays[$d->format('Y-m-d')] = ['st' => 0.0, 'ot' => 0.0, 'pd' => 0.0];
        }

        $shifts = [];
        $grandDayTotals = $zeroDays;
        $grandSt = 0.0; $grandOt = 0.0; $grandPd = 0.0; $grandCost = 0.0; $grandBillable = 0.0;

        if ($project) {
            $query = Timesheet::with(['employee.craft', 'shift', 'costAllocations'])
                ->where('project_id', $project->id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnding->toDateString()]);

            $timesheets = $query->get();

            // Pivot: shift_name → employee_id → date_iso → [st, ot, pd]
            foreach ($timesheets->groupBy(fn ($t) => $t->shift->name ?? 'UNASSIGNED') as $shiftName => $shiftRows) {
                $shiftEmployees = [];
                $shiftDayTotals = $zeroDays;
                $shiftSt = 0.0; $shiftOt = 0.0; $shiftPd = 0.0; $shiftCost = 0.0; $shiftBillable = 0.0;
                $multiplier = optional($shiftRows->first()->shift)->multiplier ?? 1.0;

                foreach ($shiftRows->groupBy('employee_id') as $empId => $empRows) {
                    $emp = $empRows->first()->employee;
                    $empName = $emp ? trim($emp->first_name . ' ' . $emp->last_name) : 'Unknown';
                    $classification = $emp->classification
                        ?? ($emp->craft->name ?? $emp->legacy_craft ?? '');

                    $empDays = $zeroDays;
                    $empSt = 0.0; $empOt = 0.0; $empPd = 0.0; $empCost = 0.0; $empBillable = 0.0;

                    foreach ($empRows as $t) {
                        $iso = $t->date?->format('Y-m-d');
                        if (!$iso || !isset($empDays[$iso])) continue;

                        $st = (float) $t->regular_hours;
                        $ot = (float) $t->overtime_hours + (float) $t->double_time_hours;
                        // Per-diem lives on cost allocations (sum per timesheet).
                        $pd = (float) $t->costAllocations->sum('per_diem_amount');

                        $empDays[$iso]['st'] += $st;
                        $empDays[$iso]['ot'] += $ot;
                        $empDays[$iso]['pd'] += $pd;
                        $shiftDayTotals[$iso]['st'] += $st;
                        $shiftDayTotals[$iso]['ot'] += $ot;
                        $shiftDayTotals[$iso]['pd'] += $pd;
                        $grandDayTotals[$iso]['st'] += $st;
                        $grandDayTotals[$iso]['ot'] += $ot;
                        $grandDayTotals[$iso]['pd'] += $pd;

                        $empSt += $st;
                        $empOt += $ot;
                        $empPd += $pd;
                        $empCost += (float) $t->total_cost;
                        $empBillable += (float) $t->billable_amount;
                    }

                    $shiftEmployees[] = [
                        'id' => $empId,
                        'name' => $empName,
                        'classification' => $classification,
                        'days' => $empDays,
                        'st_total' => $empSt,
                        'ot_total' => $empOt,
                        'pd_total' => $empPd,
                        'cost' => $empCost,
                        'billable' => $empBillable,
                    ];

                    $shiftSt += $empSt;
                    $shiftOt += $empOt;
                    $shiftPd += $empPd;
                    $shiftCost += $empCost;
                    $shiftBillable += $empBillable;
                }

                // Sort employees alphabetically for a predictable layout.
                usort($shiftEmployees, fn ($a, $b) => strcmp($a['name'], $b['name']));

                $shifts[$shiftName] = [
                    'multiplier' => (float) $multiplier,
                    'employees' => $shiftEmployees,
                    'day_totals' => $shiftDayTotals,
                    'shift_st' => $shiftSt,
                    'shift_ot' => $shiftOt,
                    'shift_pd' => $shiftPd,
                    'shift_cost' => $shiftCost,
                    'shift_billable' => $shiftBillable,
                ];

                $grandSt += $shiftSt;
                $grandOt += $shiftOt;
                $grandPd += $shiftPd;
                $grandCost += $shiftCost;
                $grandBillable += $shiftBillable;
            }

            // Stable shift ordering: DAY first, NIGHT second, others by name.
            uksort($shifts, function ($a, $b) {
                $rank = fn ($n) => str_contains(strtoupper($n), 'DAY') ? 0
                    : (str_contains(strtoupper($n), 'NIGHT') ? 1 : 2);
                return [$rank($a), $a] <=> [$rank($b), $b];
            });
        }

        return [
            'project' => $project,
            'week_ending' => $weekEnding,
            'week_start' => $weekStart,
            'days' => $days,
            'shifts' => $shifts,
            'day_totals' => $grandDayTotals,
            'grand_st' => $grandSt,
            'grand_ot' => $grandOt,
            'grand_pd' => $grandPd,
            'grand_cost' => $grandCost,
            'grand_billable' => $grandBillable,
        ];
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

        // Cost = invoices + uninvoiced commitments + labor. Invoices replace
        // commitments they're billed against, so we strip those commitments to
        // avoid double-counting. Labor timesheets are always additive.
        $invoicedCommitmentIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uninvoicedCommitments = $commitments->whereNotIn('id', $invoicedCommitmentIds);
        $laborCost = (float) $project->timesheets()
            ->where('status', '!=', 'rejected')
            ->sum('total_cost');
        $totalCosts = (float) $invoices->sum('amount')
            + (float) $uninvoicedCommitments->sum('amount')
            + $laborCost;

        $margin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? round(($margin / $totalRevenue) * 100, 2) : 0;

        // Per-row cost breakdown via the same cost-type-grouped helper used by
        // the cost & forecast reports. `buildCostTypeBreakdown()` returns rows
        // with header/subtotal markers so the P&L table can show the same
        // Budget → Cost Type → Phase Code hierarchy.
        $breakdownRows = $this->buildCostTypeBreakdown($project);

        // Revenue is not tracked per phase code (billing invoices are project-
        // level). Distribute proportionally by each detail row's cost share so
        // the P&L per-row revenue still sums to the project total revenue.
        $detailRows = array_values(array_filter(
            $breakdownRows,
            fn ($r) => ($r['is_header'] ?? false) === false && ($r['is_group_total'] ?? false) === false
        ));
        $sumCost = (float) array_sum(array_column($detailRows, 'committed')) ?: 0.0;

        $plData = collect();
        foreach ($breakdownRows as $r) {
            // Preserve header + subtotal rows with zero revenue — the view
            // renders them with different styling.
            $cost = (float) ($r['committed'] ?? 0);
            $isStructural = ($r['is_header'] ?? false) || ($r['is_group_total'] ?? false);
            $revenue = $isStructural
                ? 0.0
                : ($sumCost > 0 ? ($cost / $sumCost) * $totalRevenue : 0.0);

            $plData->push([
                'code'           => $r['code'] ?? '',
                'name'           => $r['name'] ?? '',
                'cost_type'      => $r['cost_type'] ?? '',
                'revenue'        => $revenue,
                'cost'           => $cost,
                'is_header'      => $r['is_header'] ?? false,
                'is_group_total' => $r['is_group_total'] ?? false,
                'indent'         => $r['indent'] ?? false,
            ]);
        }

        return view('reports.profit-loss', [
            'project' => $project,
            'totalRevenue' => $totalRevenue,
            'totalCosts' => $totalCosts,
            'margin' => $margin,
            'marginPercentage' => $marginPercentage,
            'byCodeData' => $breakdownRows,
            'plData' => $plData,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_cost'    => $totalCosts,
                'labor_cost'    => $laborCost,
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

        // 2026-05-01 BUG FIX (Brenda): "the cost reports and the forecast
        // reports are not working". Eloquent's Collection::merge() assumes
        // items are models and calls ->getKey() on each — when both sides
        // are scalar string keys (cost code IDs normalized to strings), it
        // blows up with "Call to a member function getKey() on string".
        // Coerce to base Collections via collect(...->all()) so the plain
        // Illuminate\Support\Collection::merge() runs and concatenates the
        // string lists correctly.
        $keys = collect($manhourBudgets->map(fn ($b) => $normalizeCcKey($b->cost_code_id))->all())
            ->merge($timesheets->map(fn ($t) => $normalizeCcKey($t->cost_code_id))->all())
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
            // Labor cost is folded in so booking timesheets against a cost code
            // moves its % complete — matches the dashboard's new committed calc.
            $budgetDollars = (float) ($budgetLines->get($ccId)?->sum('amount') ?? 0);
            $committedDollars = (float) ($commitments->get($ccId)?->sum('amount') ?? 0)
                + (float) ($invoices->get($ccId)?->sum('amount') ?? 0)
                + $laborCost;

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

    /**
     * Per-cost-code manhour forecast rows for the Forecast Report's "Man Hour
     * Data" table.
     *
     * Returns one row per cost code that has either a manhour budget or any
     * timesheet activity, with the keys the forecast view reads directly:
     *   - code, name (phase code label)
     *   - actual_hours (non-rejected timesheet hours)
     *   - budget_hours (from ManhourBudget, used as forecast fallback)
     *   - forecast_hours (max of budget vs actual — at least cover actual burn)
     *   - labor_cost
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getManHourForecastData(Project $project, array $filters): array
    {
        $normalizeCcKey = static fn ($id): string => $id === null ? '_null_' : (string) $id;

        $query = Timesheet::where('project_id', $project->id)
            ->where('status', '!=', 'rejected')
            ->with('costCode');
        if ($filters['date_from'] ?? null) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] ?? null) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        $timesheets = $query->get();
        $byCc = $timesheets->groupBy(fn ($t) => $normalizeCcKey($t->cost_code_id));

        $manhourBudgets = $project->manhourBudgets()->with('costCode')->get();

        // 2026-05-01 BUG FIX (Brenda): "the cost reports and the forecast
        // reports are not working". Eloquent's Collection::merge() assumes
        // items are models and calls ->getKey() on each — when both sides
        // are scalar string keys (cost code IDs normalized to strings), it
        // blows up with "Call to a member function getKey() on string".
        // Coerce to base Collections via collect(...->all()) so the plain
        // Illuminate\Support\Collection::merge() runs and concatenates the
        // string lists correctly.
        $keys = collect($manhourBudgets->map(fn ($b) => $normalizeCcKey($b->cost_code_id))->all())
            ->merge($timesheets->map(fn ($t) => $normalizeCcKey($t->cost_code_id))->all())
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        foreach ($keys as $key) {
            $ccId = $key === '_null_' ? null : (int) $key;
            $group = $byCc->get($key, collect());

            $actualHours = (float) $group->sum('total_hours');
            $laborCost   = (float) $group->sum('total_cost');
            $budgetHours = (float) $manhourBudgets
                ->where('cost_code_id', $ccId)
                ->sum('budget_hours');

            // Forecast = max(budget, actual). If we're burning over budget, the
            // honest forecast is at least the hours already worked; otherwise
            // we trust the original budget as the completion target.
            $forecastHours = max($budgetHours, $actualHours);

            $budget = $manhourBudgets->firstWhere('cost_code_id', $ccId);
            $firstTs = $group->first();
            $code = $budget?->costCode?->code
                ?? $firstTs?->costCode?->code
                ?? ($ccId === null ? 'Unassigned' : 'N/A');
            $name = $budget?->costCode?->name
                ?? $firstTs?->costCode?->name
                ?? ($ccId === null ? 'Unassigned' : 'N/A');

            $rows[] = [
                'code'           => $code,
                'name'           => $name,
                'actual_hours'   => $actualHours,
                'budget_hours'   => $budgetHours,
                'forecast_hours' => $forecastHours,
                'labor_cost'     => $laborCost,
            ];
        }

        return $rows;
    }

    protected function getTimesheetsForPeriod(Project $project, array $filters): \Illuminate\Support\Collection
    {
        $query = Timesheet::where('project_id', $project->id);

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

        $costCodeData = $this->buildCostTypeBreakdown($project);
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

        $originalBudgetTotal = (float) $project->budgetLines()->sum('budget_amount');
        $approvedCoTotal = (float) $project->changeOrders()->where('status', 'approved')->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        $costCodeData = $this->buildCostTypeBreakdown($project);
        foreach ($costCodeData as $i => $r) {
            $costCodeData[$i]['original_budget'] = $r['budget'];
            $costCodeData[$i]['forecast_budget'] = $r['budget'];
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
        // Match the on-screen P&L: include sent/partial invoices, not only paid.
        $billingInvoices = BillingInvoice::where('project_id', $project->id)
            ->whereIn('status', ['sent', 'paid', 'partial'])
            ->get();
        $totalRevenue = (float) $billingInvoices->sum('total_amount');

        $commitments = $project->commitments()->get();
        $invoices = $project->invoices()->get();
        $invoicedCommitmentIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uninvoicedCommitments = $commitments->whereNotIn('id', $invoicedCommitmentIds);
        $laborCost = (float) $project->timesheets()
            ->where('status', '!=', 'rejected')
            ->sum('total_cost');
        $totalCosts = (float) $invoices->sum('amount')
            + (float) $uninvoicedCommitments->sum('amount')
            + $laborCost;
        $margin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? round(($margin / $totalRevenue) * 100, 2) : 0;

        $byCodeData = $this->buildCostTypeBreakdown($project);

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
            'group_by' => 'nullable|in:employee,project,date,weekly',
            'week_ending' => 'nullable|date',
        ]);

        $groupBy = $validated['group_by'] ?? 'employee';

        // Weekly matrix — dedicated PDF layout to match the WEEKLY TIMESHEET sheet.
        if ($groupBy === 'weekly') {
            $weekly = $this->buildWeeklyTimesheetMatrix(
                $validated['project_id'] ?? null,
                $validated['week_ending'] ?? null
            );

            $pdf = Pdf::loadView('pdf.weekly-timesheet', compact('weekly'));
            $pdf->setPaper('a4', 'landscape');
            $suffix = $weekly['week_ending']->format('Y-m-d');

            return $pdf->download("weekly-timesheet-{$suffix}.pdf");
        }

        $query = Timesheet::with(['employee', 'project']);

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

        $breakdown = $this->buildCostTypeBreakdown($project);

        // CSV output mirrors the on-screen hierarchy: cost-type header row,
        // phase-code detail rows, subtotal row per cost type, then a grand total.
        $header = ['Cost Type', 'Phase Code', 'Name', 'Budget', 'Committed', 'Invoiced', 'Balance', '% Complete'];
        $rows = [];
        $grandTotals = ['budget' => 0.0, 'committed' => 0.0, 'invoiced' => 0.0, 'balance' => 0.0];

        foreach ($breakdown as $r) {
            if ($r['is_header'] ?? false) {
                $rows[] = [strtoupper($r['cost_type'] ?? $r['name']), '', '', '', '', '', '', ''];
                continue;
            }
            if ($r['is_group_total'] ?? false) {
                $pct = $r['budget'] > 0 ? round(($r['committed'] / $r['budget']) * 100, 2) : 0;
                $rows[] = [
                    '',
                    'Subtotal',
                    $r['cost_type'] ?? '',
                    number_format($r['budget'], 2, '.', ''),
                    number_format($r['committed'], 2, '.', ''),
                    number_format($r['invoiced'], 2, '.', ''),
                    number_format($r['balance'], 2, '.', ''),
                    $pct,
                ];
                $rows[] = []; // blank spacer
                continue;
            }
            // Detail row
            $pct = $r['budget'] > 0 ? round(($r['committed'] / $r['budget']) * 100, 2) : 0;
            $rows[] = [
                $r['cost_type'] ?? '',
                $r['code'],
                $r['name'],
                number_format($r['budget'], 2, '.', ''),
                number_format($r['committed'], 2, '.', ''),
                number_format($r['invoiced'], 2, '.', ''),
                number_format($r['balance'], 2, '.', ''),
                $pct,
            ];
            foreach ($grandTotals as $k => $_) {
                $grandTotals[$k] += (float) $r[$k];
            }
        }

        $grandPct = $grandTotals['budget'] > 0
            ? round(($grandTotals['committed'] / $grandTotals['budget']) * 100, 2)
            : 0;
        $rows[] = [
            '', 'GRAND TOTAL', '',
            number_format($grandTotals['budget'], 2, '.', ''),
            number_format($grandTotals['committed'], 2, '.', ''),
            number_format($grandTotals['invoiced'], 2, '.', ''),
            number_format($grandTotals['balance'], 2, '.', ''),
            $grandPct,
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

        $originalBudgetTotal = (float) $project->budgetLines()->sum('budget_amount');
        $approvedCoTotal = (float) $project->changeOrders()->where('status', 'approved')->sum('amount');
        $forecastBudgetTotal = $originalBudgetTotal + $approvedCoTotal;

        $breakdown = $this->buildCostTypeBreakdown($project);

        $header = ['Cost Type', 'Phase Code', 'Name', 'Original Budget', 'Forecast Budget', 'Committed', 'Invoiced', 'Variance (Budget - Committed)'];
        $rows = [];
        $grandTotals = ['original_budget' => 0.0, 'forecast_budget' => 0.0, 'committed' => 0.0, 'invoiced' => 0.0, 'balance' => 0.0];

        foreach ($breakdown as $r) {
            if ($r['is_header'] ?? false) {
                $rows[] = [strtoupper($r['cost_type'] ?? $r['name']), '', '', '', '', '', '', ''];
                continue;
            }
            if ($r['is_group_total'] ?? false) {
                $rows[] = [
                    '',
                    'Subtotal',
                    $r['cost_type'] ?? '',
                    number_format($r['budget'], 2, '.', ''),
                    number_format($r['budget'], 2, '.', ''),
                    number_format($r['committed'], 2, '.', ''),
                    number_format($r['invoiced'], 2, '.', ''),
                    number_format($r['balance'], 2, '.', ''),
                ];
                $rows[] = [];
                continue;
            }
            $rows[] = [
                $r['cost_type'] ?? '',
                $r['code'],
                $r['name'],
                number_format($r['budget'], 2, '.', ''),
                number_format($r['budget'], 2, '.', ''),
                number_format($r['committed'], 2, '.', ''),
                number_format($r['invoiced'], 2, '.', ''),
                number_format($r['balance'], 2, '.', ''),
            ];
            $grandTotals['original_budget'] += (float) $r['budget'];
            $grandTotals['forecast_budget'] += (float) $r['budget'];
            $grandTotals['committed']       += (float) $r['committed'];
            $grandTotals['invoiced']        += (float) $r['invoiced'];
            $grandTotals['balance']         += (float) $r['balance'];
        }

        $rows[] = [
            '', 'GRAND TOTAL', '',
            number_format($grandTotals['original_budget'], 2, '.', ''),
            number_format($grandTotals['forecast_budget'], 2, '.', ''),
            number_format($grandTotals['committed'], 2, '.', ''),
            number_format($grandTotals['invoiced'], 2, '.', ''),
            number_format($grandTotals['balance'], 2, '.', ''),
        ];
        $rows[] = [];
        $rows[] = ['Approved Change Orders', '', '', '', number_format($approvedCoTotal, 2, '.', ''), '', '', ''];
        $rows[] = ['Forecast Total', '', '', number_format($originalBudgetTotal, 2, '.', ''), number_format($forecastBudgetTotal, 2, '.', ''), '', '', ''];

        $filename = "forecast-{$project->project_number}-" . now()->format('Ymd') . '.csv';
        return $this->streamCsv($filename, $header, $rows);
    }

    /**
     * Cost Report drill-down — Brenda's cost controller 2026-05-01.
     *
     *   "Cost controller wants to know if all reports can have a drill down
     *    to see where the data is coming from."
     *
     * Click a Committed/Invoiced number on the cost report → see the
     * underlying records (POs, timesheets, vendor invoices) that make up
     * that total. Returns JSON keyed by source type so the front-end can
     * render section headers per source.
     *
     * Query params:
     *   bucket          'committed' | 'invoiced'   (required)
     *   cost_code_id    int | 'all'                (defaults to 'all')
     *   cost_type_id    int | null
     */
    public function costReportDrill(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'bucket'       => 'required|in:committed,invoiced',
            'cost_code_id' => 'nullable',
            'cost_type_id' => 'nullable',
        ]);

        $bucket  = $request->query('bucket');
        $ccId    = $request->query('cost_code_id');
        $ccId    = ($ccId === '' || $ccId === 'all' || $ccId === null) ? null : (int) $ccId;
        $ctId    = $request->query('cost_type_id');
        $ctId    = ($ctId === '' || $ctId === null) ? null : (int) $ctId;

        $sections = [];

        if ($bucket === 'committed') {
            // 1) Vendor commitments (POs) ----------------------------------
            $coms = $project->commitments()
                ->with(['vendor:id,name', 'costCode:id,code,name', 'costType:id,code,name'])
                ->when($ccId !== null, fn ($q) => $q->where('cost_code_id', $ccId))
                ->when($ctId !== null, fn ($q) => $q->where('cost_type_id', $ctId))
                ->orderByDesc('created_at')
                ->get();

            if ($coms->isNotEmpty()) {
                $sections[] = [
                    'title'  => 'Purchase Orders / Commitments',
                    'count'  => $coms->count(),
                    'total'  => round((float) $coms->sum('amount'), 2),
                    'rows'   => $coms->map(fn ($c) => [
                        'date'        => optional($c->created_at)->format('Y-m-d'),
                        'reference'   => $c->po_number ?? ('Commitment #' . $c->id),
                        'description' => trim(($c->description ?? '') . ($c->vendor ? ' — ' . $c->vendor->name : '')) ?: '—',
                        'cost_code'   => $c->costCode?->code,
                        'cost_type'   => $c->costType?->code,
                        'amount'      => round((float) $c->amount, 2),
                        'link'        => route('purchase-orders.show', $c),
                    ])->all(),
                ];
            }

            // 2) Labor (timesheets, non-rejected) --------------------------
            $tsQuery = Timesheet::query()
                ->where('project_id', $project->id)
                ->where('status', '!=', 'rejected')
                ->with(['employee:id,first_name,last_name,employee_number', 'costCode:id,code,name', 'costType:id,code,name'])
                ->when($ccId !== null, fn ($q) => $q->where('cost_code_id', $ccId))
                ->when($ctId !== null, fn ($q) => $q->where('cost_type_id', $ctId))
                ->orderByDesc('date');
            $timesheets = $tsQuery->get();

            if ($timesheets->isNotEmpty()) {
                $sections[] = [
                    'title' => 'Labor (Timesheets)',
                    'count' => $timesheets->count(),
                    'total' => round((float) $timesheets->sum('total_cost'), 2),
                    'rows'  => $timesheets->map(fn ($t) => [
                        'date'        => optional($t->date)->format('Y-m-d'),
                        'reference'   => 'Timesheet #' . $t->id,
                        'description' => $t->employee
                            ? trim($t->employee->first_name . ' ' . $t->employee->last_name) . ' (' . $t->total_hours . ' hrs)'
                            : '—',
                        'cost_code'   => $t->costCode?->code,
                        'cost_type'   => $t->costType?->code,
                        'amount'      => round((float) $t->total_cost, 2),
                        'link'        => route('timesheets.show', $t),
                    ])->all(),
                ];
            }

            // 3) Per Diem allocations --------------------------------------
            $perDiems = \App\Models\TimesheetCostAllocation::query()
                ->whereHas('timesheet', fn ($q) => $q->where('project_id', $project->id)->where('status', '!=', 'rejected'))
                ->where('per_diem_amount', '>', 0)
                ->when($ccId !== null, fn ($q) => $q->where('cost_code_id', $ccId))
                ->with(['timesheet.employee:id,first_name,last_name', 'costCode:id,code,name'])
                ->get();

            if ($perDiems->isNotEmpty()) {
                $sections[] = [
                    'title' => 'Per Diem',
                    'count' => $perDiems->count(),
                    'total' => round((float) $perDiems->sum('per_diem_amount'), 2),
                    'rows'  => $perDiems->map(fn ($p) => [
                        'date'        => optional($p->timesheet?->date)->format('Y-m-d'),
                        'reference'   => 'Timesheet #' . $p->timesheet_id,
                        'description' => $p->timesheet?->employee
                            ? trim($p->timesheet->employee->first_name . ' ' . $p->timesheet->employee->last_name)
                            : '—',
                        'cost_code'   => $p->costCode?->code,
                        'cost_type'   => 'PER DIEM',
                        'amount'      => round((float) $p->per_diem_amount, 2),
                        'link'        => $p->timesheet_id ? route('timesheets.show', $p->timesheet_id) : null,
                    ])->all(),
                ];
            }
        } else {
            // bucket=invoiced — vendor invoices
            $invs = Invoice::query()
                ->where('project_id', $project->id)
                ->with(['vendor:id,name', 'costCode:id,code,name'])
                ->when($ccId !== null, fn ($q) => $q->where('cost_code_id', $ccId))
                ->orderByDesc('invoice_date')
                ->get();

            if ($invs->isNotEmpty()) {
                $sections[] = [
                    'title' => 'Vendor Invoices',
                    'count' => $invs->count(),
                    'total' => round((float) $invs->sum('amount'), 2),
                    'rows'  => $invs->map(fn ($i) => [
                        'date'        => optional($i->invoice_date)->format('Y-m-d'),
                        'reference'   => $i->invoice_number ?? ('Invoice #' . $i->id),
                        'description' => ($i->vendor?->name ?? '—') . ($i->description ? ' — ' . $i->description : ''),
                        'cost_code'   => $i->costCode?->code,
                        'cost_type'   => null,
                        'amount'      => round((float) $i->amount, 2),
                        'link'        => null,
                    ])->all(),
                ];
            }
        }

        return response()->json([
            'project_id'    => $project->id,
            'project_name'  => $project->name,
            'bucket'        => $bucket,
            'cost_code_id'  => $ccId,
            'cost_type_id'  => $ctId,
            'sections'      => $sections,
            'grand_total'   => round(array_sum(array_column($sections, 'total')), 2),
        ]);
    }
}
