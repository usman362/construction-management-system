<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Top-level / portfolio-wide Estimates module — added per Brenda's request
 * 04.25.2026 ("one big estimating module" in the side nav).
 *
 * Distinct from EstimateController, which is project-scoped (used for
 * change-order pricing inside a project). This controller surfaces ALL
 * estimates across all projects + standalone bids that don't have a project
 * yet.
 *
 * Standalone-bid workflow: when a user clicks "New Estimate" from the
 * portfolio, they pick a client first; project_id stays NULL until the
 * estimate is accepted and converted via Phase 2's
 * EstimateConversionService::convert(). At that point a Project gets
 * auto-created and project_id is back-filled.
 */
class EstimatePortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $query = Estimate::query()
            ->with(['client:id,name', 'project:id,project_number,name', 'creator:id,name'])
            // Hide change-order estimates from the portfolio — those live
            // inside the project's CO page (Brenda's "smaller one").
            ->where(function ($q) {
                $q->where('estimate_type', '!=', 'change_order')
                  ->orWhereNull('estimate_type');
            });

        // ─── Filters ──────────────────────────────────────────────
        if ($status = $request->input('status'))         $query->where('status', $status);
        if ($clientId = $request->input('client_id'))    $query->where('client_id', $clientId);
        if ($projectId = $request->input('project_id'))  $query->where('project_id', $projectId);
        if ($from = $request->input('from'))             $query->whereDate('created_at', '>=', $from);
        if ($to = $request->input('to'))                 $query->whereDate('created_at', '<=', $to);
        if ($search = trim((string) $request->input('q', ''))) {
            $like = '%' . $search . '%';
            $query->where(function ($w) use ($like) {
                $w->where('estimate_number', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        $estimates = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Roll-ups for the summary tiles at the top of the page — calculated
        // independently of pagination so the totals reflect the full filter,
        // not just the current page.
        // 2026-05-01 (Brenda): older estimates carry status='approved'
        // which wasn't in any summary bucket — all four cards rolled up
        // as $0 even when total_price was populated. Treating 'approved'
        // as Won (it's a synonym for 'accepted' from the legacy schema).
        $summaryQuery = (clone $query);
        $summary = [
            'total_count'  => (clone $summaryQuery)->count(),
            'pipeline'     => (float) (clone $summaryQuery)->whereIn('status', ['draft', 'submitted', 'sent_to_client'])->sum('total_price'),
            'won'          => (float) (clone $summaryQuery)->whereIn('status', ['accepted', 'approved', 'converted_to_project'])->sum('total_price'),
            'lost'         => (float) (clone $summaryQuery)->where('status', 'rejected')->sum('total_price'),
        ];

        return view('estimates.portfolio', [
            'estimates' => $estimates,
            'summary'   => $summary,
            'filters'   => $request->only(['status', 'client_id', 'project_id', 'from', 'to', 'q']),
            'clients'   => Client::orderBy('name')->get(['id', 'name']),
            'projects'  => Project::orderBy('project_number')->get(['id', 'project_number', 'name']),
            'statusLabels' => [
                'draft'                 => 'Draft',
                'submitted'             => 'Submitted',
                'sent_to_client'        => 'Sent',
                'accepted'              => 'Accepted',
                'approved'              => 'Approved', // legacy synonym of "Accepted"
                'rejected'              => 'Rejected',
                'converted_to_project'  => 'Converted',
            ],
        ]);
    }

    /**
     * "New Estimate" creation from the portfolio — accepts a client (no
     * project required; one gets auto-created on acceptance via Phase 2).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'client_id'        => 'required|exists:clients,id',
            'estimate_number'  => 'nullable|string|max:50',
            'description'      => 'nullable|string',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'valid_until'      => 'nullable|date',
        ]);

        if (empty($data['estimate_number'])) {
            // Auto-number: EST-YYYY-#### where #### is a per-year sequence.
            $year = now()->year;
            $lastNumber = Estimate::where('estimate_number', 'like', "EST-{$year}-%")
                ->orderByDesc('estimate_number')
                ->value('estimate_number');
            $nextSeq = $lastNumber
                ? ((int) substr($lastNumber, -4) + 1)
                : 1;
            $data['estimate_number'] = sprintf('EST-%d-%04d', $year, $nextSeq);
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $data['duration_days'] = \Carbon\Carbon::parse($data['start_date'])
                ->diffInDays(\Carbon\Carbon::parse($data['end_date']));
        }

        $data['estimate_type'] = 'standard';
        $data['status']        = 'draft';
        $data['created_by']    = auth()->id();

        $estimate = Estimate::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Estimate created.',
                'estimate' => $estimate,
                'url'      => route('estimates.portfolio.show', $estimate->id),
            ], 201);
        }

        return redirect()->route('estimates.portfolio.show', $estimate->id)
            ->with('success', 'Estimate created.');
    }

    /**
     * Spawn a draft project for a standalone estimate, then redirect into
     * the full project-scoped estimate builder.
     *
     * Called from the "Open as Project Draft" button on the standalone-bid
     * detail page. The project is created in 'bidding' status; it'll
     * transition to 'awarded'/'active' automatically when the user later
     * runs Phase 2 conversion (accept & convert).
     */
    public function spawnProject(Estimate $estimate): \Illuminate\Http\JsonResponse
    {
        if ($estimate->project_id) {
            // Already attached — just send them to the project-scoped page.
            return response()->json([
                'success' => true,
                'message' => 'Estimate is already linked to a project.',
                'url'     => route('projects.estimates.show', [$estimate->project_id, $estimate->id]),
            ]);
        }

        $project = \DB::transaction(function () use ($estimate) {
            $projNumber = 'DRAFT-' . str_pad((string) ($estimate->id), 4, '0', STR_PAD_LEFT);

            $project = Project::create([
                'project_number'  => $projNumber,
                'name'            => $estimate->name ?: 'Bid #' . $estimate->id,
                'client_id'       => $estimate->client_id,
                'status'          => 'bidding',
                'start_date'      => $estimate->start_date ?? now()->toDateString(),
                'end_date'        => $estimate->end_date ?? now()->addMonths(3)->toDateString(),
                'original_budget' => 0,
                'current_budget'  => 0,
                'estimate'        => $estimate->total_price,
                'contract_value'  => $estimate->total_price,
                'description'     => $estimate->description,
            ]);
            $estimate->update(['project_id' => $project->id]);
            return $project;
        });

        return response()->json([
            'success' => true,
            'message' => "Draft project {$project->project_number} created. Opening builder…",
            'url'     => route('projects.estimates.show', [$project->id, $estimate->id]),
        ]);
    }

    /**
     * Show a portfolio estimate. Re-uses the project-scoped show view so we
     * don't fork the UI — passes a "virtual" project derived from the
     * estimate's project_id (or a stub when standalone).
     */
    public function show(Estimate $estimate)
    {
        // If the estimate belongs to a project, redirect to the project-scoped
        // detail page so the full feature set (sections + lines + convert)
        // is reachable without a duplicate template.
        if ($estimate->project_id) {
            return redirect()->route('projects.estimates.show', [
                $estimate->project_id, $estimate->id,
            ]);
        }

        // Standalone bid (no project yet) — render a lighter view so the user
        // can edit metadata before conversion. After conversion, the redirect
        // above kicks in on subsequent visits.
        $estimate->load(['client', 'sections.lines.craft', 'sections.lines.material',
                         'sections.lines.equipment', 'lines' => fn ($q) => $q->whereNull('section_id')]);

        return view('estimates.portfolio-show', [
            'estimate'  => $estimate,
            'crafts'    => \App\Models\Craft::where('is_active', true)->orderBy('name')->get(['id', 'name', 'base_hourly_rate']),
            'materials' => \App\Models\Material::orderBy('name')->get(['id', 'name', 'unit_of_measure', 'unit_cost']),
            'equipment' => \App\Models\Equipment::orderBy('name')->get(['id', 'name', 'daily_rate']),
            'costCodes' => \App\Models\CostCode::orderBy('code')->get(['id', 'code', 'name']),
            'costTypes' => \App\Models\CostType::active()->orderBy('sort_order')->get(['id', 'code', 'name']),
            'lineTypes' => \App\Models\EstimateLine::TYPES,
        ]);
    }
}
