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

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'required|exists:cost_codes,id',
            'estimated_hours' => 'required|numeric|min:0',
            'labor_type' => 'required|in:regular,overtime,double_time',
            'notes' => 'nullable|string',
        ]);

        $existing = $project->manhourBudgets()
            ->where('cost_code_id', $validated['cost_code_id'])
            ->where('labor_type', $validated['labor_type'])
            ->first();

        if ($existing) {
            $existing->update(['estimated_hours' => $existing->estimated_hours + $validated['estimated_hours']]);
        } else {
            $project->manhourBudgets()->create($validated);
        }

        return redirect()->route('projects.manhour-budgets.index', $project)
            ->with('success', 'Manhour budget created successfully.');
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

    public function update(Request $request, ManhourBudget $manhourBudget): RedirectResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'required|exists:cost_codes,id',
            'estimated_hours' => 'required|numeric|min:0',
            'labor_type' => 'required|in:regular,overtime,double_time',
            'notes' => 'nullable|string',
        ]);

        $manhourBudget->update($validated);

        return redirect()->route('projects.manhour-budgets.index', $manhourBudget->project)
            ->with('success', 'Manhour budget updated successfully.');
    }

    public function destroy(ManhourBudget $manhourBudget): RedirectResponse
    {
        $project = $manhourBudget->project;
        $manhourBudget->delete();

        return redirect()->route('projects.manhour-budgets.index', $project)
            ->with('success', 'Manhour budget deleted successfully.');
    }
}
