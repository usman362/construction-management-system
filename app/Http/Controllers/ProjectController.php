<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('projects.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Project::with(['client']);
        $totalRecords = Project::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        $filteredRecords = $query->count();

        // Order (column indices match DataTables: project #, name, client, status, start, budget)
        $columns = ['project_number', 'name', 'client_name', 'status', 'start_date', 'current_budget'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'project_number';
        $orderDir = $request->input('order.0.dir', 'asc');
        if ($orderCol === 'client_name') {
            $query->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
                ->orderBy('clients.name', $orderDir)
                ->select('projects.*');
        } else {
            $query->orderBy($orderCol, $orderDir);
        }

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'project_number' => $item->project_number,
                    'name' => $item->name,
                    'client_name' => $item->client->name ?? '',
                    'status' => $item->status,
                    'start_date' => $item->start_date,
                    'budget' => $item->current_budget,
                    'actions' => $item->id,
                ];
            }),
        ]);
    }

    /**
     * 2026-04-28 — Project creation is handled by the modal on the projects
     * index page (cleaner UX, single source of truth, no field-shape drift
     * between two forms — see the bug Brenda hit). The dedicated /projects/create
     * page was retired; this redirect keeps any old bookmarks/links working
     * by sending the user to the index with `?new=1`, which auto-opens the
     * "Add Project" modal.
     */
    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('projects.index', ['new' => 1]);
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // 2026-04-28 — Brenda's create-page bug:
        //   The dedicated /projects/create form sends `original_budget` (not
        //   `budget`), plus address/city/state/zip/substantial_completion_date/
        //   estimate fields the old validation rules didn't cover. Validation
        //   silently failed and the page just bounced back with no visible
        //   error. We now accept BOTH input shapes:
        //     - modal flow: posts `budget` (legacy from /projects index modal)
        //     - dedicated page flow: posts `original_budget` + extras
        //
        //   Whichever the user sent, we coerce into the canonical
        //   `original_budget` / `current_budget` columns at save time.
        $validated = $request->validate([
            'project_number' => 'required|unique:projects|string|max:50',
            'name'           => 'required|string|max:255',
            'client_id'      => 'required|exists:clients,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'substantial_completion_date' => 'nullable|date',

            // Either field name is OK; at least one with a value is required.
            'budget'          => 'nullable|numeric|min:0',
            'original_budget' => 'nullable|numeric|min:0',

            'estimate'              => 'nullable|numeric|min:0',
            'contract_value'        => 'nullable|numeric|min:0',
            'retainage_percent'     => 'nullable|numeric|min:0|max:99.99',
            'default_per_diem_rate' => 'nullable|numeric|min:0',

            'po_number' => 'nullable|string|max:100',
            'po_date'   => 'nullable|date',

            'status' => 'required|in:bidding,awarded,active,on_hold,completed,closed',
            'description' => 'nullable|string',

            // Project address (dedicated create page sends these)
            'address' => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:120',
            'state'   => 'nullable|string|max:50',
            'zip'     => 'nullable|string|max:20',

            // Geofence center (modal sends these on edit)
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:10|max:100000',
        ]);

        // Resolve which budget field was sent. The dedicated form uses
        // `original_budget`; the modal uses `budget`. Default to 0 if neither
        // was provided so the project still saves (a project at bidding stage
        // legitimately has no budget yet).
        $budget = (float) ($validated['original_budget'] ?? $validated['budget'] ?? 0);
        unset($validated['budget'], $validated['original_budget']);

        $project = Project::create(array_merge($validated, [
            'original_budget' => $budget,
            'current_budget'  => $budget,
        ]));

        // Dedicated create page submits as a regular HTML form (no AJAX).
        // Send them to the project show page with a success flash.
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('projects.show', $project)
                ->with('success', 'Project created successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    public function show(Project $project): View
    {
        $project->load([
            'client',
            'phases',
            'budgetLines.costCode',
            'budgetLines.costType',
            'changeOrders',
            'commitments.vendor',
            'commitments.costCode',
            'commitments.costType',
            'invoices.vendor',
            'invoices.costCode',
            'timesheets.employee',
            'documents.uploader',
        ]);

        // Purchase orders + billing invoices live on their own tables — load
        // them here so the Costs and Client Billing tabs can read them.
        $purchaseOrders = \App\Models\PurchaseOrder::with(['vendor', 'costCode', 'costType', 'parent'])
            ->where('project_id', $project->id)
            ->orderByDesc('date')
            ->get();

        $billingInvoices = class_exists(\App\Models\BillingInvoice::class)
            ? \App\Models\BillingInvoice::where('project_id', $project->id)->orderByDesc('id')->get()
            : collect();

        $budgetLines = $project->budgetLines;
        $changeOrders = $project->changeOrders;
        $commitments = $project->commitments;
        $invoices = $project->invoices;
        $timesheets = $project->timesheets ?? collect();

        // Client's definition of "committed cost" = vendor POs/subcontracts +
        // labor already booked on timesheets. Construction-industry standard:
        // anything we've already agreed/worked on counts as committed spend.
        // Rejected timesheets are excluded because they represent entries
        // the approver rolled back.
        $laborTimesheets = $timesheets->where('status', '!=', 'rejected');
        $laborCommitted = (float) $laborTimesheets->sum('total_cost');

        $budgetTotal = $budgetLines->sum('amount');
        $vendorCommitted = (float) $commitments->sum('amount');
        $committedTotal = $vendorCommitted + $laborCommitted;
        $invoicedTotal = $invoices->sum('amount');
        $coTotal = $changeOrders->where('status', 'approved')->sum('amount');

        // Cost summary grouped by cost code — now folds in labor cost from
        // timesheets against the timesheet's own cost_code_id, so the per-
        // code totals match what the budget-line table shows below.
        $commitmentsByCostCode = $commitments->groupBy('cost_code_id');
        $invoicesByCostCode    = $invoices->groupBy('cost_code_id');
        $laborByCostCode       = $laborTimesheets->groupBy('cost_code_id');

        $allCostCodeIds = collect()
            ->merge($commitmentsByCostCode->keys())
            ->merge($laborByCostCode->keys())
            ->unique();

        $costSummary = $allCostCodeIds->map(function ($ccId) use ($commitmentsByCostCode, $laborByCostCode) {
            $vendor = (float) ($commitmentsByCostCode[$ccId] ?? collect())->sum('amount');
            $labor  = (float) ($laborByCostCode[$ccId] ?? collect())->sum('total_cost');

            // Prefer a commitment's costCode (richer relation) for display;
            // fall back to the first timesheet's costCode when a cost code is
            // labor-only (no vendor commitment yet).
            $sampleCostCode = ($commitmentsByCostCode[$ccId] ?? collect())->first()?->costCode
                ?? ($laborByCostCode[$ccId] ?? collect())->first()?->costCode;

            return (object) [
                'code'        => $sampleCostCode->code ?? 'N/A',
                'description' => $sampleCostCode->name ?? 'N/A',
                'vendor'      => $vendor,
                'labor'       => $labor,
                'total'       => $vendor + $labor,
            ];
        })->values();

        $revisedBudget = ($project->current_budget ?? 0) + $coTotal;
        $percentComplete = $revisedBudget > 0
            ? min(round(($committedTotal / $revisedBudget) * 100, 1), 100)
            : 0;

        foreach ($budgetLines as $line) {
            $lineVendor = (float) ($commitmentsByCostCode[$line->cost_code_id] ?? collect())->sum('amount');
            $lineLabor  = (float) ($laborByCostCode[$line->cost_code_id] ?? collect())->sum('total_cost');
            $line->committed_vendor = $lineVendor;
            $line->committed_labor  = $lineLabor;
            $line->committed = $lineVendor + $lineLabor;
            $line->invoiced = ($invoicesByCostCode[$line->cost_code_id] ?? collect())->sum('amount');
            $revised = $line->revised_amount ?: $line->budget_amount;
            $line->percent_complete = $revised > 0 ? round(($line->committed / $revised) * 100, 1) : 0;
        }

        return view('projects.show', [
            'project' => $project,
            'budgetLines' => $budgetLines,
            'changeOrders' => $changeOrders,
            'commitments' => $commitments,
            'invoices' => $invoices,
            'timesheets' => $timesheets,
            'costSummary' => $costSummary,
            'purchaseOrders' => $purchaseOrders,
            'billingInvoices' => $billingInvoices,
            'budgetTotal' => $budgetTotal,
            'committedTotal' => $committedTotal,
            'vendorCommitted' => $vendorCommitted,
            'laborCommitted' => $laborCommitted,
            'invoicedTotal' => $invoicedTotal,
            'coTotal' => $coTotal,
            'balance' => ($budgetTotal + $coTotal) - $committedTotal,
            'percentComplete' => $percentComplete,
            'allCostCodes' => \App\Models\CostCode::active()->orderBy('code')->get(['id', 'code', 'name']),
            'allCostTypes' => \App\Models\CostType::active()->orderBy('sort_order')->get(['id', 'code', 'name']),
            'assignableUsers' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Project $project): JsonResponse
    {
        $project->load(['client']);
        $clients = Client::all();

        return response()->json([
            'project' => $project,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Mirrors store() — accepts both `budget` (modal) and `original_budget`
        // (dedicated edit page) plus the address/extras. See store() for the
        // bug context (Brenda 04.28.2026).
        $validated = $request->validate([
            'project_number' => "required|unique:projects,project_number,{$project->id}|string|max:50",
            'name'           => 'required|string|max:255',
            'client_id'      => 'required|exists:clients,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'substantial_completion_date' => 'nullable|date',

            'budget'          => 'nullable|numeric|min:0',
            'original_budget' => 'nullable|numeric|min:0',

            'estimate'              => 'nullable|numeric|min:0',
            'contract_value'        => 'nullable|numeric|min:0',
            'retainage_percent'     => 'nullable|numeric|min:0|max:99.99',
            'default_per_diem_rate' => 'nullable|numeric|min:0',

            'po_number' => 'nullable|string|max:100',
            'po_date'   => 'nullable|date',

            'status'      => 'required|in:bidding,awarded,active,on_hold,completed,closed',
            'description' => 'nullable|string',

            'address' => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:120',
            'state'   => 'nullable|string|max:50',
            'zip'     => 'nullable|string|max:20',

            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:10|max:100000',
        ]);

        // Resolve which budget field was sent (or keep current if neither).
        $budget = $validated['original_budget'] ?? $validated['budget'] ?? null;
        unset($validated['budget'], $validated['original_budget']);

        $updates = $validated;
        if ($budget !== null) {
            // Edit page treats the input as the project's CURRENT budget; the
            // original budget stays whatever it was at create time.
            $updates['current_budget'] = (float) $budget;
        }

        $project->update($updates);

        // Dedicated edit page submits as a regular HTML form (no AJAX).
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('projects.show', $project)
                ->with('success', 'Project updated successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'project' => $project->fresh(),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }
}
