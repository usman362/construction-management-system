<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ManhourBudget;
use App\Models\CostCode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ManhourBudgetController extends Controller
{
    public function index(Project $project): View
    {
        $manhourBudgets = $project->manhourBudgets()
            ->with('costCode')
            ->get();

        return view('projects.manhour-budgets.index', [
            'project' => $project,
            'manhourBudgets' => $manhourBudgets,
        ]);
    }

    public function create(Project $project): View
    {
        $costCodes = CostCode::all();

        return view('projects.manhour-budgets.create', [
            'project' => $project,
            'costCodes' => $costCodes,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'cost_code_id' => 'required|exists:cost_codes,id',
            'budget_hours' => 'required|numeric|min:0',
            'category' => 'required|in:direct,indirect',
        ]);

        $existing = $project->manhourBudgets()
            ->where('cost_code_id', $validated['cost_code_id'])
            ->where('category', $validated['category'])
            ->first();

        if ($existing) {
            $existing->update(['budget_hours' => $existing->budget_hours + $validated['budget_hours']]);
        } else {
            $project->manhourBudgets()->create($validated);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Manhour budget saved successfully']);
        }

        return redirect()->route('projects.manhour-budgets.index', $project)
            ->with('success', 'Manhour budget saved successfully.');
    }

    public function show(Project $project, ManhourBudget $manhourBudget): View
    {
        return view('projects.manhour-budgets.show', [
            'project' => $project,
            'manhourBudget' => $manhourBudget,
        ]);
    }

    public function edit(Project $project, ManhourBudget $manhourBudget): View
    {
        $costCodes = CostCode::all();

        return view('projects.manhour-budgets.edit', [
            'project' => $project,
            'manhourBudget' => $manhourBudget,
            'costCodes' => $costCodes,
        ]);
    }

    public function update(Request $request, Project $project, ManhourBudget $manhourBudget)
    {
        $validated = $request->validate([
            'cost_code_id' => 'required|exists:cost_codes,id',
            'budget_hours' => 'required|numeric|min:0',
            'category' => 'required|in:direct,indirect',
        ]);

        $manhourBudget->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Manhour budget updated successfully']);
        }

        return redirect()->route('projects.manhour-budgets.index', $manhourBudget->project)
            ->with('success', 'Manhour budget updated successfully.');
    }

    public function destroy(Project $project, ManhourBudget $manhourBudget): RedirectResponse
    {
        $manhourBudget->delete();

        return redirect()->route('projects.manhour-budgets.index', $project)
            ->with('success', 'Manhour budget deleted successfully.');
    }
}
