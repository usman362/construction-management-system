<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use Illuminate\View\View;

/**
 * 2026-05-23 (Brenda): "There needs to be a set up tab / template
 * this needs to contain all pertenant information. Labor Rates,
 * Equipment Rates, Project Markups etc."
 *
 * Project Setup is a single dashboard that pulls the three things
 * KH needs to see before she starts estimating on a project:
 *   1. Project details (name, duration, dates, PO number, etc.)
 *   2. Labor Rates per craft (project-level, from project_billable_rates)
 *   3. Equipment Rates available (catalog, with project usage if any)
 *
 * Each section links out to the existing detailed editor pages
 * (Billable Rates, Equipment master). Setup itself is read-only —
 * a centralized "what's in this project's template" view that
 * mirrors Brenda's Excel Estimate Summary header strip.
 */
class ProjectSetupController extends Controller
{
    public function show(Project $project): View
    {
        $project->load('client');

        // Project-level billable rates (the simplified flow Brenda just
        // got — Base ST, Base OT, ST Billable, OT Billable typed direct).
        $rates = ProjectBillableRate::query()
            ->where('project_id', $project->id)
            ->with('craft:id,code,name', 'employee:id,first_name,last_name')
            ->orderBy('craft_id')
            ->orderByDesc('effective_date')
            ->get();

        // Crafts that DO NOT yet have a project-level rate — surfaced as
        // a gap so the user knows what's missing before starting the estimate.
        $craftsWithRate = $rates->pluck('craft_id')->filter()->unique();
        $missingCrafts  = Craft::where('is_active', true)
            ->whereNotIn('id', $craftsWithRate)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'base_hourly_rate', 'billable_rate']);

        $equipment = Equipment::orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'type', 'daily_rate', 'weekly_rate', 'monthly_rate']);

        return view('projects.setup', [
            'project'        => $project,
            'rates'          => $rates,
            'missingCrafts'  => $missingCrafts,
            'equipment'      => $equipment,
        ]);
    }
}
