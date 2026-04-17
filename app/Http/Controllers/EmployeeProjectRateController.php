<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeProjectRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeProjectRateController extends Controller
{
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $validated = $this->validated($request);
        EmployeeProjectRate::create($validated + ['employee_id' => $employee->id]);
        return response()->json(['message' => 'Project rate added']);
    }

    public function update(Request $request, Employee $employee, EmployeeProjectRate $projectRate): JsonResponse
    {
        abort_if($projectRate->employee_id !== $employee->id, 404);
        $validated = $this->validated($request);
        $projectRate->update($validated);
        return response()->json(['message' => 'Project rate updated']);
    }

    public function destroy(Employee $employee, EmployeeProjectRate $projectRate): JsonResponse
    {
        abort_if($projectRate->employee_id !== $employee->id, 404);
        $projectRate->delete();
        return response()->json(['message' => 'Project rate removed']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id'       => 'required|exists:projects,id',
            'hourly_rate'      => 'nullable|numeric|min:0',
            'overtime_rate'    => 'nullable|numeric|min:0',
            'double_time_rate' => 'nullable|numeric|min:0',
            'billable_rate'    => 'nullable|numeric|min:0',
            'st_burden_rate'   => 'nullable|numeric|min:0',
            'ot_burden_rate'   => 'nullable|numeric|min:0',
            'effective_date'   => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:effective_date',
            'notes'            => 'nullable|string|max:500',
        ]);
    }
}
