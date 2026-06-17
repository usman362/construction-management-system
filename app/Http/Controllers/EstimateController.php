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
use App\Services\EstimateAiService;
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
            // 2026-06-04 (Brenda): job site location + job number on
            // the estimate header.
            'location'             => 'nullable|string|max:255',
            'job_number'           => 'nullable|string|max:100',
        ]);

        // Default the client_id to the project's client when the estimate is
        // built against an existing project — saves a lookup later.
        if (empty($validated['client_id']) && $project->client_id) {
            $validated['client_id'] = $project->client_id;
        }
        // Default job_number to the project's project_number so the same
        // job carries through unless the user types something else.
        if (empty($validated['job_number']) && $project->project_number) {
            $validated['job_number'] = $project->project_number;
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
        // when both are set, they MUST agree. Use loose comparison so type
        // jitter (string DB driver vs int route binding) doesn't trip us.
        // 2026-05-23 (Brenda): instead of a raw 404 (which surfaces to the
        // user as a generic "could not save" with no actionable info),
        // throw a 422 with a clear message AND log the mismatch so we can
        // diagnose URL drift. Includes both ids in the message so support
        // can repro fast.
        if ($estimate->project_id !== null && (int) $estimate->project_id !== (int) $project->id) {
            \Log::warning('Estimate-project mismatch on action', [
                'url_project_id'      => $project->id,
                'estimate_id'         => $estimate->id,
                'estimate_project_id' => $estimate->project_id,
                'url'                 => request()->fullUrl(),
            ]);
            abort(422, "This estimate belongs to project #{$estimate->project_id} but the URL points at project #{$project->id}. Reload the page from the correct project's estimate list and try again.");
        }
    }

    /**
     * 2026-05-10 (Brenda): show() was hard-404'ing when the URL's project
     * didn't match the estimate's project_id — happens when an estimate
     * gets moved between projects (or when an old bookmark/stale link is
     * clicked). Instead of dead-ending the user, redirect to the
     * estimate's actual project so they land on a working page.
     */
    public function show(Project $project, Estimate $estimate)
    {
        // CAST TO INT before comparing — Eloquent returns project_id as
        // string in some configs, and strict !== against an int $project->id
        // caused an infinite redirect loop here on 2026-05-10.
        $estProjectId  = $estimate->project_id !== null ? (int) $estimate->project_id : null;
        $urlProjectId  = (int) $project->id;
        if ($estProjectId !== null && $estProjectId !== $urlProjectId) {
            $realProject = Project::find($estProjectId);
            if ($realProject) {
                return redirect()->route('projects.estimates.show', [$realProject->id, $estimate->id]);
            }
            abort(404, 'Estimate is linked to a project that no longer exists.');
        }
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

        // 2026-05-23 (Brenda): pass the project-level billable rate per
        // craft so the Labor tile's live preview can show the SAME
        // numbers the server will use when it creates the lines. Falls
        // through to craft master if the project has no override.
        $crafts = Craft::where('is_active', true)->orderBy('name')
            ->get(['id', 'code', 'name', 'base_hourly_rate', 'overtime_multiplier', 'billable_rate', 'ot_billable_rate']);

        $pbrByCraft = \App\Models\ProjectBillableRate::where('project_id', $project->id)
            ->whereNull('employee_id')
            ->orderByDesc('effective_date')
            ->get()
            ->keyBy('craft_id');

        foreach ($crafts as $c) {
            $row = $pbrByCraft->get($c->id);
            if ($row) {
                if ($row->base_hourly_rate)     $c->base_hourly_rate    = $row->base_hourly_rate;
                if ($row->base_ot_hourly_rate)  $c->setAttribute('base_ot_hourly_rate', $row->base_ot_hourly_rate);
                if ($row->straight_time_rate)   $c->billable_rate       = $row->straight_time_rate;
                if ($row->overtime_rate)        $c->ot_billable_rate    = $row->overtime_rate;
            }
        }

        // Group ALL lines by template section for the T&M view.
        $allLines = $estimate->lines()->with(['costCode', 'craft', 'material', 'equipment'])->orderBy('sort_order')->get();
        $laborByCategory = [];
        foreach (EstimateLine::LABOR_CATEGORIES as $cat => $label) {
            $laborByCategory[$cat] = $allLines->where('line_type', 'labor')->where('labor_category', $cat)->values();
        }
        $materialLines = $allLines->where('line_type', 'material')->values();
        $equipmentLines3p  = $allLines->where('line_type', 'equipment')->where('equipment_category', '3rd_party')->values();
        $equipmentLinesCoe = $allLines->where('line_type', 'equipment')->where('equipment_category', 'company_owned')->values();
        $subcontractorLines = $allLines->where('line_type', 'subcontractor')->values();
        $otherLines = $allLines->where('line_type', 'other')->values();
        // Lines with no labor_category set (legacy data) — show in a fallback section
        $uncategorizedLabor = $allLines->where('line_type', 'labor')->whereNull('labor_category')->values();

        return view('estimates.show', [
            'project'   => $project,
            'estimate'  => $estimate,
            // 2026-06-17 (Brenda): project-scoped phase codes. effectiveCostCodes()
            // returns the project's enabled list, or full library if nothing's set.
            'costCodes' => $project->effectiveCostCodes(),
            'costTypes' => \App\Models\CostType::active()->orderBy('sort_order')->get(['id', 'code', 'name']),
            'crafts'    => $crafts,
            'materials' => Material::orderBy('name')->get(['id', 'name', 'unit_of_measure', 'unit_cost']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'daily_rate', 'weekly_rate', 'monthly_rate']),
            'lineTypes' => EstimateLine::TYPES,
            'laborCategories'    => EstimateLine::LABOR_CATEGORIES,
            'laborByCategory'    => $laborByCategory,
            'materialLines'      => $materialLines,
            'equipmentLines3p'   => $equipmentLines3p,
            'equipmentLinesCoe'  => $equipmentLinesCoe,
            'subcontractorLines' => $subcontractorLines,
            'otherLines'         => $otherLines,
            'uncategorizedLabor' => $uncategorizedLabor,
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
            'location'                  => 'nullable|string|max:255',
            'job_number'                => 'nullable|string|max:100',
            'project_duration_weeks'    => 'nullable|integer|min:0|max:999',
            'work_schedule'             => 'nullable|string|max:10',
            'field_staff_duration_weeks'=> 'nullable|integer|min:0|max:999',
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

        // 2026-05-11 (Brenda): map legacy modal fields onto the new schema.
        //   - Default line_type to 'other' (modal doesn't expose it)
        //   - `labor_hours` (legacy) → `hours` (new column EstimateLine reads)
        //   - `amount` alone (no qty/unit_cost) → treat as quantity=1 × unit_cost=amount
        //     so EstimateLine::recalculate() produces the right cost_amount.
        // 2026-05-23 (KH): auto-classify labor-only entries as line_type='labor'
        //   so they get treated correctly downstream (vs landing as 'other'
        //   with zero amount + zero quantity which looks like junk on the
        //   estimate sheet).
        if (! empty($data['labor_hours']) && empty($data['hours'])) {
            $data['hours'] = $data['labor_hours'];
        }
        unset($data['labor_hours']);
        if (! empty($data['amount']) && empty($data['quantity']) && empty($data['unit_cost'])) {
            $data['quantity']  = 1;
            $data['unit_cost'] = $data['amount'];
        }
        unset($data['amount']);
        if (empty($data['line_type'])) {
            $hasLabor    = (float) ($data['hours']    ?? 0) > 0;
            $hasMoney    = (float) ($data['quantity'] ?? 0) > 0 || (float) ($data['unit_cost'] ?? 0) > 0;
            $data['line_type'] = ($hasLabor && ! $hasMoney) ? 'labor' : 'other';
        }
        // 2026-05-23 (KH): browsers don't submit unchecked checkboxes, so an
        // absent `is_billable` could be either "wasn't on the form" (=true)
        // or "user unchecked it" (=false). Use the explicit Request->boolean
        // helper which is_billable=0 → false; absent → false. The Add Line
        // modal sends a hidden 'is_billable_present=1' so we can tell the
        // form actually rendered the field — when present but the checkbox
        // is unchecked, save false; otherwise default to true.
        if ($request->has('is_billable_present')) {
            $data['is_billable'] = $request->boolean('is_billable');
        } else {
            $data['is_billable'] = $data['is_billable'] ?? true;
        }

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
     * 2026-05-23 (KH WBS — "ESTIMATE - LABOR" tab + Misc tab math):
     * Labor tile shortcut. User picks Craft + Qty workers + Hrs/Day +
     * Duration days; we compute ST/OT split (max ST = 40 hrs/wk per
     * person, anything over → OT) and create 1 or 2 EstimateLine rows
     * (one for ST, one for OT if applicable) so the estimate reflects
     * the labor build-up exactly the way KH sketched it.
     */
    public function addLaborBundle(Request $request, Project $project, Estimate $estimate): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $request->validate([
            'craft_id'       => 'required|exists:crafts,id',
            'classification' => 'nullable|string|max:100',
            'section_id'     => 'nullable|exists:estimate_sections,id',
            'qty'            => 'required|integer|min:1|max:500',
            'hrs_per_day'    => 'required|numeric|min:0|max:24',
            'duration_days'  => 'required|numeric|min:1|max:730',
            'markup_percent' => 'nullable|numeric|min:0|max:10',
            'is_billable'    => 'nullable|boolean',
        ]);

        $craft = \App\Models\Craft::findOrFail($data['craft_id']);
        $qty   = (int)   $data['qty'];
        $hpd   = (float) $data['hrs_per_day'];
        $dur   = (float) $data['duration_days'];

        // KH Misc tab formula: Max ST = 40 hrs / week / person.
        $weeks         = $dur / 7;
        $maxStTotal    = 40 * $weeks * $qty;
        $totalHrs      = $qty * $hpd * $dur;
        $stHrs         = min($totalHrs, $maxStTotal);
        $otHrs         = max(0, $totalHrs - $maxStTotal);

        // 2026-05-23 (Brenda bug): Labor tile was pulling billable from
        // the craft master only — when a project has its own
        // ProjectBillableRate row for the craft (the simplified flow
        // where she types ST/OT billable directly), use that instead.
        // Look-up order: project-billable-rate for (project, craft) →
        // craft master → 1.5× ST fallback for OT.
        $pbr = \App\Models\ProjectBillableRate::where('project_id', $project->id)
            ->where('craft_id', $craft->id)
            ->whereNull('employee_id')
            ->orderByDesc('effective_date')
            ->first();

        $stRate = $pbr && $pbr->base_hourly_rate
            ? (float) $pbr->base_hourly_rate
            : (float) ($craft->base_hourly_rate ?? 0);
        $otMult = (float) ($craft->overtime_multiplier ?? 1.5);
        $otRate = $pbr && $pbr->base_ot_hourly_rate
            ? (float) $pbr->base_ot_hourly_rate
            : $stRate * $otMult;
        $billSt = $pbr && $pbr->straight_time_rate
            ? (float) $pbr->straight_time_rate
            : (float) ($craft->billable_rate ?? 0);
        $billOt = $pbr && $pbr->overtime_rate
            ? (float) $pbr->overtime_rate
            : (float) ($craft->ot_billable_rate ?? $billSt * $otMult);

        $markup     = $data['markup_percent']
            ?? $this->resolveMarkup(['cost_type_id' => null], $estimate);
        $isBillable = array_key_exists('is_billable', $data)
            ? (bool) $data['is_billable']
            : true;

        $classification = trim((string) ($data['classification'] ?? ''));
        $baseDesc       = $craft->name . ($classification !== '' ? " — {$classification}" : '')
                        . " (Qty {$qty} × {$hpd} hrs/day × {$dur} days)";

        // 2026-05-23 (Brenda): single labor line carries both ST and OT
        // so the user gets one row to edit instead of two separate entries.
        $line = EstimateLine::create([
            'estimate_id'             => $estimate->id,
            'section_id'              => $data['section_id'] ?? null,
            'line_type'               => EstimateLine::TYPE_LABOR,
            'craft_id'                => $craft->id,
            'description'             => $baseDesc,
            'hours'                   => $stHrs,
            'hourly_cost_rate'        => $stRate,
            'hourly_billable_rate'    => $billSt,
            'ot_hours'                => $otHrs > 0 ? $otHrs : null,
            'ot_hourly_cost_rate'     => $otHrs > 0 ? $otRate : null,
            'ot_hourly_billable_rate' => $otHrs > 0 ? $billOt : null,
            'markup_percent'          => $markup,
            'is_billable'             => $isBillable,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Labor line created (ST/OT split: '
                . number_format($stHrs, 1) . ' / ' . number_format($otHrs, 1) . ' hrs).',
            'created'  => 1,
            'line_id'  => $line->id,
            'st_hours' => $stHrs,
            'ot_hours' => $otHrs,
            'totals'   => $this->totalsFor($estimate),
        ], 201);
    }

    /**
     * 2026-05-12 (Brenda — Phase 6 recommendation): AI Estimate Builder.
     *
     * POST { scope: string } → AI returns suggested sections + line items.
     * Nothing is committed to the estimate — UI shows the suggestions with
     * checkboxes so Brenda can selectively add lines.
     */
    public function aiSuggest(Request $request, Project $project, Estimate $estimate, EstimateAiService $ai): JsonResponse
    {
        $this->assertEstimateBelongsToProject($project, $estimate);
        $data = $request->validate([
            'scope' => 'required|string|min:20|max:20000',
        ]);

        try {
            $result = $ai->suggestFromScope($data['scope'], [
                'project_name' => $project->name,
                'client_name'  => $project->client?->name,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI suggest failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'summary'  => $result['summary'],
            'sections' => $result['sections'],
        ]);
    }

    /**
     * Shared validation for add + update. Accepts every line shape; the model
     * observer figures out which fields actually matter based on line_type.
     */
    private function validateLine(Request $request): array
    {
        // 2026-05-11 BUG FIX (Brenda): legacy "Add Line Item" modal sends
        // `amount` + `labor_hours` and does NOT send `line_type`. Validation
        // was rejecting because line_type was required. Made line_type
        // nullable (defaults to 'other' in addLine), and accept the legacy
        // field names so they don't get silently dropped from validated().
        $rules = [
            'line_type'      => 'nullable|in:' . implode(',', array_keys(EstimateLine::TYPES)),
            'labor_category' => 'nullable|in:' . implode(',', array_keys(EstimateLine::LABOR_CATEGORIES)),
            'section_id'     => 'nullable|exists:estimate_sections,id',
            'sort_order'     => 'nullable|integer',
            'description'    => 'required|string|max:500',
            'cost_code_id'   => 'nullable|exists:cost_codes,id',
            'cost_type_id'   => 'nullable|exists:cost_types,id',

            // Labor crew-scheduling (T&M template)
            'work_schedule'  => 'nullable|string|max:10',
            'role'           => 'nullable|string|max:100',
            'crew_size'      => 'nullable|integer|min:0|max:999',
            'weeks'          => 'nullable|numeric|min:0|max:999',
            'days_per_week'  => 'nullable|integer|min:0|max:7',
            'hours_per_day'  => 'nullable|numeric|min:0|max:24',

            'craft_id'             => 'nullable|exists:crafts,id',
            'hours'                => 'nullable|numeric|min:0',
            'hourly_cost_rate'     => 'nullable|numeric|min:0',
            'hourly_billable_rate' => 'nullable|numeric|min:0',
            'ot_hours'                => 'nullable|numeric|min:0',
            'ot_hourly_cost_rate'     => 'nullable|numeric|min:0',
            'ot_hourly_billable_rate' => 'nullable|numeric|min:0',
            'premium_hours'                => 'nullable|numeric|min:0',
            'premium_hourly_cost_rate'     => 'nullable|numeric|min:0',
            'premium_hourly_billable_rate' => 'nullable|numeric|min:0',

            'material_id'        => 'nullable|exists:materials,id',
            'vendor_name'        => 'nullable|string|max:255',
            'subcontractor_name' => 'nullable|string|max:255',
            'discipline'         => 'nullable|string|max:255',
            'equipment_id'       => 'nullable|exists:equipment,id',
            'equipment_category' => 'nullable|in:' . implode(',', array_keys(EstimateLine::EQUIPMENT_CATEGORIES)),
            'duration_uom'       => 'nullable|in:daily,weekly,monthly',
            'equipment_duration' => 'nullable|numeric|min:0',
            'fuel_cost'          => 'nullable|numeric|min:0',

            'quantity'  => 'nullable|numeric|min:0',
            'unit'      => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',

            'quote_amount'   => 'nullable|numeric|min:0',
            'freight_amount' => 'nullable|numeric|min:0',
            'tax_amount'     => 'nullable|numeric|min:0',

            'amount'      => 'nullable|numeric|min:0',
            'labor_hours' => 'nullable|numeric|min:0',

            'markup_percent' => 'nullable|numeric|min:0|max:10',
            'notes'          => 'nullable|string|max:1000',
            'is_billable'    => 'nullable|boolean',
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
