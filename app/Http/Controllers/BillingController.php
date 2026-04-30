<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        return view('billing.index', [
            'projects' => Project::query()->orderBy('project_number')->get(['id', 'name', 'project_number']),
            // 2026-04-30 (Brenda): the create/edit modals let the office link
            // a vendor invoice to an internal PO. We pass *all* active POs and
            // filter them client-side based on the chosen project (data-project
            // attribute on each <option>).
            'purchaseOrders' => \App\Models\PurchaseOrder::query()
                ->orderBy('po_number')
                ->get(['id', 'po_number', 'project_id']),
        ]);
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = BillingInvoice::query()->with(['project']);

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $totalRecords = BillingInvoice::count();
        $filteredRecords = (clone $query)->count();

        $orderColIndex = (int) $request->input('order.0.column', 1);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ($orderColIndex) {
            case 0:
                $query->orderBy('invoice_number', $orderDir);
                break;
            case 1:
                $query->orderBy('invoice_date', $orderDir);
                break;
            case 2:
                $query->orderBy(
                    Project::select('name')
                        ->whereColumn('projects.id', 'billing_invoices.project_id')
                        ->limit(1),
                    $orderDir
                );
                break;
            case 3:
                $query->orderBy('total_amount', $orderDir);
                break;
            case 4:
                $query->orderBy('status', $orderDir);
                break;
            default:
                $query->orderBy('invoice_date', 'desc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function (BillingInvoice $inv) {
                $displayDate = $inv->invoice_date ?? $inv->billing_period_start;

                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $displayDate?->format('M j, Y') ?? '—',
                    'project' => $inv->project?->name ?? '—',
                    'total_amount' => $inv->total_amount,
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
            // 2026-04-30 (Brenda): vendor-invoice → PO cross reference.
            // Either link to an internal PO, or type a free-text vendor PO
            // number, or both. Both nullable.
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'po_reference'      => 'nullable|string|max:100',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'description' => 'nullable|string',
        ]);

        BillingInvoice::create([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'po_reference'      => $validated['po_reference'] ?? null,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'billing_period_start' => $validated['invoice_date'],
            'billing_period_end' => $validated['invoice_date'],
            'total_amount' => 0,
            'status' => 'draft',
        ]);

        return response()->json(['message' => 'Billing invoice created successfully']);
    }

    public function show(BillingInvoice $billingInvoice): View
    {
        $billingInvoice->load(['project.client']);

        return view('billing.show', ['billingInvoice' => $billingInvoice]);
    }

    public function edit(BillingInvoice $billingInvoice): JsonResponse
    {
        return response()->json([
            'id' => $billingInvoice->id,
            'project_id' => $billingInvoice->project_id,
            'purchase_order_id' => $billingInvoice->purchase_order_id,
            'po_reference' => $billingInvoice->po_reference,
            'invoice_number' => $billingInvoice->invoice_number,
            'invoice_date' => $billingInvoice->invoice_date?->format('Y-m-d')
                ?? $billingInvoice->billing_period_start?->format('Y-m-d'),
            'due_date' => $billingInvoice->due_date?->format('Y-m-d'),
            'description' => $billingInvoice->description,
        ]);
    }

    public function update(Request $request, BillingInvoice $billingInvoice): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'po_reference'      => 'nullable|string|max:100',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'description' => 'nullable|string',
        ]);

        $billingInvoice->update([
            'project_id' => $validated['project_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'po_reference'      => $validated['po_reference'] ?? null,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'billing_period_start' => $validated['invoice_date'],
            'billing_period_end' => $validated['invoice_date'],
        ]);

        return response()->json(['message' => 'Billing invoice updated successfully']);
    }

    public function destroy(BillingInvoice $billingInvoice): JsonResponse
    {
        $billingInvoice->delete();

        return response()->json(['message' => 'Billing invoice deleted successfully']);
    }

    public function generate(Request $request, BillingInvoice $billingInvoice): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'invoice_number' => 'required|string|max:100',
        ]);

        $project = $billingInvoice->project;
        $timesheets = Timesheet::where('project_id', $project->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
            ->get();

        $timesheetAmount = (float) $timesheets->sum('billable_amount');

        $invoices = Invoice::where('project_id', $project->id)
            ->where('status', 'approved')
            ->whereBetween('invoice_date', [$validated['start_date'], $validated['end_date']])
            ->get();

        $invoiceAmount = (float) $invoices->sum('amount');
        $subtotal = $timesheetAmount + $invoiceAmount;

        // Retainage snapshot from the project default (can be edited on the invoice later).
        $retainagePercent = (float) ($project->retainage_percent ?? 0);
        $retainageAmount  = round($subtotal * ($retainagePercent / 100), 2);

        $billingInvoice->update([
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => now()->toDateString(),
            'billing_period_start' => $validated['start_date'],
            'billing_period_end' => $validated['end_date'],
            'labor_amount' => $timesheetAmount,
            'material_amount' => $invoiceAmount,
            'subtotal' => $subtotal,
            'retainage_percent' => $retainagePercent,
            'retainage_amount' => $retainageAmount,
            'total_amount' => $subtotal,
            'status' => 'draft',
        ]);

        return response()->json(['message' => 'Billing invoice generated successfully']);
    }

    /**
     * Mark retainage on this invoice as released (e.g. at substantial completion).
     */
    public function releaseRetainage(Request $request, BillingInvoice $billingInvoice): JsonResponse|RedirectResponse
    {
        $billingInvoice->update([
            'retainage_released'      => true,
            'retainage_released_date' => now()->toDateString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Retainage marked as released.',
                'retainage_released_date' => $billingInvoice->retainage_released_date?->format('m/d/Y'),
            ]);
        }

        return redirect()
            ->route('billing.show', $billingInvoice)
            ->with('success', 'Retainage marked as released.');
    }

    public function send(Request $request, BillingInvoice $billingInvoice): JsonResponse|RedirectResponse
    {
        $billingInvoice->update([
            'status' => 'sent',
            'sent_date' => now()->toDateString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Billing invoice sent']);
        }

        return redirect()
            ->route('billing.show', $billingInvoice)
            ->with('success', 'Invoice marked as sent.');
    }

    public function markPaid(Request $request, BillingInvoice $billingInvoice): JsonResponse|RedirectResponse
    {
        $billingInvoice->update([
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Billing invoice marked as paid']);
        }

        return redirect()
            ->route('billing.show', $billingInvoice)
            ->with('success', 'Invoice marked as paid.');
    }

    public function downloadPdf(BillingInvoice $billingInvoice)
    {
        $billingInvoice->load(['project.client']);

        $pdf = Pdf::loadView('pdf.billing-invoice', compact('billingInvoice'));

        return $pdf->download("invoice-{$billingInvoice->invoice_number}.pdf");
    }
}
