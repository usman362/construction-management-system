<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\CostCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class EstimateController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        return view('estimates.index', ['project' => $project]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->estimates();
        $totalRecords = $project->estimates()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'name', 'description', 'status', 'total_amount'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
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
            'data' => $data->map(function ($est) {
                return [
                    'id' => $est->id,
                    'name' => $est->name,
                    'description' => $est->description ?? '—',
                    'status' => $est->status,
                    'total_amount' => $est->total_amount ?? 0,
                    'actions' => $est->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        $project->estimates()->create($validated);
        return response()->json(['message' => 'Estimate created successfully']);
    }

    public function show(Project $project, Estimate $estimate): View
    {
        $estimate->load('lines.costCode');
        $costCodes = CostCode::orderBy('code')->get();

        return view('estimates.show', [
            'project' => $project,
            'estimate' => $estimate,
            'costCodes' => $costCodes,
        ]);
    }

    public function edit(Project $project, Estimate $estimate): JsonResponse
    {
        return response()->json($estimate);
    }

    public function update(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        $estimate->update($validated);
        return response()->json(['message' => 'Estimate updated successfully']);
    }

    public function destroy(Project $project, Estimate $estimate): JsonResponse
    {
        $estimate->delete();
        return response()->json(['message' => 'Estimate deleted successfully']);
    }

    public function addLine(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'labor_hours' => 'nullable|numeric|min:0',
        ]);

        $amount = $validated['quantity'] * $validated['unit_cost'];
        $estimate->lines()->create($validated + ['amount' => $amount]);

        return response()->json(['message' => 'Line item added to estimate']);
    }

    public function updateLine(Request $request, Project $project, EstimateLine $estimateLine): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'labor_hours' => 'nullable|numeric|min:0',
        ]);

        $amount = $validated['quantity'] * $validated['unit_cost'];
        $estimateLine->update($validated + ['amount' => $amount]);

        return response()->json(['message' => 'Line item updated']);
    }

    public function removeLine(Project $project, EstimateLine $estimateLine): JsonResponse
    {
        $estimateLine->delete();
        return response()->json(['message' => 'Line item removed']);
    }
}
