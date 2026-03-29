<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function create(): View
    {
        return view('equipment.create');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('equipment.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Equipment::with('vendor');
        $totalRecords = Equipment::count();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        $columns = ['name', 'type', 'model_number', 'serial_number', 'daily_rate', 'status'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'type' => $equipment->type,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'daily_rate' => $equipment->daily_rate,
                    'status' => $equipment->status,
                    'actions' => $equipment->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:owned,rented,third_party',
            'model_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'monthly_rate' => 'nullable|numeric|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|in:available,in_use,maintenance,retired',
        ]);

        Equipment::create($validated);

        return response()->json(['message' => 'Equipment created successfully']);
    }

    public function show(Request $request, Equipment $equipment): JsonResponse|View
    {
        $equipment->load(['vendor', 'assignments.project', 'currentAssignment.project']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($equipment);
        }

        return view('equipment.show', ['equipment' => $equipment]);
    }

    public function edit(Request $request, Equipment $equipment): JsonResponse|View
    {
        $equipment->load('vendor');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($equipment);
        }

        return view('equipment.edit', ['equipment' => $equipment]);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:owned,rented,third_party',
            'model_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'monthly_rate' => 'nullable|numeric|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|in:available,in_use,maintenance,retired',
        ]);

        $equipment->update($validated);

        $message = 'Equipment updated successfully';

        return $request->ajax() || $request->wantsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('equipment.show', $equipment)->with('success', $message);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json(['message' => 'Equipment deleted successfully']);
    }

    public function assign(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'assigned_date' => 'required|date',
        ]);

        EquipmentAssignment::create([
            'equipment_id' => $equipment->id,
            'project_id' => $validated['project_id'],
            'assigned_date' => $validated['assigned_date'],
        ]);

        $equipment->update(['status' => 'in_use']);

        return response()->json(['message' => 'Equipment assigned to project']);
    }

    public function unassign(EquipmentAssignment $equipmentAssignment): JsonResponse
    {
        $equipment = $equipmentAssignment->equipment;

        $equipmentAssignment->update([
            'returned_date' => now(),
        ]);

        $equipment->update(['status' => 'available']);

        return response()->json(['message' => 'Equipment returned from project']);
    }
}
