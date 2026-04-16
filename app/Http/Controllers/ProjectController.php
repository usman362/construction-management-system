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

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        return view('projects.create', ['clients' => $clients]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_number' => 'required|unique:projects|string|max:50',
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'po_date' => 'nullable|date',
            'status' => 'required|in:bidding,awarded,active,on_hold,completed,closed',
            'description' => 'nullable|string',
        ]);

        $budget = $validated['budget'];
        unset($validated['budget']);

        $project = Project::create(array_merge($validated, [
            'original_budget' => $budget,
            'current_budget' => $budget,
        ]));

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
            'budgetLines',
            'changeOrders',
            'commitments',
            'invoices',
            'timesheets.employee',
            'documents.uploader',
        ]);

        $budgetLines = $project->budgetLines;
        $changeOrders = $project->changeOrders;
        $commitments = $project->commitments;
        $invoices = $project->invoices;
        $timesheets = $project->timesheets ?? collect();

        $budgetTotal = $budgetLines->sum('amount');
        $committedTotal = $commitments->sum('amount');
        $invoicedTotal = $invoices->sum('amount');
        $coTotal = $changeOrders->where('status', 'approved')->sum('amount');

        // Cost summary grouped by cost code
        $costSummary = $commitments->groupBy('cost_code_id')->map(function ($items, $key) {
            return (object) [
                'code' => $items->first()->costCode->code ?? 'N/A',
                'description' => $items->first()->costCode->name ?? 'N/A',
                'total' => $items->sum('amount'),
            ];
        })->values();

        $revisedBudget = ($project->current_budget ?? 0) + $coTotal;
        $percentComplete = $revisedBudget > 0
            ? min(round(($committedTotal / $revisedBudget) * 100, 1), 100)
            : 0;

        $commitmentsByCostCode = $commitments->groupBy('cost_code_id');
        $invoicesByCostCode = $invoices->groupBy('cost_code_id');

        foreach ($budgetLines as $line) {
            $line->committed = ($commitmentsByCostCode[$line->cost_code_id] ?? collect())->sum('amount');
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
            'budgetTotal' => $budgetTotal,
            'committedTotal' => $committedTotal,
            'invoicedTotal' => $invoicedTotal,
            'coTotal' => $coTotal,
            'balance' => ($budgetTotal + $coTotal) - $committedTotal,
            'percentComplete' => $percentComplete,
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

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'project_number' => "required|unique:projects,project_number,{$project->id}|string|max:50",
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'po_number' => 'nullable|string|max:100',
            'po_date' => 'nullable|date',
            'status' => 'required|in:bidding,awarded,active,on_hold,completed,closed',
            'description' => 'nullable|string',
        ]);

        $budget = $validated['budget'];
        unset($validated['budget']);

        $project->update(array_merge($validated, [
            'current_budget' => $budget,
        ]));

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
