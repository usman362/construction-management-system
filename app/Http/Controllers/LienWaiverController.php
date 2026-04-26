<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\LienWaiver;
use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LienWaiverController extends Controller
{
    /**
     * Portfolio-wide lien waiver register — filterable by project, status, type.
     */
    public function index(Request $request): View
    {
        $query = LienWaiver::query()->with(['project:id,name,project_number', 'vendor:id,name', 'commitment:id,commitment_number,po_number']);

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $waivers = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('lien-waivers.index', [
            'waivers'  => $waivers,
            'projects' => Project::orderBy('name')->get(['id', 'name', 'project_number']),
            'filters'  => $request->only(['project_id', 'status', 'type']),
            'typeLabels' => LienWaiver::$typeLabels,
        ]);
    }

    /**
     * List waivers for a specific project (returns JSON for the project show page).
     */
    public function projectIndex(Project $project): JsonResponse
    {
        $waivers = $project->lienWaivers()
            ->with(['vendor:id,name', 'commitment:id,commitment_number,po_number', 'documents'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'waivers'    => $waivers,
            'typeLabels' => LienWaiver::$typeLabels,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id'     => 'nullable|exists:vendors,id',
            'commitment_id' => 'nullable|exists:commitments,id',
            'type'          => 'required|in:' . implode(',', array_keys(LienWaiver::$typeLabels)),
            'amount'        => 'required|numeric|min:0',
            'through_date'  => 'nullable|date',
            'received_date' => 'nullable|date',
            'status'        => 'required|in:pending,received,rejected',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $waiver = $project->lienWaivers()->create($validated + ['created_by' => auth()->id()]);

        return response()->json([
            'success' => true,
            'waiver'  => $waiver->load(['vendor', 'commitment']),
            'message' => 'Lien waiver recorded.',
        ], 201);
    }

    public function update(Request $request, Project $project, LienWaiver $lienWaiver): JsonResponse
    {
        abort_unless($lienWaiver->project_id === $project->id, 404);

        $validated = $request->validate([
            'vendor_id'     => 'nullable|exists:vendors,id',
            'commitment_id' => 'nullable|exists:commitments,id',
            'type'          => 'required|in:' . implode(',', array_keys(LienWaiver::$typeLabels)),
            'amount'        => 'required|numeric|min:0',
            'through_date'  => 'nullable|date',
            'received_date' => 'nullable|date',
            'status'        => 'required|in:pending,received,rejected',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $lienWaiver->update($validated);

        return response()->json([
            'success' => true,
            'waiver'  => $lienWaiver->fresh()->load(['vendor', 'commitment']),
            'message' => 'Lien waiver updated.',
        ]);
    }

    public function destroy(Project $project, LienWaiver $lienWaiver): JsonResponse
    {
        abort_unless($lienWaiver->project_id === $project->id, 404);

        $lienWaiver->delete();

        return response()->json(['success' => true, 'message' => 'Lien waiver deleted.']);
    }

    public function show(Project $project, LienWaiver $lienWaiver): JsonResponse
    {
        abort_unless($lienWaiver->project_id === $project->id, 404);

        return response()->json($lienWaiver->load(['vendor', 'commitment', 'documents.uploader']));
    }

    /**
     * Phase 7F — Capture an e-signature on a lien waiver. Same shape as
     * ChangeOrderController::sign() so the partial works on both.
     */
    public function sign(Request $request, Project $project, LienWaiver $lienWaiver): JsonResponse
    {
        $data = $request->validate([
            'signature'      => ['required', 'string', 'max:200000', 'starts_with:data:image/'],
            'signature_name' => ['required', 'string', 'max:150'],
        ]);

        abort_unless($lienWaiver->project_id === $project->id, 404);

        $lienWaiver->update([
            'signature'      => $data['signature'],
            'signature_name' => $data['signature_name'],
            'signed_at'      => now(),
            'signed_by'      => auth()->id(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Signature saved.',
            'signed_at' => $lienWaiver->signed_at->format('M j, Y g:i A'),
        ]);
    }
}
