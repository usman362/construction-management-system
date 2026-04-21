<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProjectBillableRateController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        return view('project-billable-rates.index', [
            'project' => $project,
            'crafts' => Craft::active()->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => Employee::where('status', 'active')
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'employee_number', 'first_name', 'last_name']),
        ]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->projectBillableRates()->with(['craft', 'employee']);
        $totalRecords = $project->projectBillableRates()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('base_hourly_rate', 'like', "%{$search}%")
                  ->orWhereHas('craft', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'craft.name', 'employee.first_name', 'base_hourly_rate', 'markup_percentage', 'straight_time_rate', 'overtime_rate', 'effective_date'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'asc');

        if (strpos($orderCol, '.') !== false) {
            // Handle relationship columns
            if ($orderCol === 'craft.name') {
                $query->orderBy('craft_id', $orderDir);
            } elseif ($orderCol === 'employee.first_name') {
                $query->orderBy('employee_id', $orderDir);
            }
        } else {
            $query->orderBy($orderCol, $orderDir);
        }

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'craft_name' => $rate->craft?->name ?? '—',
                    'employee_name' => $rate->employee ? "{$rate->employee->first_name} {$rate->employee->last_name}" : '—',
                    'base_hourly_rate' => $rate->base_hourly_rate,
                    'base_ot_hourly_rate' => $rate->base_ot_hourly_rate,
                    'markup_percentage' => number_format((float)$rate->markup_percentage * 100, 2) . '%',
                    'straight_time_rate' => $rate->straight_time_rate,
                    'overtime_rate' => $rate->overtime_rate,
                    'double_time_rate' => $rate->double_time_rate,
                    'effective_date' => $rate->effective_date?->format('Y-m-d') ?? '—',
                    'actions' => $rate->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'craft_id' => 'nullable|exists:crafts,id',
            'employee_id' => 'nullable|exists:employees,id',
            'base_hourly_rate' => 'required|numeric|min:0',
            'base_ot_hourly_rate' => 'nullable|numeric|min:0',
            'payroll_tax_rate' => 'nullable|numeric|min:0|max:1',
            'burden_rate' => 'nullable|numeric|min:0|max:1',
            'insurance_rate' => 'nullable|numeric|min:0|max:1',
            'job_expenses_rate' => 'nullable|numeric|min:0|max:1',
            'consumables_rate' => 'nullable|numeric|min:0|max:1',
            'overhead_rate' => 'nullable|numeric|min:0|max:1',
            'profit_rate' => 'nullable|numeric|min:0|max:1',
            'payroll_tax_ot_rate' => 'nullable|numeric|min:0|max:1',
            'burden_ot_rate' => 'nullable|numeric|min:0|max:1',
            'insurance_ot_rate' => 'nullable|numeric|min:0|max:1',
            'job_expenses_ot_rate' => 'nullable|numeric|min:0|max:1',
            'consumables_ot_rate' => 'nullable|numeric|min:0|max:1',
            'overhead_ot_rate' => 'nullable|numeric|min:0|max:1',
            'profit_ot_rate' => 'nullable|numeric|min:0|max:1',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Set default values for null rates
        $validated['payroll_tax_rate'] = $validated['payroll_tax_rate'] ?? 0;
        $validated['burden_rate'] = $validated['burden_rate'] ?? 0;
        $validated['insurance_rate'] = $validated['insurance_rate'] ?? 0;
        $validated['job_expenses_rate'] = $validated['job_expenses_rate'] ?? 0;
        $validated['consumables_rate'] = $validated['consumables_rate'] ?? 0;
        $validated['overhead_rate'] = $validated['overhead_rate'] ?? 0;
        $validated['profit_rate'] = $validated['profit_rate'] ?? 0;

        $project->projectBillableRates()->create($validated);
        return response()->json(['message' => 'Project billable rate created successfully']);
    }

    public function edit(Project $project, ProjectBillableRate $projectBillableRate): JsonResponse
    {
        return response()->json($projectBillableRate->load('craft', 'employee'));
    }

    public function update(Request $request, Project $project, ProjectBillableRate $projectBillableRate): JsonResponse
    {
        $validated = $request->validate([
            'craft_id' => 'nullable|exists:crafts,id',
            'employee_id' => 'nullable|exists:employees,id',
            'base_hourly_rate' => 'required|numeric|min:0',
            'base_ot_hourly_rate' => 'nullable|numeric|min:0',
            'payroll_tax_rate' => 'nullable|numeric|min:0|max:1',
            'burden_rate' => 'nullable|numeric|min:0|max:1',
            'insurance_rate' => 'nullable|numeric|min:0|max:1',
            'job_expenses_rate' => 'nullable|numeric|min:0|max:1',
            'consumables_rate' => 'nullable|numeric|min:0|max:1',
            'overhead_rate' => 'nullable|numeric|min:0|max:1',
            'profit_rate' => 'nullable|numeric|min:0|max:1',
            'payroll_tax_ot_rate' => 'nullable|numeric|min:0|max:1',
            'burden_ot_rate' => 'nullable|numeric|min:0|max:1',
            'insurance_ot_rate' => 'nullable|numeric|min:0|max:1',
            'job_expenses_ot_rate' => 'nullable|numeric|min:0|max:1',
            'consumables_ot_rate' => 'nullable|numeric|min:0|max:1',
            'overhead_ot_rate' => 'nullable|numeric|min:0|max:1',
            'profit_ot_rate' => 'nullable|numeric|min:0|max:1',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Set default values for null rates
        $validated['payroll_tax_rate'] = $validated['payroll_tax_rate'] ?? 0;
        $validated['burden_rate'] = $validated['burden_rate'] ?? 0;
        $validated['insurance_rate'] = $validated['insurance_rate'] ?? 0;
        $validated['job_expenses_rate'] = $validated['job_expenses_rate'] ?? 0;
        $validated['consumables_rate'] = $validated['consumables_rate'] ?? 0;
        $validated['overhead_rate'] = $validated['overhead_rate'] ?? 0;
        $validated['profit_rate'] = $validated['profit_rate'] ?? 0;

        $projectBillableRate->update($validated);
        return response()->json(['message' => 'Project billable rate updated successfully']);
    }

    public function destroy(Project $project, ProjectBillableRate $projectBillableRate): JsonResponse
    {
        $projectBillableRate->delete();
        return response()->json(['message' => 'Project billable rate deleted successfully']);
    }
}
