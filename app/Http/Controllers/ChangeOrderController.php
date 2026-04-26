<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ChangeOrder;
use App\Models\ChangeOrderItem;
use App\Models\ChangeOrderLabor;
use App\Models\CostCode;
use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ChangeOrderController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }

        // Cost codes + cost types powering the phase-code / cost-type dropdowns
        // on the create/edit modals, mirroring the Commitments screen.
        $costCodes = CostCode::orderBy('code')->get();
        $costTypes = CostType::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();

        return view('change-orders.index', [
            'project' => $project,
            'costCodes' => $costCodes,
            'costTypes' => $costTypes,
        ]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->changeOrders()->with(['items.costCode', 'items.costType']);
        $totalRecords = $project->changeOrders()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('co_number', 'like', "%{$search}%")
                  ->orWhere('client_po', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'co_number', 'client_po', 'title', 'amount', 'status'];
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
            'data' => $data->map(function ($co) {
                // If the CO has line items, surface the first item's phase
                // code / cost type / description so the list looks like
                // Commitments. When the CO has >1 item we say "Multiple" to
                // signal the user should drill into the detail view.
                $itemCount = $co->items->count();
                $firstItem = $co->items->first();

                $phaseCode = $firstItem?->costCode?->code;
                $costType  = $firstItem?->costType?->name ?? $firstItem?->costType?->code;
                $itemDesc  = $firstItem?->description;

                if ($itemCount > 1) {
                    $phaseCode = $phaseCode ? $phaseCode.' +'.($itemCount - 1).' more' : 'Multiple ('.$itemCount.')';
                    $costType  = $costType ? $costType.' +'.($itemCount - 1).' more' : 'Multiple';
                    $itemDesc  = $itemDesc ? $itemDesc.' …' : 'Multiple line items';
                }

                // Fall back to CO-level description if there are no items
                $description = $itemDesc ?: $co->description ?: $co->title;

                return [
                    'id' => $co->id,
                    'co_number' => $co->co_number,
                    'client_po' => $co->client_po,
                    'title' => $co->title ?? '—',
                    'phase_code' => $phaseCode,
                    'cost_type' => $costType,
                    'description' => $description,
                    'amount' => $co->amount,
                    'status' => $co->status,
                    'pricing_type' => $co->pricing_type ?? 'lump_sum',
                    'actions' => $co->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'co_number' => 'nullable|string|max:50',
            'client_po' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'date' => 'nullable|date',
            'contract_time_change_days' => 'nullable|integer',
            'new_completion_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected',
            'pricing_type' => 'nullable|in:lump_sum,t_and_m',
        ]);

        // If the user didn't supply a number, auto-generate based on project
        // number (e.g. BM-5400 → BM-5400-01, -02, -03 ...).
        $coNumber = trim($validated['co_number'] ?? '');
        if ($coNumber === '') {
            $existingCount = (int) $project->changeOrders()->count();
            $prefix = $project->project_number ?: ('P' . $project->id);
            $coNumber = $prefix . '-' . str_pad($existingCount + 1, 2, '0', STR_PAD_LEFT);
        }
        $validated['co_number'] = $coNumber;

        $project->changeOrders()->create([
            ...$validated,
            'date' => $validated['date'] ?? now()->toDateString(),
        ]);

        return response()->json(['message' => 'Change order created successfully']);
    }

    public function show(Project $project, ChangeOrder $changeOrder): View
    {
        $changeOrder->load(['items', 'laborDetails']);

        // Get previously approved COs (excluding current one)
        $previousCOs = ChangeOrder::where('project_id', $project->id)
            ->where('id', '!=', $changeOrder->id)
            ->where('status', 'approved')
            ->orderBy('date')
            ->get();

        $previousCOsTotal = $previousCOs->sum('amount');

        return view('change-orders.show', [
            'project' => $project,
            'changeOrder' => $changeOrder,
            'previousCOs' => $previousCOs,
            'previousCOsTotal' => $previousCOsTotal,
        ]);
    }

    public function edit(Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        return response()->json($changeOrder);
    }

    public function update(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $validated = $request->validate([
            'co_number' => 'nullable|string|max:50',
            'client_po' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'date' => 'nullable|date',
            'contract_time_change_days' => 'nullable|integer',
            'new_completion_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected',
            'pricing_type' => 'nullable|in:lump_sum,t_and_m',
        ]);

        $changeOrder->update($validated);
        return response()->json(['message' => 'Change order updated successfully']);
    }

    public function destroy(Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $changeOrder->delete();
        return response()->json(['message' => 'Change order deleted successfully']);
    }

    public function addItem(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $validated = $request->validate([
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $amount = $validated['quantity'] * $validated['unit_cost'];

        $changeOrder->items()->create([
            ...$validated,
            'amount' => $amount,
        ]);

        return response()->json(['message' => 'Item added to change order']);
    }

    public function addLabor(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $validated = $request->validate([
            'craft_id' => 'nullable|exists:crafts,id',
            'skill_description' => 'nullable|string|max:255',
            'num_workers' => 'required|integer|min:1',
            'rate_per_hour' => 'required|numeric|min:0',
            'hours_per_day' => 'required|numeric|min:0',
            'duration_days' => 'required|numeric|min:0',
            'is_overtime' => 'boolean',
        ]);

        $cost = $validated['num_workers'] * $validated['rate_per_hour'] *
                $validated['hours_per_day'] * $validated['duration_days'];

        $changeOrder->laborDetails()->create([
            ...$validated,
            'cost' => $cost,
        ]);

        return response()->json(['message' => 'Labor added to change order']);
    }

    /**
     * Phase 7F — Capture an e-signature on a change order.
     * Stored inline as a Base64 PNG data URL (`signature` longText) plus a
     * typed legal name and timestamp. The currently logged-in user is also
     * recorded as the audit-trail signer.
     */
    public function sign(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $data = $request->validate([
            // ~30KB hard ceiling — typical signature PNG is 4-15KB. Anything
            // larger is almost certainly garbage from a misuse of the endpoint.
            'signature'      => ['required', 'string', 'max:200000', 'starts_with:data:image/'],
            'signature_name' => ['required', 'string', 'max:150'],
        ]);

        // Soft sanity check — make sure this CO actually belongs to the project
        // in the URL (Laravel's implicit binding doesn't enforce the chain).
        abort_unless($changeOrder->project_id === $project->id, 404);

        $changeOrder->update([
            'signature'      => $data['signature'],
            'signature_name' => $data['signature_name'],
            'signed_at'      => now(),
            'signed_by'      => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signature saved.',
            'signed_at' => $changeOrder->signed_at->format('M j, Y g:i A'),
        ]);
    }

    public function approve(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        // Idempotency: don't double-credit project budget if already approved
        if ($changeOrder->status === 'approved') {
            return response()->json(['message' => 'Change order is already approved']);
        }

        $changeOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_date' => now()->toDateString(),
        ]);

        $project->increment('current_budget', $changeOrder->amount);

        return response()->json(['message' => 'Change order approved and project budget updated']);
    }

    /**
     * Generate and download a Change Order PDF with signature lines
     */
    public function downloadPdf(Project $project, ChangeOrder $changeOrder)
    {
        $changeOrder->load(['items', 'laborDetails']);
        $project->load('client');

        // Calculate previously approved CO totals (excluding this one)
        $previousCOTotal = ChangeOrder::where('project_id', $project->id)
            ->where('id', '!=', $changeOrder->id)
            ->where('status', 'approved')
            ->sum('amount');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.change-order', [
            'project' => $project,
            'changeOrder' => $changeOrder,
            'previousCOTotal' => $previousCOTotal,
        ]);

        return $pdf->download("CO-{$changeOrder->co_number}-{$project->project_number}.pdf");
    }
}
