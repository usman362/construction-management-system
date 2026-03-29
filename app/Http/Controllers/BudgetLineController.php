<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\CostCode;
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
        return view('budget.index', ['project' => $project]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->budgetLines()->with(['costCode']);
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
        $columns = ['id', 'costCode.code', 'description', 'original_amount', 'current_amount'];
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
                    'description' => $line->description,
                    'original_amount' => $line->original_amount,
                    'current_amount' => $line->current_amount,
                    'actions' => $line->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'original_amount' => 'required|numeric|min:0',
        ]);

        $project->budgetLines()->create($validated + ['current_amount' => $validated['original_amount']]);
        return response()->json(['message' => 'Budget line created successfully']);
    }

    public function show(Project $project, BudgetLine $budgetLine): JsonResponse
    {
        return response()->json($budgetLine->load('costCode'));
    }

    public function edit(Project $project, BudgetLine $budgetLine): JsonResponse
    {
        return response()->json($budgetLine->load('costCode'));
    }

    public function update(Request $request, Project $project, BudgetLine $budgetLine): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'original_amount' => 'required|numeric|min:0',
            'current_amount' => 'required|numeric|min:0',
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
