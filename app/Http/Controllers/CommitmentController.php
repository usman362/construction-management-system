<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Commitment;
use App\Models\Vendor;
use App\Models\CostCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CommitmentController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);
        $costCodes = CostCode::orderBy('code')->get(['id', 'code', 'name']);
        return view('commitments.index', [
            'project' => $project,
            'vendors' => $vendors,
            'costCodes' => $costCodes,
        ]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->commitments()->with(['vendor']);
        $totalRecords = $project->commitments()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('commitment_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'commitment_number', 'vendor_id', 'description', 'amount', 'status'];
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
            'data' => $data->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'commitment_number' => $comm->commitment_number,
                    'vendor' => $comm->vendor?->name ?? '—',
                    'description' => $comm->description,
                    'amount' => $comm->amount,
                    'status' => $comm->status,
                    'actions' => $comm->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'status' => 'required|in:draft,released,accepted,completed',
            'notes' => 'nullable|string',
        ]);

        $commNumber = 'COMM-' . str_pad($project->commitments()->count() + 1, 5, '0', STR_PAD_LEFT);

        $project->commitments()->create([
            ...$validated,
            'commitment_number' => $commNumber,
        ]);

        return response()->json(['message' => 'Commitment created successfully']);
    }

    public function show(Project $project, Commitment $commitment): View
    {
        $commitment->load(['vendor', 'costCode', 'invoices']);
        return view('projects.commitments.show', [
            'project' => $project,
            'commitment' => $commitment,
        ]);
    }

    public function edit(Project $project, Commitment $commitment): JsonResponse
    {
        return response()->json($commitment->load('vendor'));
    }

    public function update(Request $request, Project $project, Commitment $commitment): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'status' => 'required|in:draft,released,accepted,completed',
            'notes' => 'nullable|string',
        ]);

        $commitment->update($validated);
        return response()->json(['message' => 'Commitment updated successfully']);
    }

    public function destroy(Project $project, Commitment $commitment): JsonResponse
    {
        $commitment->delete();
        return response()->json(['message' => 'Commitment deleted successfully']);
    }
}
