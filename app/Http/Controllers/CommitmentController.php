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
        $costTypes = \App\Models\CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']);
        return view('commitments.index', [
            'project' => $project,
            'vendors' => $vendors,
            'costCodes' => $costCodes,
            'costTypes' => $costTypes,
        ]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->commitments()->with(['vendor', 'costCode', 'costType']);
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
                    'po_number' => $comm->po_number,
                    'vendor' => $comm->vendor?->name ?? '—',
                    'cost_code' => $comm->costCode?->code,
                    'cost_type' => $comm->costType?->name,
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
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
        ]);

        // 2026-05-23 (KH): commitment_number is now user-editable (e.g.
        // PO-5413-01, SC-5413-01) instead of auto-generated. Uniqueness is
        // scoped per-project so different projects can re-use sequences.
        // Status enum expanded to include "pending_signature" + "executed".
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'commitment_number' => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,pending_signature,executed,approved,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate only if the user didn't type one
        if (empty($validated['commitment_number'])) {
            $maxId = (int) ($project->commitments()->max('id') ?? 0);
            $validated['commitment_number'] = 'COMM-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
        } else {
            // Reject duplicates within this project
            $clash = $project->commitments()
                ->where('commitment_number', $validated['commitment_number'])
                ->exists();
            if ($clash) {
                return response()->json([
                    'message' => "Commitment number \"{$validated['commitment_number']}\" already exists on this project. Pick a different one.",
                ], 422);
            }
        }

        $project->commitments()->create([
            ...$validated,
            'committed_date' => now()->toDateString(),
        ]);

        return response()->json(['message' => 'Commitment created successfully']);
    }

    public function show(Project $project, Commitment $commitment): View
    {
        // 2026-05-23 (KH): include costType so the show page can display it.
        $commitment->load(['vendor', 'costCode', 'costType', 'invoices']);
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
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
        ]);

        // Same updates as store() — see store() for context on commitment_number
        // being user-editable + the expanded status enum.
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'commitment_number' => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,pending_signature,executed,approved,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Uniqueness check on rename (skip self)
        if (! empty($validated['commitment_number']) && $validated['commitment_number'] !== $commitment->commitment_number) {
            $clash = $project->commitments()
                ->where('commitment_number', $validated['commitment_number'])
                ->where('id', '!=', $commitment->id)
                ->exists();
            if ($clash) {
                return response()->json([
                    'message' => "Commitment number \"{$validated['commitment_number']}\" already exists on this project.",
                ], 422);
            }
        }
        // Don't let the user blank-out the number on update; keep the old one if they did.
        if (empty($validated['commitment_number'])) {
            unset($validated['commitment_number']);
        }

        $commitment->update($validated);
        return response()->json(['message' => 'Commitment updated successfully']);
    }

    public function destroy(Project $project, Commitment $commitment): JsonResponse
    {
        $commitment->delete();
        return response()->json(['message' => 'Commitment deleted successfully']);
    }
}
