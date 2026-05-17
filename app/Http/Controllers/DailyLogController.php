<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\DailyLog;
use App\Services\DailyLogAiService;
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
        $validated = $request->validate($this->rules());

        $log = $project->dailyLogs()->create($validated + ['created_by' => auth()->id()]);
        return response()->json([
            'message' => 'Daily log created successfully',
            'id'      => $log->id,
        ]);
    }

    /**
     * Mobile-first daily log entry — same store endpoint, but a phone-friendly
     * form with camera capture, GPS-driven weather auto-fill, and voice notes.
     * See resources/views/daily-logs/mobile-create.blade.php.
     */
    public function mobileCreate(Project $project): View
    {
        return view('daily-logs.mobile-create', ['project' => $project]);
    }

    public function show(Project $project, DailyLog $dailyLog): View
    {
        $dailyLog->load(['creator', 'photos.uploader']);

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
        $validated = $request->validate($this->rules());

        $dailyLog->update($validated);
        return response()->json(['message' => 'Daily log updated successfully']);
    }

    public function destroy(Project $project, DailyLog $dailyLog): JsonResponse
    {
        $dailyLog->delete();
        return response()->json(['message' => 'Daily log deleted successfully']);
    }

    /**
     * 2026-05-12 (Brenda — Phase 2): AI Daily Log Generator.
     *
     * Foreman dictates a voice note on the mobile-create page; browser
     * SpeechRecognition transcribes it locally; we POST the transcript
     * here and Groq Llama 4 Scout returns structured fields (weather,
     * temperature, notes, visitors, incidents, etc.). The mobile UI
     * pre-fills the form with the result so the foreman just reviews +
     * hits Save instead of typing on a phone keyboard.
     *
     * Returns the same shape as the AI service's normalize output.
     */
    public function voiceParse(Request $request, Project $project, DailyLogAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'transcript' => 'required|string|min:5|max:8000',
        ]);

        try {
            $result = $ai->extractFromTranscript($data['transcript']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI parse failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'summary' => $result['summary'],
            'fields'  => $result['fields'],
        ]);
    }

    /**
     * Shared validation rules — keeps store/update in sync as we add weather + safety fields.
     */
    private function rules(): array
    {
        return [
            'date'              => 'required|date',
            'weather'           => 'required|string|max:255',
            'temperature'       => 'nullable|numeric',
            'temperature_high'  => 'nullable|numeric',
            'temperature_low'   => 'nullable|numeric',
            'precipitation'     => 'nullable|string|max:100',
            'wind_speed'        => 'nullable|string|max:50',
            'notes'             => 'required|string',
            'visitors'          => 'nullable|string',
            'safety_issues'     => 'nullable|string',
            'incidents_count'   => 'nullable|integer|min:0|max:65535',
            'near_misses_count' => 'nullable|integer|min:0|max:65535',
            'delays'            => 'nullable|string',
        ];
    }
}
