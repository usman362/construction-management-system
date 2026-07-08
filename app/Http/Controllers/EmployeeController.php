<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Craft;
use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        $crafts = Craft::all();

        return view('employees.index', [
            'crafts' => $crafts,
        ]);
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Employee::with(['craft']);

        // Get total records before filtering
        $totalRecords = Employee::count();

        // Apply search filter
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('employee_number', 'like', "%{$searchValue}%")
                  ->orWhere('legacy_employee_id', 'like', "%{$searchValue}%")
                  ->orWhere('first_name', 'like', "%{$searchValue}%")
                  ->orWhere('last_name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Apply craft filter
        if ($request->filled('craft_id')) {
            $query->where('craft_id', $request->craft_id);
        }

        // Get records after filtering
        $recordsFiltered = $query->count();

        // Apply sorting. Column indices must match what's rendered in the
        // DataTable, which now shifts when the Hourly Rate column is hidden
        // from non-rate-seeing users (2026-05-12). Build the map dynamically.
        $showRates = auth()->user()?->canSeeEmployeeRates();
        $columns = [
            0 => 'employee_number',
            1 => 'legacy_employee_id',
            2 => 'first_name',
            3 => 'email',
            4 => 'role',
            5 => 'craft_id',
        ];
        if ($showRates) {
            $columns[6] = 'hourly_rate';
            $columns[7] = 'status';
        } else {
            $columns[6] = 'status';
        }

        if ($request->has('order') && is_array($request->input('order'))) {
            $order = $request->input('order')[0];
            if (isset($columns[$order['column']])) {
                $dir = $order['dir'];
                if ((int) $order['column'] === 2) {
                    $query->orderBy('first_name', $dir)->orderBy('last_name', $dir);
                } else {
                    $query->orderBy($columns[$order['column']], $dir);
                }
            }
        } else {
            $query->orderBy('employee_number', 'asc');
        }

        // Apply pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $employees = $query->offset($start)->limit($length)->get();

        // Map data for DataTables
        $data = $employees->map(fn($employee) => [
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'legacy_employee_id' => $employee->legacy_employee_id,
            'first_name' => $employee->first_name,
            'middle_name' => $employee->middle_name,
            'last_name' => $employee->last_name,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'craft_name' => $employee->craft->name ?? '',
            'role' => $employee->role,
            'hourly_rate' => $employee->hourly_rate,
            'overtime_rate' => $employee->overtime_rate,
            'billable_rate' => $employee->billable_rate,
            'hire_date' => $employee->hire_date,
            'status' => $employee->status,
        ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'crafts' => Craft::orderBy('name')->get(),
            'costTypes' => CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // HTML <select> sends "" when the user leaves the dropdown on the
        // blank option, which breaks FK integer columns (SQLSTATE 1366).
        $this->normalizeNullableIds($request);
        $this->stripRateFieldsIfDisallowed($request);
        $validated = $request->validate($this->employeeRules());
        $validated['is_supervisor'] = $request->boolean('is_supervisor');
        $validated['certified_pay'] = $request->boolean('certified_pay');

        // Non-rate users never have the rate inputs on their form; default
        // rates to 0 on create so the NOT NULL columns are satisfied.
        if (!auth()->user()?->canSeeEmployeeRates()) {
            $validated['hourly_rate']   = $validated['hourly_rate']   ?? 0;
            $validated['overtime_rate'] = $validated['overtime_rate'] ?? 0;
            $validated['billable_rate'] = $validated['billable_rate'] ?? 0;
        }

        $employee = Employee::create($validated);

        // Full form (non-AJAX) redirects to show
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()
                ->route('employees.show', $employee)
                ->with('success', 'Employee created successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully.',
            'employee' => $employee,
        ], 201);
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'craft',
            'certifications',
            'documents.uploader',
            'projectRates' => fn($q) => $q->with('project')->orderByDesc('created_at'),
            'timesheets' => function ($q) {
                $q->with('project')->orderBy('date', 'desc')->limit(20);
            },
        ]);

        $totalHours = $employee->timesheets->sum('total_hours');
        $totalCost = $employee->timesheets->sum('total_cost');

        return view('employees.show', [
            'employee' => $employee,
            'allProjects' => \App\Models\Project::whereNotIn('status', ['closed', 'completed'])->orderBy('name')->get(['id', 'name', 'project_number']),
            'totalHours' => $totalHours,
            'totalCost' => $totalCost,
        ]);
    }

    public function edit(Request $request, Employee $employee): View|JsonResponse
    {
        $employee->load(['craft']);
        $crafts = Craft::all();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge(
                $employee->toArray(),
                ['crafts' => $crafts]
            ));
        }

        return view('employees.edit', [
            'employee' => $employee,
            'crafts' => $crafts,
            'costTypes' => CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $this->normalizeNullableIds($request);
        $this->stripRateFieldsIfDisallowed($request);
        $validated = $request->validate($this->employeeRules($employee->id));
        $validated['is_supervisor'] = $request->boolean('is_supervisor');
        $validated['certified_pay'] = $request->boolean('certified_pay');

        $employee->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'employee' => $employee->fresh(),
            ]);
        }

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.',
        ]);
    }

    /**
     * Empty "" from optional FK <select>s → null before validate/save.
     */
    private function normalizeNullableIds(Request $request): void
    {
        foreach (['craft_id', 'default_cost_type_id', 'rotation_group_id'] as $k) {
            if ($request->input($k) === '') {
                $request->merge([$k => null]);
            }
        }
    }

    /**
     * 2026-05-12 (Brenda): if the current user can't see employee rates,
     * strip every rate-related field from the incoming request before
     * validation. The form doesn't show those inputs to non-rate users —
     * this guard ensures a curl POST can't sneak in a rate change either.
     */
    private function stripRateFieldsIfDisallowed(Request $request): void
    {
        if (auth()->user()?->canSeeEmployeeRates()) {
            return;
        }
        foreach ([
            'hourly_rate', 'overtime_rate', 'billable_rate',
            'st_burden_rate', 'ot_burden_rate',
            'pay_cycle', 'pay_type',
        ] as $k) {
            $request->request->remove($k);
        }
    }

    /**
     * Shared validation rules for store/update. Pass $id to ignore current record on unique checks.
     */
    private function employeeRules(?int $id = null): array
    {
        $numberUnique = $id ? "unique:employees,employee_number,{$id}" : 'unique:employees,employee_number';
        $emailUnique  = $id ? "unique:employees,email,{$id}" : 'unique:employees,email';

        return [
            'employee_number'     => "required|string|max:50|{$numberUnique}",
            'legacy_employee_id'  => 'nullable|string|max:50',
            'legacy_position'     => 'nullable|string|max:100',
            'legacy_craft'        => 'nullable|string|max:100',
            'first_name'          => 'required|string|max:255',
            'middle_name'         => 'nullable|string|max:100',
            'last_name'           => 'required|string|max:255',
            'email'               => "nullable|email|{$emailUnique}",
            'phone'               => 'nullable|string|max:30',
            'address_1'           => 'nullable|string|max:255',
            'address_2'           => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:100',
            'state'               => 'nullable|string|max:50',
            'zip'                 => 'nullable|string|max:20',
            'home_phone'          => 'nullable|string|max:30',
            'work_cell'           => 'nullable|string|max:30',
            'personal_cell'       => 'nullable|string|max:30',
            'craft_id'            => 'nullable|exists:crafts,id',
            'role'                => 'required|in:field,foreman,superintendent,project_manager,admin,accounting',
            // Rates: required only when the current user is allowed to see
            // / set them (admin / PM / accountant). Non-rate users hit the
            // create + edit flows for cert management — their rate fields
            // are stripped from the request and defaulted to 0 on create.
            'hourly_rate'         => (auth()->user()?->canSeeEmployeeRates() ? 'required' : 'nullable') . '|numeric|min:0',
            'overtime_rate'       => (auth()->user()?->canSeeEmployeeRates() ? 'required' : 'nullable') . '|numeric|min:0',
            'billable_rate'       => (auth()->user()?->canSeeEmployeeRates() ? 'required' : 'nullable') . '|numeric|min:0',
            'pay_cycle'           => 'nullable|in:weekly,bi_weekly,semi_monthly,monthly',
            'pay_type'            => 'nullable|in:hourly,salary',
            'union'               => 'nullable|string|max:100',
            'employee_type'       => 'nullable|string|max:100',
            'department'          => 'nullable|string|max:100',
            'classification'      => 'nullable|string|max:100',
            'default_cost_type_id'=> 'nullable|exists:cost_types,id',
            'is_supervisor'       => 'nullable|boolean',
            'certified_pay'       => 'nullable|boolean',
            'work_comp_code'      => 'nullable|string|max:50',
            'suta_state'          => 'nullable|string|max:10',
            'state_tax'           => 'nullable|string|max:10',
            'city_tax'            => 'nullable|string|max:50',
            'st_burden_rate'      => 'nullable|numeric|min:0|max:9999.9999',
            'ot_burden_rate'      => 'nullable|numeric|min:0|max:9999.9999',
            'status'              => 'required|in:active,inactive,terminated',
            'hire_date'           => 'required|date',
            'start_date'          => 'nullable|date',
            'rehire_date'         => 'nullable|date',
            'term_date'           => 'nullable|date',
            'term_reason'         => 'nullable|string',
        ];
    }
}
