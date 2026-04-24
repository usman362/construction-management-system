<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Rfi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RfiController extends Controller
{
    /**
     * Portfolio-wide RFI list — filterable by project, status, priority, assignee.
     */
    public function index(Request $request): View
    {
        $query = Rfi::query()->with([
            'project:id,name,project_number',
            'submitter:id,name',
            'assignee:id,name',
        ]);

        if ($projectId = $request->input('project_id')) $query->where('project_id', $projectId);
        if ($status    = $request->input('status'))     $query->where('status', $status);
        if ($priority  = $request->input('priority'))   $query->where('priority', $priority);
        if ($assignee  = $request->input('assigned_to'))$query->where('assigned_to', $assignee);

        if ($request->boolean('overdue_only')) {
            $query->whereNotNull('needed_by')
                  ->whereNotIn('status', ['answered', 'closed'])
                  ->whereDate('needed_by', '<', now()->toDateString());
        }

        $rfis = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('rfis.index', [
            'rfis'       => $rfis,
            'projects'   => Project::orderBy('name')->get(['id', 'name', 'project_number']),
            'users'      => User::orderBy('name')->get(['id', 'name']),
            'filters'    => $request->only(['project_id', 'status', 'priority', 'assigned_to', 'overdue_only']),
            'statusLabels'   => Rfi::$statusLabels,
            'priorityLabels' => Rfi::$priorityLabels,
            'categoryLabels' => Rfi::$categoryLabels,
        ]);
    }

    /**
     * RFIs for a specific project (JSON for the project show tab).
     */
    public function projectIndex(Project $project): JsonResponse
    {
        $rfis = $project->rfis()
            ->with(['submitter:id,name', 'assignee:id,name', 'responder:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'rfis'           => $rfis,
            'statusLabels'   => Rfi::$statusLabels,
            'priorityLabels' => Rfi::$priorityLabels,
            'categoryLabels' => Rfi::$categoryLabels,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate($this->rules());

        // Auto-status: if user includes a submitted_date, the RFI is "submitted", not "draft".
        if (empty($validated['status'])) {
            $validated['status'] = !empty($validated['submitted_date']) ? 'submitted' : 'draft';
        }

        $validated['submitted_by'] = auth()->id();

        $rfi = $project->rfis()->create($validated);

        return response()->json([
            'success' => true,
            'rfi'     => $rfi->load(['submitter', 'assignee']),
            'message' => "RFI {$rfi->rfi_number} created.",
        ], 201);
    }

    public function show(Project $project, Rfi $rfi): View|JsonResponse
    {
        abort_unless($rfi->project_id === $project->id, 404);

        if (request()->wantsJson()) {
            return response()->json($rfi->load(['submitter', 'assignee', 'responder', 'documents.uploader']));
        }

        $rfi->load(['submitter', 'assignee', 'responder', 'documents.uploader', 'auditLogs.user']);

        return view('rfis.show', [
            'project' => $project,
            'rfi'     => $rfi,
            'users'   => User::orderBy('name')->get(['id', 'name']),
            'statusLabels'   => Rfi::$statusLabels,
            'priorityLabels' => Rfi::$priorityLabels,
            'categoryLabels' => Rfi::$categoryLabels,
        ]);
    }

    public function update(Request $request, Project $project, Rfi $rfi): JsonResponse
    {
        abort_unless($rfi->project_id === $project->id, 404);

        $validated = $request->validate($this->rules());

        $rfi->update($validated);

        return response()->json([
            'success' => true,
            'rfi'     => $rfi->fresh()->load(['submitter', 'assignee', 'responder']),
            'message' => 'RFI updated.',
        ]);
    }

    public function destroy(Project $project, Rfi $rfi): JsonResponse
    {
        abort_unless($rfi->project_id === $project->id, 404);

        $rfi->delete();

        return response()->json(['success' => true, 'message' => 'RFI deleted.']);
    }

    /**
     * Dedicated "answer this RFI" action — stamps responded_by + date and flips status to "answered".
     */
    public function respond(Request $request, Project $project, Rfi $rfi): JsonResponse|RedirectResponse
    {
        abort_unless($rfi->project_id === $project->id, 404);

        $validated = $request->validate([
            'response'             => 'required|string',
            'cost_schedule_impact' => 'nullable|string|max:2000',
            'cost_impact'          => 'nullable|boolean',
            'schedule_impact'      => 'nullable|boolean',
        ]);

        $rfi->update($validated + [
            'status'         => 'answered',
            'responded_by'   => auth()->id(),
            'responded_date' => now()->toDateString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'rfi'     => $rfi->fresh()->load(['responder']),
                'message' => 'RFI answered.',
            ]);
        }

        return redirect()->route('projects.rfis.show', [$project, $rfi])->with('success', 'RFI answered.');
    }

    /**
     * Flip an answered RFI to closed — the originator confirms the answer is acceptable.
     */
    public function close(Request $request, Project $project, Rfi $rfi): JsonResponse|RedirectResponse
    {
        abort_unless($rfi->project_id === $project->id, 404);

        $rfi->update([
            'status'      => 'closed',
            'closed_date' => now()->toDateString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'RFI closed.']);
        }

        return redirect()->route('projects.rfis.show', [$project, $rfi])->with('success', 'RFI closed.');
    }

    private function rules(): array
    {
        return [
            'subject'              => 'required|string|max:255',
            'question'             => 'required|string',
            'category'             => 'nullable|in:' . implode(',', array_keys(Rfi::$categoryLabels)),
            'priority'             => 'nullable|in:' . implode(',', array_keys(Rfi::$priorityLabels)),
            'status'               => 'nullable|in:' . implode(',', array_keys(Rfi::$statusLabels)),
            'assigned_to'          => 'nullable|exists:users,id',
            'submitted_date'       => 'nullable|date',
            'needed_by'            => 'nullable|date',
            'response'             => 'nullable|string',
            'cost_schedule_impact' => 'nullable|string|max:2000',
            'cost_impact'          => 'nullable|boolean',
            'schedule_impact'      => 'nullable|boolean',
        ];
    }
}
