<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('invoices.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Invoice::with(['vendor', 'project']);
        $totalRecords = Invoice::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('project', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'invoice_number', 'invoice_date', 'amount', 'status'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');
        $query->orderBy($orderCol, $orderDir);

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                    'vendor' => $inv->vendor?->name ?? '—',
                    'project' => $inv->project?->name ?? '—',
                    'amount' => $inv->amount,
                    'status' => $inv->status,
                    'actions' => $inv->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'commitment_id' => 'nullable|exists:commitments,id',
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,submitted,approved,paid',
        ]);

        Invoice::create($validated);
        return response()->json(['message' => 'Invoice created successfully']);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['project', 'vendor']);
        return response()->json($invoice);
    }

    public function edit(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'commitment_id' => 'nullable|exists:commitments,id',
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,submitted,approved,paid',
        ]);

        $invoice->update($validated);
        return response()->json(['message' => 'Invoice updated successfully']);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    public function approve(Invoice $invoice): JsonResponse
    {
        $invoice->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Invoice approved']);
    }
}
