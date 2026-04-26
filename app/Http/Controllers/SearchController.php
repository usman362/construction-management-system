<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\Client;
use App\Models\CostCode;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Rfi;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single-endpoint global search.
 *
 * Returns up to 5 hits per entity type for the given query, grouped so the
 * dropdown UI in the top nav can render section headers (Projects, Employees,
 * etc.) without the JS having to know how each model is shaped.
 *
 * Performance:
 *   - 9 lightweight LIKE queries; each capped at 5 rows
 *   - Total typical response ~50-150 ms even on a multi-thousand-row dataset
 *   - No fulltext index required (works on shared cPanel MySQL out of the box)
 *
 * Future upgrade path: swap the LIKE queries for Laravel Scout + Meilisearch
 * (or Typesense) without changing this endpoint's shape.
 */
class SearchController extends Controller
{
    /** Max results per category in the dropdown. */
    private const PER_CATEGORY = 5;

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        // 2-char minimum keeps the dropdown from firing on every keystroke and
        // returning thousands of accidental matches.
        if (strlen($q) < 2) {
            return response()->json(['query' => $q, 'count' => 0, 'groups' => []]);
        }

        $like   = '%' . $q . '%';
        $groups = [];

        // ─── Projects ───────────────────────────────────────────────
        $projects = Project::query()
            ->where(function ($w) use ($like) {
                $w->where('project_number', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('description', 'like', $like);
            })
            ->orderByRaw("CASE WHEN project_number LIKE ? THEN 0 ELSE 1 END", [$like])
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'project_number', 'name', 'status']);

        if ($projects->isNotEmpty()) {
            $groups[] = [
                'label' => 'Projects',
                'icon'  => 'folder',
                'items' => $projects->map(fn ($p) => [
                    'title'    => "{$p->project_number} — {$p->name}",
                    'subtitle' => ucfirst($p->status ?? '—'),
                    'url'      => route('projects.show', $p->id),
                ])->all(),
            ];
        }

        // ─── Employees ──────────────────────────────────────────────
        $employees = Employee::query()
            ->where(function ($w) use ($like) {
                $w->where('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like)
                  ->orWhere('employee_number', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'first_name', 'last_name', 'employee_number', 'craft_id']);

        if ($employees->isNotEmpty()) {
            $groups[] = [
                'label' => 'Employees',
                'icon'  => 'user',
                'items' => $employees->map(fn ($e) => [
                    'title'    => trim("{$e->first_name} {$e->last_name}") ?: '—',
                    'subtitle' => $e->employee_number ? '#' . $e->employee_number : 'Employee',
                    'url'      => route('employees.show', $e->id),
                ])->all(),
            ];
        }

        // ─── Purchase Orders ────────────────────────────────────────
        $purchaseOrders = PurchaseOrder::query()
            ->with('vendor:id,name')
            ->where(function ($w) use ($like) {
                $w->where('po_number', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', $like));
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'po_number', 'vendor_id', 'status', 'total_amount']);

        if ($purchaseOrders->isNotEmpty()) {
            $groups[] = [
                'label' => 'Purchase Orders',
                'icon'  => 'shopping-bag',
                'items' => $purchaseOrders->map(fn ($po) => [
                    'title'    => "PO {$po->po_number}",
                    'subtitle' => ($po->vendor->name ?? '—') . ' · $' . number_format((float) $po->total_amount, 2),
                    'url'      => route('purchase-orders.show', $po->id),
                ])->all(),
            ];
        }

        // ─── Invoices ───────────────────────────────────────────────
        $invoices = Invoice::query()
            ->with('vendor:id,name')
            ->where(function ($w) use ($like) {
                $w->where('invoice_number', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', $like));
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'invoice_number', 'vendor_id', 'amount', 'status']);

        if ($invoices->isNotEmpty()) {
            $groups[] = [
                'label' => 'Invoices',
                'icon'  => 'file-invoice',
                'items' => $invoices->map(fn ($inv) => [
                    'title'    => "Invoice {$inv->invoice_number}",
                    'subtitle' => ($inv->vendor->name ?? '—') . ' · $' . number_format((float) $inv->amount, 2) . ' · ' . ucfirst($inv->status ?? '—'),
                    'url'      => route('invoices.show', $inv->id),
                ])->all(),
            ];
        }

        // ─── RFIs ───────────────────────────────────────────────────
        $rfis = Rfi::query()
            ->with('project:id,project_number,name')
            ->where(function ($w) use ($like) {
                $w->where('rfi_number', 'like', $like)
                  ->orWhere('subject', 'like', $like)
                  ->orWhere('question', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'project_id', 'rfi_number', 'subject', 'status']);

        if ($rfis->isNotEmpty()) {
            $groups[] = [
                'label' => 'RFIs',
                'icon'  => 'help-circle',
                'items' => $rfis->map(fn ($r) => [
                    'title'    => "{$r->rfi_number} — " . ($r->subject ?? '—'),
                    'subtitle' => ($r->project->project_number ?? '—') . ' · ' . ucfirst(str_replace('_', ' ', $r->status ?? '—')),
                    'url'      => route('projects.rfis.show', [$r->project_id, $r->id]),
                ])->all(),
            ];
        }

        // ─── Change Orders ──────────────────────────────────────────
        $changeOrders = ChangeOrder::query()
            ->with('project:id,project_number')
            ->where(function ($w) use ($like) {
                $w->where('co_number', 'like', $like)
                  ->orWhere('title', 'like', $like)
                  ->orWhere('description', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'project_id', 'co_number', 'title', 'amount', 'status']);

        if ($changeOrders->isNotEmpty()) {
            $groups[] = [
                'label' => 'Change Orders',
                'icon'  => 'edit-3',
                'items' => $changeOrders->map(fn ($co) => [
                    'title'    => "CO {$co->co_number} — " . ($co->title ?? '—'),
                    'subtitle' => ($co->project->project_number ?? '—') . ' · $' . number_format((float) $co->amount, 2) . ' · ' . ucfirst($co->status ?? '—'),
                    'url'      => route('projects.change-orders.show', [$co->project_id, $co->id]),
                ])->all(),
            ];
        }

        // ─── Vendors ────────────────────────────────────────────────
        $vendors = Vendor::query()
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('vendor_code', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'name', 'vendor_code', 'email']);

        if ($vendors->isNotEmpty()) {
            $groups[] = [
                'label' => 'Vendors',
                'icon'  => 'truck',
                'items' => $vendors->map(fn ($v) => [
                    'title'    => $v->name ?? '—',
                    'subtitle' => $v->vendor_code ? "Code {$v->vendor_code}" : ($v->email ?? 'Vendor'),
                    'url'      => route('vendors.show', $v->id),
                ])->all(),
            ];
        }

        // ─── Clients ────────────────────────────────────────────────
        $clients = Client::query()
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'name', 'email']);

        if ($clients->isNotEmpty()) {
            $groups[] = [
                'label' => 'Clients',
                'icon'  => 'users',
                'items' => $clients->map(fn ($c) => [
                    'title'    => $c->name ?? '—',
                    'subtitle' => $c->email ?? 'Client',
                    'url'      => route('clients.show', $c->id),
                ])->all(),
            ];
        }

        // ─── Cost Codes ─────────────────────────────────────────────
        $costCodes = CostCode::query()
            ->where(function ($w) use ($like) {
                $w->where('code', 'like', $like)
                  ->orWhere('name', 'like', $like);
            })
            ->where('is_active', true)
            ->limit(self::PER_CATEGORY)
            ->get(['id', 'code', 'name']);

        if ($costCodes->isNotEmpty()) {
            $groups[] = [
                'label' => 'Cost Codes',
                'icon'  => 'hash',
                'items' => $costCodes->map(fn ($cc) => [
                    'title'    => "{$cc->code} — {$cc->name}",
                    'subtitle' => 'Cost Code',
                    'url'      => route('cost-codes.index') . '?search=' . urlencode($cc->code),
                ])->all(),
            ];
        }

        return response()->json([
            'query'  => $q,
            'count'  => collect($groups)->sum(fn ($g) => count($g['items'])),
            'groups' => $groups,
        ]);
    }
}
