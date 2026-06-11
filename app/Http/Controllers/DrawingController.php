<?php

namespace App\Http\Controllers;

use App\Models\Drawing;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Drawing Log (Brenda, 2026-06-11) — Procore-style sheet tracking.
 * Upload a PDF as sheet A-101 rev 0, upload again as rev 1, and the
 * controller auto-marks rev 0 as superseded + links them so the
 * revision history is preserved.
 */
class DrawingController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $q = Drawing::where('project_id', $project->id)->with('uploader');

        if ($request->filled('discipline')) {
            $q->where('discipline', $request->input('discipline'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        } else {
            // Default: show current only — superseded ones live in the per-sheet history.
            $q->where('status', Drawing::STATUS_CURRENT);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $q->where(function ($w) use ($s) {
                $w->where('sheet_number', 'like', $s)
                  ->orWhere('sheet_title', 'like', $s);
            });
        }

        $drawings = $q->orderBy('discipline')->orderBy('sheet_number')->paginate(50)->withQueryString();

        return view('drawings.index', [
            'project'     => $project,
            'drawings'    => $drawings,
            'disciplines' => Drawing::DISCIPLINES,
            'filters'     => $request->only(['discipline', 'status', 'search']),
            'counts'      => [
                'current'    => Drawing::where('project_id', $project->id)->where('status', Drawing::STATUS_CURRENT)->count(),
                'superseded' => Drawing::where('project_id', $project->id)->where('status', Drawing::STATUS_SUPERSEDED)->count(),
            ],
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'sheet_number' => 'required|string|max:50',
            'sheet_title'  => 'required|string|max:255',
            'discipline'   => 'nullable|string|max:50',
            'revision'     => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:1000',
            'file'         => 'required|file|mimes:pdf|max:102400', // 100MB
        ]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return back()->withErrors(['file' => 'The uploaded file was not received.']);
        }

        $folder = 'drawings/' . $project->id;
        try {
            $disk = Storage::disk('documents');
            if (! $disk->exists($folder)) {
                $disk->makeDirectory($folder);
            }
            $path = $file->store($folder, 'documents');
            if (! $path) {
                throw new \RuntimeException('Storage returned no path.');
            }
        } catch (\Throwable $e) {
            Log::error('Drawing upload failed', ['error' => $e->getMessage(), 'project' => $project->id]);
            return back()->withErrors(['file' => 'Could not save the file: ' . $e->getMessage()]);
        }

        DB::transaction(function () use ($project, $validated, $file, $path, &$drawing) {
            // Auto-supersede the prior current revision of the same sheet number.
            $prior = Drawing::where('project_id', $project->id)
                ->where('sheet_number', $validated['sheet_number'])
                ->where('status', Drawing::STATUS_CURRENT)
                ->lockForUpdate()
                ->first();

            $drawing = Drawing::create([
                'project_id'   => $project->id,
                'sheet_number' => $validated['sheet_number'],
                'sheet_title'  => $validated['sheet_title'],
                'discipline'   => $validated['discipline'] ?? null,
                'revision'     => $validated['revision'] ?? '0',
                'status'       => Drawing::STATUS_CURRENT,
                'file_path'    => $path,
                'file_name'    => $file->getClientOriginalName(),
                'file_type'    => $file->getClientMimeType(),
                'file_size'    => $file->getSize(),
                'uploaded_by'  => auth()->id(),
                'notes'        => $validated['notes'] ?? null,
            ]);

            if ($prior) {
                $prior->update([
                    'status'           => Drawing::STATUS_SUPERSEDED,
                    'superseded_by_id' => $drawing->id,
                    'superseded_at'    => Carbon::now(),
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'drawing' => $drawing ?? null], 201);
        }
        return redirect()->route('projects.drawings.index', $project)
            ->with('success', 'Drawing uploaded.');
    }

    public function show(Project $project, Drawing $drawing): View
    {
        abort_unless($drawing->project_id === $project->id, 404);
        $drawing->load(['uploader', 'supersededBy']);

        $history = Drawing::where('project_id', $project->id)
            ->where('sheet_number', $drawing->sheet_number)
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();

        return view('drawings.show', [
            'project'  => $project,
            'drawing'  => $drawing,
            'history'  => $history,
        ]);
    }

    public function download(Project $project, Drawing $drawing)
    {
        abort_unless($drawing->project_id === $project->id, 404);
        if (! Storage::disk('documents')->exists($drawing->file_path)) {
            abort(404, 'File not found.');
        }
        return Storage::disk('documents')->download($drawing->file_path, $drawing->file_name);
    }

    public function preview(Project $project, Drawing $drawing)
    {
        abort_unless($drawing->project_id === $project->id, 404);
        if (! Storage::disk('documents')->exists($drawing->file_path)) {
            abort(404, 'File not found.');
        }
        return response()->file(
            Storage::disk('documents')->path($drawing->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function destroy(Project $project, Drawing $drawing): RedirectResponse|JsonResponse
    {
        abort_unless($drawing->project_id === $project->id, 404);

        // If we're deleting the current rev, restore the most-recent prior one
        // (if any) back to 'current' so the sheet doesn't disappear from the log.
        if ($drawing->status === Drawing::STATUS_CURRENT) {
            $prior = Drawing::where('project_id', $project->id)
                ->where('sheet_number', $drawing->sheet_number)
                ->where('id', '!=', $drawing->id)
                ->orderByDesc('created_at')
                ->first();
            if ($prior) {
                $prior->update([
                    'status'           => Drawing::STATUS_CURRENT,
                    'superseded_by_id' => null,
                    'superseded_at'    => null,
                ]);
            }
        }

        $drawing->delete(); // soft delete — file stays on disk
        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('projects.drawings.index', $project)->with('success', 'Drawing deleted.');
    }
}
