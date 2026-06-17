<?php

namespace App\Http\Controllers;

use App\Models\CostCode;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Project-scoped phase codes (Brenda 2026-06-17).
 *
 * Manages the subset of the global cost_codes library enabled for a given
 * project. Also exposes a JSON endpoint that pickers across the system
 * call to filter their dropdowns by project.
 */
class ProjectCostCodeController extends Controller
{
    public function index(Project $project): View
    {
        $allCodes = CostCode::with('costType')->orderBy('code')->get();

        // Map cost_code_id → ['enabled' => bool, 'sort_order' => int]
        $assigned = $project->costCodes()->get()->mapWithKeys(fn ($c) => [
            $c->id => [
                'is_active'  => (bool) $c->pivot->is_active,
                'sort_order' => (int)  $c->pivot->sort_order,
            ],
        ]);

        $otherProjects = Project::where('id', '!=', $project->id)
            ->whereHas('costCodes')
            ->orderBy('name')
            ->get(['id', 'name', 'project_number']);

        return view('projects.cost-codes', [
            'project'        => $project,
            'allCodes'       => $allCodes,
            'assigned'       => $assigned,
            'otherProjects'  => $otherProjects,
        ]);
    }

    /**
     * Bulk-save the enabled cost codes for this project.
     * Body: { cost_code_ids: [1, 4, 7, ...] }
     */
    public function sync(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'cost_code_ids'   => 'nullable|array',
            'cost_code_ids.*' => 'integer|exists:cost_codes,id',
        ]);

        $ids = $validated['cost_code_ids'] ?? [];
        $sync = [];
        foreach ($ids as $i => $id) {
            $sync[$id] = ['is_active' => true, 'sort_order' => $i];
        }
        $project->costCodes()->sync($sync);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'count' => count($ids)]);
        }
        return redirect()->route('projects.cost-codes.index', $project)
            ->with('success', count($ids) . ' cost code(s) enabled for this project.');
    }

    /**
     * Copy the enabled cost codes from another project.
     * Body: { source_project_id: N }
     */
    public function copyFrom(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'source_project_id' => 'required|integer|exists:projects,id|different:project',
        ]);

        $source = Project::findOrFail($validated['source_project_id']);
        $rows   = $source->costCodes()->wherePivot('is_active', true)->get();

        $sync = [];
        foreach ($rows as $i => $cc) {
            $sync[$cc->id] = ['is_active' => true, 'sort_order' => $i];
        }
        $project->costCodes()->sync($sync);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'count' => $rows->count()]);
        }
        return redirect()->route('projects.cost-codes.index', $project)
            ->with('success', "Copied {$rows->count()} cost codes from {$source->name}.");
    }

    /**
     * JSON endpoint used by pickers system-wide.
     * Returns the effective cost-code list for this project — project-scoped
     * when configured, otherwise the full global library.
     */
    public function list(Project $project): JsonResponse
    {
        $codes = $project->effectiveCostCodes();
        return response()->json([
            'project_id'    => $project->id,
            'project_scoped' => $project->costCodes()->wherePivot('is_active', true)->exists(),
            'data'          => $codes->map(fn ($c) => [
                'id'   => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ]),
        ]);
    }
}
