<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ChangeOrder;
use App\Models\ChangeOrderItem;
use App\Models\ChangeOrderLabor;
use App\Models\CostCode;
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
        return view('change-orders.index', ['project' => $project]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->changeOrders();
        $totalRecords = $project->changeOrders()->count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('co_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'co_number', 'title', 'amount', 'status'];
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
                return [
                    'id' => $co->id,
                    'co_number' => $co->co_number,
                    'title' => $co->title ?? '—',
                    'amount' => $co->amount,
                    'status' => $co->status,
                    'actions' => $co->id,
                ];
            }),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $coNumber = 'CO-' . str_pad($project->changeOrders()->count() + 1, 4, '0', STR_PAD_LEFT);

        $project->changeOrders()->create([
            ...$validated,
            'co_number' => $coNumber,
        ]);

        return response()->json(['message' => 'Change order created successfully']);
    }

    public function show(Project $project, ChangeOrder $changeOrder): View
    {
        $changeOrder->load(['items', 'laborDetails']);
        return view('change-orders.show', [
            'project' => $project,
            'changeOrder' => $changeOrder,
        ]);
    }

    public function edit(Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        return response()->json($changeOrder);
    }

    public function update(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected',
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

    public function approve(Request $request, Project $project, ChangeOrder $changeOrder): JsonResponse
    {
        $changeOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
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
