<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Craft;
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

        // Apply sorting (indices match employees/index DataTable: id, name, email, role, craft, rate, status, actions)
        if ($request->has('order') && is_array($request->input('order'))) {
            $order = $request->input('order')[0];
            $columns = [
                0 => 'employee_number',
                1 => 'first_name',
                2 => 'email',
                3 => 'role',
                4 => 'craft_id',
                5 => 'hourly_rate',
                6 => 'status',
            ];

            if (isset($columns[$order['column']])) {
                $dir = $order['dir'];
                if ((int) $order['column'] === 1) {
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
            'first_name' => $employee->first_name,
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_number' => 'required|string|max:50|unique:employees,employee_number',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees',
            'phone' => 'nullable|string|max:20',
            'craft_id' => 'nullable|exists:crafts,id',
            'role' => 'required|in:field,foreman,superintendent,project_manager,admin,accounting',
            'hourly_rate' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'billable_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,terminated',
            'hire_date' => 'required|date',
        ]);

        $employee = Employee::create($validated);

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
            'timesheets' => function ($q) {
                $q->with('project')->orderBy('date', 'desc')->limit(20);
            },
        ]);

        $totalHours = $employee->timesheets->sum('total_hours');
        $totalCost = $employee->timesheets->sum('total_cost');

        return view('employees.show', [
            'employee' => $employee,
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
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'employee_number' => "required|string|max:50|unique:employees,employee_number,{$employee->id}",
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => "nullable|email|unique:employees,email,{$employee->id}",
            'phone' => 'nullable|string|max:20',
            'craft_id' => 'nullable|exists:crafts,id',
            'role' => 'required|in:field,foreman,superintendent,project_manager,admin,accounting',
            'hourly_rate' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'billable_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,terminated',
            'hire_date' => 'required|date',
        ]);

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
}
