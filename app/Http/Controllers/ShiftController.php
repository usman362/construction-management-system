<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('shifts.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Shift::query();
        $totalRecords = Shift::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order (matches DataTable: name, start, end, break, status, actions)
        $columns = ['name', 'start_time', 'end_time', 'break_duration', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'break_duration' => $shift->break_duration ?? 0,
                    'hours_per_day' => $shift->hours_per_day,
                    'multiplier' => $shift->multiplier,
                    'is_active' => $shift->is_active,
                    'actions' => $shift->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_duration' => 'nullable|integer|min:0|max:1440',
            'hours_per_day' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['break_duration'] = $validated['break_duration'] ?? 0;
        $validated['hours_per_day'] = $validated['hours_per_day'] ?? 8;
        $validated['multiplier'] = $validated['multiplier'] ?? 1;

        Shift::create($validated);

        return response()->json(['message' => 'Shift created successfully']);
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->load('crews');
        return response()->json($shift);
    }

    public function edit(Shift $shift): JsonResponse
    {
        return response()->json($shift);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_duration' => 'nullable|integer|min:0|max:1440',
            'hours_per_day' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['break_duration'] = $validated['break_duration'] ?? 0;
        if (! array_key_exists('hours_per_day', $validated) || $validated['hours_per_day'] === null) {
            $validated['hours_per_day'] = $shift->hours_per_day;
        }
        if (! array_key_exists('multiplier', $validated) || $validated['multiplier'] === null) {
            $validated['multiplier'] = $shift->multiplier;
        }

        $shift->update($validated);

        return response()->json(['message' => 'Shift updated successfully']);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully']);
    }
}
