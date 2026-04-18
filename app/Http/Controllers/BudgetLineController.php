<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\CostCode;
use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BudgetLineController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        $costCodes = CostCode::orderBy('code')->get(['id', 'code', 'name']);
        $costTypes = CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']);
        return view('budget.index', [
            'project' => $project,
            'costCodes' => $costCodes,
            'costTypes' => $costTypes,
        ]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->budgetLines()->with(['costCode', 'costType']);
        $totalRecords = $project->budgetLines()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('costCode', function ($cq) use ($search) {
                      $cq->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'costCode.code', 'description', 'budget_amount', 'revised_amount'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'asc');
        if ($orderCol === 'costCode.code') {
            $query->orderBy('cost_code_id', $orderDir);
        } else {
            $query->orderBy(str_replace('costCode.', '', $orderCol), $orderDir);
        }

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($line) {
                return [
                    'id' => $line->id,
                    'cost_code' => $line->costCode?->code ?? '—',
                    'cost_type' => $line->costType?->name ?? '—',
                    'description' => $line->description,
                    'original_amount' => $line->budget_amount,
                    'current_amount' => $line->current_amount,
                    'labor_hours' => $line->labor_hours,
                    'actions' => $line->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'description' => 'required|string|max:255',
            'budget_amount' => 'required|numeric|min:0',
            'labor_hours' => 'nullable|numeric|min:0',
        ]);

        $project->budgetLines()->create($validated + ['revised_amount' => $validated['budget_amount']]);
        return response()->json(['message' => 'Budget line created successfully']);
    }

    public function show(Project $project, BudgetLine $budgetLine): JsonResponse
    {
        return response()->json($budgetLine->load(['costCode', 'costType']));
    }

    public function edit(Project $project, BudgetLine $budgetLine): JsonResponse
    {
        return response()->json($budgetLine->load(['costCode', 'costType']));
    }

    public function update(Request $request, Project $project, BudgetLine $budgetLine): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'description' => 'required|string|max:255',
            'budget_amount' => 'required|numeric|min:0',
            'labor_hours' => 'nullable|numeric|min:0',
            'revised_amount' => 'required|numeric|min:0',
        ]);

        $budgetLine->update($validated);
        return response()->json(['message' => 'Budget line updated successfully']);
    }

    public function destroy(Project $project, BudgetLine $budgetLine): JsonResponse
    {
        $budgetLine->delete();
        return response()->json(['message' => 'Budget line deleted successfully']);
    }
}
