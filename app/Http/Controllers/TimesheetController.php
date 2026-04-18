<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\TimesheetCostAllocation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use App\Models\Crew;
use App\Models\Shift;
use App\Models\CostCode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class TimesheetController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        return view('timesheets.index', $this->timesheetFormOptions());
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Timesheet::with(['employee', 'project', 'crew', 'costCode']);

        // Apply filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Get total records count before filtering
        $totalRecords = Timesheet::count();

        // Apply search (DataTables sends 'search[value]')
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('employee', function ($eq) use ($searchValue) {
                    $eq->where('first_name', 'like', "%{$searchValue}%")
                        ->orWhere('last_name', 'like', "%{$searchValue}%");
                })->orWhereHas('project', function ($pq) use ($searchValue) {
                    $pq->where('name', 'like', "%{$searchValue}%");
                })->orWhereHas('crew', function ($cq) use ($searchValue) {
                    $cq->where('name', 'like', "%{$searchValue}%");
                })->orWhere('status', 'like', "%{$searchValue}%");
            });
        }

        // Get filtered records count
        $recordsFiltered = $query->count();

        // Ordering (columns match index DataTable: date, employee, project, cost code, crew, reg, ot, dt, total, cost, status, actions)
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = [
            0 => 'date',
            1 => 'employee_id',
            2 => 'project_id',
            3 => 'cost_code_id',
            4 => 'crew_id',
            5 => 'regular_hours',
            6 => 'overtime_hours',
            7 => 'double_time_hours',
            8 => 'total_hours',
            9 => 'total_cost',
            10 => 'status',
        ];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('date', 'desc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $timesheets = $query->offset($start)->limit($length)->get();

        // Format data for DataTables (must match resources/views/timesheets/index.blade.php columns)
        $data = $timesheets->map(function ($timesheet) {
            $emp = $timesheet->employee;

            return [
                'id' => $timesheet->id,
                'employee_id' => $timesheet->employee_id,
                'employee_name' => $emp ? trim($emp->first_name.' '.$emp->last_name) : '—',
                'project_id' => $timesheet->project_id,
                'project_name' => $timesheet->project->name ?? '',
                'cost_code' => $timesheet->costCode?->code ?? '—',
                'crew_id' => $timesheet->crew_id,
                'crew_name' => $timesheet->crew->name ?? '—',
                'date' => $timesheet->date,
                'shift_id' => $timesheet->shift_id,
                'regular_hours' => $timesheet->regular_hours,
                'overtime_hours' => $timesheet->overtime_hours,
                'double_time_hours' => $timesheet->double_time_hours,
                'total_hours' => $timesheet->total_hours,
                'cost' => $timesheet->total_cost,
                'rate_type' => $timesheet->rate_type ?? 'standard',
                'status' => $timesheet->status,
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($request->input('draw', 0)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'required|exists:projects,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'crew_id' => 'nullable|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'regular_hours' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'double_time_hours' => 'nullable|numeric|min:0',
            'gate_log_hours' => 'nullable|numeric|min:0',
            'work_through_lunch' => 'nullable|boolean',
            'is_billable' => 'nullable|boolean',
            'client_signature' => 'nullable|string',
            'client_signature_name' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $reg = (float) $validated['regular_hours'];
        $ot = (float) ($validated['overtime_hours'] ?? 0);
        $dt = (float) ($validated['double_time_hours'] ?? 0);
        $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);
        // Billable flag: default true on create (hours exist to bill), honor explicit unchecked box
        $isBillable = $request->has('is_billable') ? $request->boolean('is_billable') : true;

        $timesheet = Timesheet::create([
            'employee_id' => $validated['employee_id'],
            'project_id' => $validated['project_id'],
            'cost_code_id' => $validated['cost_code_id'] ?? null,
            'cost_type_id' => $validated['cost_type_id'] ?? null,
            'crew_id' => $validated['crew_id'] ?? null,
            'date' => $validated['date'],
            'shift_id' => $validated['shift_id'] ?? null,
            'gate_log_hours' => $validated['gate_log_hours'] ?? null,
            'work_through_lunch' => $request->boolean('work_through_lunch'),
            'client_signature' => $validated['client_signature'] ?? null,
            'client_signature_name' => $validated['client_signature_name'] ?? null,
            'signed_at' => !empty($validated['client_signature']) ? now() : null,
            'regular_hours' => $reg,
            'overtime_hours' => $ot,
            'double_time_hours' => $dt,
            'total_hours' => $totals['total_hours'],
            'regular_rate' => $totals['regular_rate'],
            'overtime_rate' => $totals['overtime_rate'],
            'total_cost' => $totals['total_cost'],
            'billable_rate' => $totals['billable_rate'],
            'billable_amount' => $isBillable ? $totals['billable_amount'] : 0,
            'is_billable' => $isBillable,
            'rate_type' => $totals['rate_type'],
            'project_billable_rate_id' => $totals['project_billable_rate_id'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncTimesheetCostAllocation($timesheet->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Timesheet created successfully.',
            'timesheet' => $timesheet,
        ], 201);
    }

    public function show(Timesheet $timesheet): View
    {
        $timesheet->load(['employee', 'project', 'crew', 'shift', 'costCode', 'costAllocations.costCode']);

        return view('timesheets.show', array_merge(
            ['timesheet' => $timesheet],
            $this->timesheetFormOptions()
        ));
    }

    public function edit(Timesheet $timesheet): JsonResponse
    {
        $timesheet->load(['employee', 'project', 'crew']);

        return response()->json($timesheet);
    }

    /**
     * @return array{employees: \Illuminate\Database\Eloquent\Collection, projects: \Illuminate\Database\Eloquent\Collection, crews: \Illuminate\Database\Eloquent\Collection, shifts: \Illuminate\Database\Eloquent\Collection}
     */
    private function timesheetFormOptions(): array
    {
        return [
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'crews' => Crew::query()->with('project')->orderBy('name')->get(),
            'shifts' => Shift::query()->orderBy('name')->get(),
            'costCodes' => CostCode::query()->orderBy('code')->get(['id', 'code', 'name']),
            'costTypes' => \App\Models\CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
        ];
    }

    public function update(Request $request, Timesheet $timesheet): JsonResponse
    {
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'required|exists:projects,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'crew_id' => 'nullable|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'regular_hours' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'double_time_hours' => 'nullable|numeric|min:0',
            'gate_log_hours' => 'nullable|numeric|min:0',
            'work_through_lunch' => 'nullable|boolean',
            'is_billable' => 'nullable|boolean',
            'client_signature' => 'nullable|string',
            'client_signature_name' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $reg = (float) $validated['regular_hours'];
        $ot = (float) ($validated['overtime_hours'] ?? 0);
        $dt = (float) ($validated['double_time_hours'] ?? 0);
        $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);
        // Honor the "Billable" checkbox the user explicitly sent. If the field
        // wasn't submitted at all (e.g. a partial update), keep the previous value.
        $isBillable = $request->has('is_billable')
            ? $request->boolean('is_billable')
            : (bool) $timesheet->is_billable;

        $timesheet->update([
            'employee_id' => $validated['employee_id'],
            'project_id' => $validated['project_id'],
            'cost_code_id' => $validated['cost_code_id'] ?? null,
            'cost_type_id' => $validated['cost_type_id'] ?? null,
            'crew_id' => $validated['crew_id'] ?? null,
            'date' => $validated['date'],
            'shift_id' => $validated['shift_id'] ?? null,
            'gate_log_hours' => $validated['gate_log_hours'] ?? null,
            'work_through_lunch' => $request->boolean('work_through_lunch'),
            'client_signature' => $validated['client_signature'] ?? $timesheet->client_signature,
            'client_signature_name' => $validated['client_signature_name'] ?? $timesheet->client_signature_name,
            'signed_at' => !empty($validated['client_signature']) && $timesheet->signed_at === null ? now() : $timesheet->signed_at,
            'regular_hours' => $reg,
            'overtime_hours' => $ot,
            'double_time_hours' => $dt,
            'total_hours' => $totals['total_hours'],
            'regular_rate' => $totals['regular_rate'],
            'overtime_rate' => $totals['overtime_rate'],
            'total_cost' => $totals['total_cost'],
            'billable_rate' => $totals['billable_rate'],
            'billable_amount' => $isBillable ? $totals['billable_amount'] : 0,
            'is_billable' => $isBillable,
            'rate_type' => $totals['rate_type'],
            'project_billable_rate_id' => $totals['project_billable_rate_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncTimesheetCostAllocation($timesheet->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Timesheet updated successfully.',
            'timesheet' => $timesheet->fresh(),
        ]);
    }

    /**
     * Keep a single allocation row in sync so payroll and reports can resolve cost code by hours.
     * Auto-fills per_diem_amount from the project's default_per_diem_rate if set.
     */
    private function syncTimesheetCostAllocation(Timesheet $timesheet): void
    {
        $timesheet->costAllocations()->delete();
        if ($timesheet->cost_code_id) {
            $perDiem = 0;
            if ($timesheet->project_id) {
                $rate = $timesheet->project?->default_per_diem_rate ?? 0;
                // Apply per diem only for days the employee actually worked (total_hours > 0)
                $perDiem = $timesheet->total_hours > 0 ? (float) $rate : 0;
            }
            TimesheetCostAllocation::create([
                'timesheet_id' => $timesheet->id,
                'cost_code_id' => $timesheet->cost_code_id,
                'cost_type_id' => $timesheet->cost_type_id,
                'hours' => $timesheet->total_hours,
                'cost' => $timesheet->total_cost,
                'per_diem_amount' => $perDiem,
            ]);
        }
    }

    public function destroy(Timesheet $timesheet): JsonResponse
    {
        $timesheet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timesheet deleted successfully.',
        ]);
    }

    public function approve(Request $request, Timesheet $timesheet): JsonResponse
    {
        $timesheet->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet approved.',
            'timesheet' => $timesheet->fresh(),
        ]);
    }

    public function reject(Request $request, Timesheet $timesheet): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $notes = $timesheet->notes;
        if ($request->filled('rejection_reason')) {
            $notes = trim(($notes ? $notes."\n\n" : '').'Rejection: '.$request->input('rejection_reason'));
        }

        $timesheet->update([
            'status' => 'rejected',
            'notes' => $notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet rejected.',
            'timesheet' => $timesheet->fresh(),
        ]);
    }

    public function bulkCreate(Request $request): View
    {
        $crews = Crew::with(['project', 'foreman'])->get();
        $projects = Project::where('status', 'active')->get();
        $shifts = Shift::all();
        $costCodes = CostCode::query()->orderBy('code')->get(['id', 'code', 'name']);
        $costTypes = \App\Models\CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']);
        $crewMembers = collect();

        // Load crew members (employees) if crew_id is provided via query string
        if ($request->filled('crew_id')) {
            $crew = Crew::find($request->crew_id);
            if ($crew) {
                $crewMembers = $crew->employees;
            }
        }

        return view('timesheets.bulk-create', [
            'crews' => $crews,
            'projects' => $projects,
            'shifts' => $shifts,
            'crewMembers' => $crewMembers,
            'costCodes' => $costCodes,
            'costTypes' => $costTypes,
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'crew_id' => 'required|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'entries' => 'required|array|min:1',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.regular_hours' => 'required|numeric|min:0',
            'entries.*.overtime_hours' => 'required|numeric|min:0',
            'entries.*.double_time_hours' => 'required|numeric|min:0',
            'entries.*.gate_log_hours' => 'nullable|numeric|min:0',
            'entries.*.per_diem' => 'nullable|boolean',
            'entries.*.per_diem_amount' => 'nullable|numeric|min:0',
            'entries.*.work_through_lunch' => 'nullable|boolean',
            'entries.*.cost_type_id' => 'nullable|exists:cost_types,id',
        ]);

        $timesheets = [];

        // Default per diem rate for this project (used when the row checkbox
        // is ticked but the user didn't type a specific amount).
        $project = \App\Models\Project::find($validated['project_id']);
        $projectPerDiem = (float) ($project->default_per_diem_rate ?? 0);

        foreach ($validated['entries'] as $entry) {
            $employee = Employee::findOrFail($entry['employee_id']);
            $reg = (float) $entry['regular_hours'];
            $ot = (float) ($entry['overtime_hours'] ?? 0);
            $dt = (float) ($entry['double_time_hours'] ?? 0);
            $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);

            $timesheet = Timesheet::create([
                'employee_id' => $entry['employee_id'],
                'project_id' => $validated['project_id'],
                'cost_code_id' => $validated['cost_code_id'] ?? null,
                // Per-row cost_type_id wins; otherwise fall back to the
                // crew-level cost_type_id picked at the top of the form.
                'cost_type_id' => $entry['cost_type_id'] ?? $validated['cost_type_id'] ?? null,
                'crew_id' => $validated['crew_id'],
                'date' => $validated['date'],
                'shift_id' => $validated['shift_id'],
                'regular_hours' => $reg,
                'overtime_hours' => $ot,
                'double_time_hours' => $dt,
                'total_hours' => $totals['total_hours'],
                'gate_log_hours' => $entry['gate_log_hours'] ?? null,
                'work_through_lunch' => !empty($entry['work_through_lunch']),
                'regular_rate' => $totals['regular_rate'],
                'overtime_rate' => $totals['overtime_rate'],
                'total_cost' => $totals['total_cost'],
                'billable_rate' => $totals['billable_rate'],
                'billable_amount' => $totals['billable_amount'],
                'rate_type' => $totals['rate_type'],
                'project_billable_rate_id' => $totals['project_billable_rate_id'],
                'status' => 'draft',
            ]);

            $this->syncTimesheetCostAllocation($timesheet->fresh());

            // Override per_diem on the allocation if user supplied one for this row
            if (!empty($entry['per_diem']) || !empty($entry['per_diem_amount'])) {
                $amount = (float) ($entry['per_diem_amount'] ?? $projectPerDiem);
                $timesheet->costAllocations()->update(['per_diem_amount' => $amount]);
            }

            $timesheets[] = $timesheet;
        }

        // If traditional form POST (not AJAX), redirect back with success message
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('timesheets.index')
                ->with('success', count($timesheets) . ' timesheets created successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk timesheets created successfully.',
            'timesheets' => $timesheets,
            'count' => count($timesheets),
        ], 201);
    }

    /**
     * Find the best matching ProjectBillableRate for a given project, employee, and date.
     * Priority: employee-specific rate > craft-specific rate > null (use standard)
     */
    private function findProjectBillableRate(int $projectId, Employee $employee, string $date): ?ProjectBillableRate
    {
        // First try employee-specific rate for this project
        $rate = ProjectBillableRate::forProject($projectId)
            ->forEmployee($employee->id)
            ->effectiveOn($date)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate) {
            return $rate;
        }

        // Then try craft-specific rate for this project
        if ($employee->craft_id) {
            $rate = ProjectBillableRate::forProject($projectId)
                ->forCraft($employee->craft_id)
                ->whereNull('employee_id')
                ->effectiveOn($date)
                ->orderByDesc('effective_date')
                ->first();

            if ($rate) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Compute timesheet totals.
     *
     * Architecture (per client):
     *   - COST (what we pay, used in budgets/cost reports)
     *     = (wage + burden) × hours, always sourced from the employee:
     *         ST cost = (hourly_rate + st_burden_rate) × regular_hours
     *         OT cost = (overtime_rate + ot_burden_rate) × overtime_hours
     *         DT cost = (hourly_rate × 2 + ot_burden_rate) × dt_hours
     *
     *   - BILLABLE (what we charge the client)
     *     = Project Billable Rate if one exists for this project+employee/craft+date,
     *       otherwise falls back to employee's billable_rate × multipliers.
     *
     * @return array{total_hours: float, total_cost: float, regular_rate: string|float, overtime_rate: string|float, billable_rate: string|float, billable_amount: float, rate_type: string, project_billable_rate_id: int|null}
     */
    private function computeLaborTotals(Employee $employee, float $regularHours, float $overtimeHours, float $doubleTimeHours, int $projectId = 0, string $date = ''): array
    {
        $totalHours = $regularHours + $overtimeHours + $doubleTimeHours;

        // ── COST (employee's rate, or a project-specific override if one exists) ──
        // Per-project pay rates take precedence so an employee can be paid
        // different wages on different jobs.
        $projRate = null;
        if ($projectId) {
            $projRate = \App\Models\EmployeeProjectRate::where('project_id', $projectId)
                ->where('employee_id', $employee->id)
                ->when($date, function ($q) use ($date) {
                    $q->where(function ($qq) use ($date) {
                        $qq->whereNull('effective_date')->orWhere('effective_date', '<=', $date);
                    })->where(function ($qq) use ($date) {
                        $qq->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    });
                })
                ->orderByDesc('effective_date')
                ->first();
        }

        $stWage   = (float) ($projRate->hourly_rate   ?? $employee->hourly_rate);
        $otWage   = (float) ($projRate->overtime_rate ?? $employee->overtime_rate);
        $stBurden = (float) ($projRate->st_burden_rate ?? $employee->st_burden_rate ?? 0);
        $otBurden = (float) ($projRate->ot_burden_rate ?? $employee->ot_burden_rate ?? 0);

        $regularCost = $regularHours * ($stWage + $stBurden);
        $otCost      = $overtimeHours * ($otWage + $otBurden);
        $dtCost      = $doubleTimeHours * (($stWage * 2) + $otBurden);
        $totalCost   = $regularCost + $otCost + $dtCost;

        // ── BILLABLE (project rate if set, else employee billable_rate) ───
        $projectRate = null;
        if ($projectId && $date) {
            $projectRate = $this->findProjectBillableRate($projectId, $employee, $date);
        }

        if ($projectRate) {
            $stRate = (float) $projectRate->straight_time_rate;
            $otRate = (float) $projectRate->overtime_rate;
            $dtRate = (float) $projectRate->double_time_rate;
            $billableAmount = ($regularHours * $stRate)
                + ($overtimeHours * $otRate)
                + ($doubleTimeHours * $dtRate);

            return [
                'total_hours' => $totalHours,
                'total_cost' => $totalCost,
                'regular_rate' => $stWage,
                'overtime_rate' => $otWage,
                'billable_rate' => $stRate,
                'billable_amount' => $billableAmount,
                'rate_type' => 'loaded',
                'project_billable_rate_id' => $projectRate->id,
            ];
        }

        $bRate = (float) ($projRate->billable_rate ?? $employee->billable_rate ?? $employee->hourly_rate);
        $billableAmount = ($regularHours * $bRate)
            + ($overtimeHours * $bRate * 1.5)
            + ($doubleTimeHours * $bRate * 2);

        return [
            'total_hours' => $totalHours,
            'total_cost' => $totalCost,
            'regular_rate' => $employee->hourly_rate,
            'overtime_rate' => $employee->overtime_rate,
            'billable_rate' => $bRate,
            'billable_amount' => $billableAmount,
            'rate_type' => 'standard',
            'project_billable_rate_id' => null,
        ];
    }
}
