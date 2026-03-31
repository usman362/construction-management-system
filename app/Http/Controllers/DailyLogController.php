<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\DailyLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DailyLogController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        return view('daily-logs.index', ['project' => $project]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->dailyLogs();
        $totalRecords = $project->dailyLogs()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', "%{$search}%")
                  ->orWhere('weather', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'date', 'weather', 'temperature', 'notes'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');
        $query->orderBy($orderCol, $orderDir);

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($log) {
                return [
                    'id' => $log->id,
                    'date' => $log->date,
                    'weather' => $log->weather,
                    'temperature' => $log->temperature,
                    'notes' => substr($log->notes ?? '', 0, 50) . '...',
                    'actions' => $log->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'weather' => 'required|string|max:255',
            'temperature' => 'nullable|numeric',
            'notes' => 'required|string',
            'visitors' => 'nullable|string',
            'safety_issues' => 'nullable|string',
            'delays' => 'nullable|string',
        ]);

        $project->dailyLogs()->create($validated);
        return response()->json(['message' => 'Daily log created successfully']);
    }

    public function show(Project $project, DailyLog $dailyLog): View
    {
        $dailyLog->load('creator');

        return view('daily-logs.show', [
            'project' => $project,
            'dailyLog' => $dailyLog,
        ]);
    }

    public function edit(Project $project, DailyLog $dailyLog): JsonResponse
    {
        return response()->json($dailyLog);
    }

    public function update(Request $request, Project $project, DailyLog $dailyLog): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'weather' => 'required|string|max:255',
            'temperature' => 'nullable|numeric',
            'notes' => 'required|string',
            'visitors' => 'nullable|string',
            'safety_issues' => 'nullable|string',
            'delays' => 'nullable|string',
        ]);

        $dailyLog->update($validated);
        return response()->json(['message' => 'Daily log updated successfully']);
    }

    public function destroy(Project $project, DailyLog $dailyLog): JsonResponse
    {
        $dailyLog->delete();
        return response()->json(['message' => 'Daily log deleted successfully']);
    }
}
