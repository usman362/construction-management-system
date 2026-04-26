<?php

namespace App\Http\Controllers;

use App\Models\ClientDefaultMarkup;
use App\Models\CostCode;
use App\Models\Craft;
use App\Models\Equipment;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\EstimateSection;
use App\Models\Material;
use App\Models\Project;
use App\Services\EstimateConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstimateController extends Controller
{
    public function index(Project $project, Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($project, $request);
        }
        return view('estimates.index', ['project' => $project]);
    }

    private function dataTable(Project $project, Request $request): JsonResponse
    {
        $query = $project->estimates();
        $totalRecords = $project->estimates()->count();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        $columns = ['id', 'name', 'description', 'status', 'total_price'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(fn ($est) => [
                'id'           => $est->id,
                'name'         => $est->name,
                'description'  => $est->description ?? '—',
                'status'       => $est->status,
                'total_price'  => (float) $est->total_price,
                'total_amount' => (float) $est->total_amount,
                'actions'      => $est->id,
            ]),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'status'               => 'nullable|in:draft,submitted,sent_to_client,accepted,rejected,approved,revised,converted_to_project',
            'client_id'            => 'nullable|exists:clients,id',
            'estimate_type'        => 'nullable|string|max:32',
            'valid_until'          => 'nullable|date',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'terms_and_conditions' => 'nullable|string',
            'assumed_exclusions'   => 'nullable|string',
        ]);

        // Default the client_id to the project's client when the estimate is
        // built against an existing project — saves a lookup later.
        if (empty($validated['client_id']) && $project->client_id) {
            $validated['client_id'] = $project->client_id;
        }

        // Auto-derive duration_days when both start/end are provided.
        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $validated['duration_days'] = \Carbon\Carbon::parse($validated['start_date'])
                ->diffInDays(\Carbon\Carbon::parse($validated['end_date']));
        }

        $validated['status']     ??= 'draft';
        $validated['created_by']  = auth()->id();

        $estimate = $project->estimates()->create($validated);
        return response()->json(['message' => 'Estimate created.', 'estimate' => $estimate], 201);
    }

    /**
     * Defense in depth: implicit route binding doesn't enforce that the
     * URL's {project} matches the estimate's project_id. Without this guard,
     * an admin could view estimate from project A by visiting project B's
     * URL — the data leak is small but real. Called from every estimate
     * detail/action method below.
     */
    private function assertEstimateBelongsToProject(Project $project, Estimate $estimate): void
    {
        // null project_id is allowed (estimate created before project) but
        // when both are set, they MUST agree.
        if ($estimate->project_id !== null && $estimate->project_id !== $project->id) {
            abort(404);
        }
    }

    public function show(Project $project, Estimate $estimate): View
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $estimate->load([
            'client',
            'sections.lines.costCode',
            'sections.lines.craft',
            'sections.lines.material',
            'sections.lines.equipment',
            // Lines that haven't been assigned to a section yet — show in an
            // "Unsectioned" pseudo-bucket on the page.
            'lines' => fn ($q) => $q->whereNull('section_id'),
            'lines.costCode',
            'lines.craft',
            'lines.material',
            'lines.equipment',
        ]);

        return view('estimates.show', [
            'project'   => $project,
            'estimate'  => $estimate,
            'costCodes' => CostCode::orderBy('code')->get(['id', 'code', 'name']),
            'costTypes' => \App\Models\CostType::active()->orderBy('sort_order')->get(['id', 'code', 'name']),
            'crafts'    => Craft::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'base_hourly_rate']),
            'materials' => Material::orderBy('name')->get(['id', 'name', 'unit_of_measure', 'unit_cost']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'daily_rate', 'weekly_rate', 'monthly_rate']),
            'lineTypes' => EstimateLine::TYPES,
        ]);
    }

    public function edit(Project $project, Estimate $estimate): JsonResponse
    {
        return response()->json($estimate);
    }

    public function update(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'status'               => 'nullable|in:draft,submitted,sent_to_client,accepted,rejected,approved,revised,converted_to_project',
            'client_id'            => 'nullable|exists:clients,id',
            'estimate_type'        => 'nullable|string|max:32',
            'valid_until'          => 'nullable|date',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'terms_and_conditions' => 'nullable|string',
            'assumed_exclusions'   => 'nullable|string',
        ]);

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $validated['duration_days'] = \Carbon\Carbon::parse($validated['start_date'])
                ->diffInDays(\Carbon\Carbon::parse($validated['end_date']));
        }

        $estimate->update($validated);
        return response()->json(['message' => 'Estimate updated.', 'estimate' => $estimate->fresh()]);
    }

    public function destroy(Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $estimate->delete();
        return response()->json(['message' => 'Estimate deleted.']);
    }

    // ─── Phase 2: Convert estimate → project ───────────────────────────

    /**
     * Accept the estimate and turn its line items into a real project's
     * budget. Auto-creates a project if none is attached yet, otherwise
     * re-uses the existing project.
     *
     * Body params (all optional):
     *   - project_number  (override the auto-generated number)
     *   - name            (override the project name)
     *   - start_date / end_date
     */
    public function convertToProject(
        Request $request,
        Project $project,
        Estimate $estimate,
        EstimateConversionService $service
    ): JsonResponse {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $request->validate([
            'project_number' => 'nullable|string|max:50',
            'name'           => 'nullable|string|max:255',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $resultingProject = $service->convert($estimate, array_filter($data));
        $summary = $resultingProject->_conversion_summary ?? [];

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Estimate accepted and converted to project. Created %d budget line(s) and copied %d billable rate(s).',
                $summary['budget_lines_created'] ?? 0,
                $summary['billable_rates_copied'] ?? 0,
            ),
            'project_id'  => $resultingProject->id,
            'project_url' => route('projects.show', $resultingProject->id),
            'summary'     => $summary,
        ]);
    }

    /**
     * Phase 3: Mark an estimate as sent to the client (timestamped) and
     * optionally bump its status forward.
     */
    public function markSent(Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $estimate->update([
            'status'              => 'sent_to_client',
            'sent_to_client_date' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Estimate marked as sent to client.']);
    }

    /**
     * Phase 3: Record client acceptance/rejection (without converting yet —
     * conversion is a separate explicit action so the PM can review before
     * the budget is created).
     */
    public function recordResponse(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $request->validate([
            'response' => 'required|in:accepted,rejected',
        ]);

        $estimate->update([
            'status'               => $data['response'],
            'client_response_date' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Client response recorded.']);
    }

    /**
     * Phase 3: Stream a styled PDF of the estimate (sections, totals, terms).
     */
    public function downloadPdf(Project $project, Estimate $estimate)
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $estimate->load(['client', 'sections.lines.craft', 'sections.lines.material', 'sections.lines.equipment',
                         'lines' => fn ($q) => $q->whereNull('section_id')]);
        $project->load('client');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.estimate', [
            'project'  => $project,
            'estimate' => $estimate,
            'company'  => \App\Models\Setting::get('company_name', 'BuildTrack'),
        ]);

        $filename = 'estimate-' . ($estimate->estimate_number ?? $estimate->id) . '.pdf';
        return $pdf->download($filename);
    }

    // ─── Sections ──────────────────────────────────────────────────────

    public function storeSection(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
        ]);
        $data['sort_order'] ??= ($estimate->sections()->max('sort_order') ?? 0) + 1;

        $section = $estimate->sections()->create($data);
        return response()->json(['message' => 'Section added.', 'section' => $section], 201);
    }

    public function updateSection(Request $request, Project $project, Estimate $estimate, EstimateSection $section): JsonResponse
    {
        abort_unless($section->estimate_id === $estimate->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
        ]);
        $section->update($data);
        return response()->json(['message' => 'Section updated.', 'section' => $section->fresh()]);
    }

    public function destroySection(Project $project, Estimate $estimate, EstimateSection $section): JsonResponse
    {
        abort_unless($section->estimate_id === $estimate->id, 404);
        // Cascade-detach the lines (set section_id null) rather than delete them.
        $section->lines()->update(['section_id' => null]);
        $section->delete();
        $estimate->recalculateTotals();
        return response()->json(['message' => 'Section removed; lines moved to Unsectioned.']);
    }

    // ─── Lines ─────────────────────────────────────────────────────────

    public function addLine(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $this->validateLine($request);
        $data['estimate_id'] = $estimate->id;

        // If the user didn't pick a markup_percent, fall back to the client's
        // default for this line type (set in ClientDefaultMarkup).
        $data['markup_percent'] = $this->resolveMarkup($data, $estimate);

        $line = EstimateLine::create($data);
        return response()->json([
            'message' => 'Line added.',
            'line'    => $line->fresh(),
            'totals'  => $this->totalsFor($estimate),
        ], 201);
    }

    public function updateLine(Request $request, Project $project, EstimateLine $estimateLine): JsonResponse
    {
        $data = $this->validateLine($request);
        $estimateLine->update($data);
        return response()->json([
            'message' => 'Line updated.',
            'line'    => $estimateLine->fresh(),
            'totals'  => $this->totalsFor($estimateLine->estimate),
        ]);
    }

    public function removeLine(Project $project, EstimateLine $estimateLine): JsonResponse
    {
        $estimate = $estimateLine->estimate;
        $estimateLine->delete();
        return response()->json([
            'message' => 'Line removed.',
            'totals'  => $this->totalsFor($estimate),
        ]);
    }

    /**
     * Shared validation for add + update. Accepts every line shape; the model
     * observer figures out which fields actually matter based on line_type.
     */
    private function validateLine(Request $request): array
    {
        $rules = [
            'line_type'      => 'required|in:' . implode(',', array_keys(EstimateLine::TYPES)),
            'section_id'     => 'nullable|exists:estimate_sections,id',
            'sort_order'     => 'nullable|integer',
            'description'    => 'required|string|max:500',
            'cost_code_id'   => 'nullable|exists:cost_codes,id',
            'cost_type_id'   => 'nullable|exists:cost_types,id',

            // Labor fields
            'craft_id'             => 'nullable|exists:crafts,id',
            'hours'                => 'nullable|numeric|min:0',
            'hourly_cost_rate'     => 'nullable|numeric|min:0',
            'hourly_billable_rate' => 'nullable|numeric|min:0',

            // Material/equipment lookups
            'material_id'  => 'nullable|exists:materials,id',
            'equipment_id' => 'nullable|exists:equipment,id',

            // Generic qty/unit-cost (materials, equipment, sub, other)
            'quantity'  => 'nullable|numeric|min:0',
            'unit'      => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',

            // Pricing
            'markup_percent' => 'nullable|numeric|min:0|max:10',  // 10 = 1000% just in case
            'notes'          => 'nullable|string|max:1000',
        ];

        return $request->validate($rules);
    }

    /**
     * Pick the markup % to apply when the user didn't enter one.
     *
     * Order of preference:
     *   1. The value the user typed
     *   2. The client_default_markups row matching (client, cost_type)
     *   3. The client_default_markups row matching (client, NULL cost_type)
     *   4. Zero
     */
    private function resolveMarkup(array $data, Estimate $estimate): float
    {
        if (isset($data['markup_percent']) && $data['markup_percent'] !== null) {
            return (float) $data['markup_percent'];
        }

        $clientId = $estimate->client_id;
        $costTypeId = $data['cost_type_id'] ?? null;
        if (!$clientId) return 0.0;

        $row = ClientDefaultMarkup::query()
            ->where('client_id', $clientId)
            ->where(function ($q) use ($costTypeId) {
                $q->where('cost_type_id', $costTypeId)->orWhereNull('cost_type_id');
            })
            ->orderByRaw('cost_type_id IS NULL ASC')   // exact match first, NULL fallback after
            ->first();

        return $row ? $row->markupForType($data['line_type']) : 0.0;
    }

    /**
     * Bundle the live totals so the client UI can update without a full reload.
     */
    private function totalsFor(?Estimate $estimate): array
    {
        if (!$estimate) return [];
        $estimate = $estimate->fresh();
        return [
            'total_cost'     => (float) $estimate->total_cost,
            'total_price'    => (float) $estimate->total_price,
            'margin_percent' => (float) $estimate->margin_percent,
            'sections'       => $estimate->sections->map(fn ($s) => [
                'id'           => $s->id,
                'cost_amount'  => (float) $s->cost_amount,
                'price_amount' => (float) $s->price_amount,
            ])->all(),
        ];
    }
}
