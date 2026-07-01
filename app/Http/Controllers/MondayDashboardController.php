<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\DailyLog;
use App\Models\EquipmentAssignment;
use App\Models\Project;
use App\Models\Rfi;
use App\Models\Timesheet;
use Illuminate\View\View;
use Carbon\Carbon;

/**
 * "Brenda's Monday Morning Dashboard" — Phase 3 (2026-05-12).
 *
 * A focused single-page view designed to be opened first thing Monday
 * morning. Surfaces exactly the things Brenda needs to act on before
 * her week kicks off:
 *
 *   1. Last week (prior Mon-Sun): labor cost vs budget per project,
 *      red/green status, hours totals, biggest variances at the top.
 *   2. Anomalies: timesheets that don't match a daily log (likely typos
 *      or missed approvals), projects with cost spikes vs trailing avg.
 *   3. Pending timesheet approvals — one-click bulk-approve last week.
 *   4. Equipment 3+ weeks past expected_return_date (rental clock burning).
 *   5. Projected payroll for THIS week (Mon-today actual + projection).
 *   6. Open RFIs (with priority + needed_by) and pending Change Orders.
 *
 * Separate from the main /dashboard (Phase 7C) which is a real-time
 * "what's happening right now" view. This one is weekly-rhythm focused.
 */
class MondayDashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        // Last week = the prior Mon-Sun pair (not this-week-so-far).
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd   = $now->copy()->subWeek()->endOfWeek();
        // This week = Monday of the current week through Sunday.
        $thisWeekStart = $now->copy()->startOfWeek();
        $thisWeekEnd   = $now->copy()->endOfWeek();

        return view('dashboard.monday', [
            'now'           => $now,
            'lastWeek'      => ['start' => $lastWeekStart, 'end' => $lastWeekEnd],
            'thisWeek'      => ['start' => $thisWeekStart, 'end' => $thisWeekEnd],
            'laborRollup'   => $this->laborRollupByProject($lastWeekStart, $lastWeekEnd),
            'anomalies'     => $this->detectAnomalies($lastWeekStart, $lastWeekEnd),
            'approvals'     => $this->pendingApprovals($lastWeekStart, $lastWeekEnd),
            'overdueEq'     => $this->equipmentOverdue(),
            'projectedThisWeek' => $this->projectedPayrollThisWeek($thisWeekStart, $thisWeekEnd),
            'openRfis'      => $this->openRfis(),
            'pendingCOs'    => $this->pendingChangeOrders(),
        ]);
    }

    /**
     * Per-project labor totals for a given Mon-Sun range, joined against the
     * project's current_budget (or estimate fallback) so we can color rows
     * red/yellow/green based on burn percent.
     */
    private function laborRollupByProject(Carbon $start, Carbon $end): array
    {
        $rows = Timesheet::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereIn('status', ['approved', 'submitted'])
            ->selectRaw('
                project_id,
                SUM(regular_hours)     as reg_hours,
                SUM(overtime_hours)    as ot_hours,
                SUM(double_time_hours) as dt_hours,
                SUM(total_cost)        as total_cost,
                COUNT(*)               as entries
            ')
            ->groupBy('project_id')
            ->get();

        // Pull the projects + each one's budget figure in one batch.
        $projectIds = $rows->pluck('project_id')->filter()->unique();
        $projects = Project::with('budgetLines')
            ->whereIn('id', $projectIds)
            ->get(['id', 'project_number', 'name', 'estimate', 'current_budget', 'contract_value', 'status']);

        $result = [];
        foreach ($rows as $r) {
            $proj = $projects->firstWhere('id', $r->project_id);
            if (! $proj) continue;

            // Budget fallback: current_budget → sum(budget_lines.revised|amount) → estimate
            $budget = (float) ($proj->current_budget ?? 0);
            if ($budget <= 0) {
                $budget = (float) $proj->budgetLines->sum(fn ($l) => (float) ($l->revised_amount ?? $l->budget_amount ?? 0));
            }
            if ($budget <= 0) {
                $budget = (float) ($proj->estimate ?? 0);
            }

            // Burn % is "this week's cost vs total budget" — useful as a
            // weekly sanity check (anything over a few % per week deserves
            // a look depending on project length).
            $weekBurnPct = $budget > 0 ? ($r->total_cost / $budget) * 100 : null;

            $result[] = (object) [
                'project'       => $proj,
                'reg_hours'     => (float) $r->reg_hours,
                'ot_hours'      => (float) $r->ot_hours,
                'dt_hours'      => (float) $r->dt_hours,
                'total_hours'   => (float) ($r->reg_hours + $r->ot_hours + $r->dt_hours),
                'total_cost'    => (float) $r->total_cost,
                'entries'       => (int) $r->entries,
                'budget'        => $budget,
                'week_burn_pct' => $weekBurnPct,
                // Color thresholds: >10%/week = red flag (over-pace for any
                // multi-month project), 5-10%/week = yellow, under = green.
                'burn_state'    => $this->burnState($weekBurnPct),
            ];
        }

        // Sort biggest spend first so the top row is always the one that
        // matters most.
        usort($result, fn ($a, $b) => $b->total_cost <=> $a->total_cost);
        return $result;
    }

    private function burnState(?float $pct): string
    {
        if ($pct === null) return 'unknown';
        if ($pct >= 10)    return 'red';
        if ($pct >= 5)     return 'yellow';
        return 'green';
    }

    /**
     * Best-effort anomaly hints. Two checks for now (both cheap):
     *
     *  - "Hours but no daily log": projects that booked labor on a date
     *    with no DailyLog row (a foreman likely forgot to submit it).
     *  - "Daily log says no work" mismatches: skipped for now — would
     *    need NLP on daily log notes. Can layer in later.
     */
    private function detectAnomalies(Carbon $start, Carbon $end): array
    {
        // Pull every (project_id, date) pair where timesheets exist last week
        $tsKeys = Timesheet::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereIn('status', ['approved', 'submitted'])
            ->selectRaw('project_id, date, SUM(total_hours) as h, SUM(total_cost) as c, COUNT(*) as entries')
            ->groupBy('project_id', 'date')
            ->get();

        // Pull all daily logs in the same window
        $logKeys = DailyLog::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get(['id', 'project_id', 'date'])
            ->map(fn ($l) => $l->project_id . '|' . $l->date->toDateString())
            ->flip();

        $missingLog = [];
        foreach ($tsKeys as $k) {
            $key = $k->project_id . '|' . Carbon::parse($k->date)->toDateString();
            if (! $logKeys->has($key)) {
                $missingLog[] = $k;
            }
        }

        // Decorate each with project name + the missing date
        $projects = Project::whereIn('id', collect($missingLog)->pluck('project_id')->unique())
            ->get(['id', 'project_number', 'name'])
            ->keyBy('id');

        $rows = collect($missingLog)->map(function ($m) use ($projects) {
            return (object) [
                'project'      => $projects->get($m->project_id),
                'date'         => Carbon::parse($m->date),
                'hours'        => (float) $m->h,
                'cost'         => (float) $m->c,
                'entries'      => (int) $m->entries,
                'kind'         => 'missing_daily_log',
                'description'  => 'Labor booked but no daily log on file',
            ];
        })->sortByDesc('cost')->values()->all();

        return $rows;
    }

    /**
     * Last week's pending timesheets — count + sample. The "Approve last week"
     * button on the dashboard hits the existing /timesheets/bulk-approve-range
     * endpoint with the lastWeek date range.
     */
    private function pendingApprovals(Carbon $start, Carbon $end): array
    {
        $pending = Timesheet::query()
            ->where('status', 'submitted')
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->with('employee:id,first_name,last_name,employee_number', 'project:id,project_number,name')
            ->orderBy('date')
            ->get();

        return [
            'count'   => $pending->count(),
            'hours'   => (float) $pending->sum('total_hours'),
            'cost'    => (float) $pending->sum('total_cost'),
            'sample'  => $pending->take(10), // surface the first 10 for context
        ];
    }

    /**
     * Equipment past its expected_return_date by 3+ weeks. Rental costs
     * keep accruing on these so Brenda wants them flagged loudly.
     */
    private function equipmentOverdue(): array
    {
        $cutoff = now()->subWeeks(3)->startOfDay();
        return EquipmentAssignment::query()
            ->whereNull('returned_date')
            ->whereNotNull('expected_return_date')
            ->whereDate('expected_return_date', '<', $cutoff->toDateString())
            ->with('equipment:id,name,type', 'project:id,project_number,name')
            ->orderBy('expected_return_date')
            ->limit(20)
            ->get()
            ->map(function ($a) {
                $weeksLate = $a->expected_return_date
                    ? (int) floor(now()->diffInDays($a->expected_return_date, false) * -1 / 7)
                    : 0;
                return (object) [
                    'assignment'   => $a,
                    'weeks_late'   => max(0, $weeksLate),
                    'extra_cost'   => $weeksLate * 7 * (float) ($a->daily_cost ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Projected payroll for the current Mon-Sun week. Sums:
     *   - Actual cost booked Mon→today
     *   - Plus an estimate for the remaining weekdays based on the average
     *     of the prior 2 full weeks' weekday cost (a rough but useful guess)
     */
    private function projectedPayrollThisWeek(Carbon $start, Carbon $end): array
    {
        $today = now()->startOfDay();

        // Booked so far
        $bookedSoFar = (float) Timesheet::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $today->toDateString())
            ->whereIn('status', ['approved', 'submitted', 'draft'])
            ->sum('total_cost');

        // Trailing 2-week average per weekday (skip weekends)
        $trailingStart = $start->copy()->subWeeks(2);
        $trailingEnd   = $start->copy()->subDay();
        $trailingDays = max(1, $trailingEnd->diffInDays($trailingStart) + 1);
        $trailingCost = (float) Timesheet::whereDate('date', '>=', $trailingStart->toDateString())
            ->whereDate('date', '<=', $trailingEnd->toDateString())
            ->whereIn('status', ['approved', 'submitted'])
            ->sum('total_cost');
        $avgPerDay = $trailingCost / $trailingDays;

        // Remaining weekdays this week (today's dayOfWeek..Friday)
        $remainingWeekdays = 0;
        for ($d = $today->copy()->addDay(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekday()) $remainingWeekdays++;
        }

        $projectedRemaining = $avgPerDay * $remainingWeekdays;

        return [
            'booked_so_far'        => $bookedSoFar,
            'avg_per_day'          => $avgPerDay,
            'remaining_weekdays'   => $remainingWeekdays,
            'projected_remaining'  => $projectedRemaining,
            'projected_total'      => $bookedSoFar + $projectedRemaining,
        ];
    }

    private function openRfis()
    {
        return Rfi::query()
            ->whereIn('status', ['submitted', 'in_review'])
            ->whereHas('project')
            ->with('project:id,project_number,name')
            ->orderByRaw("CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'normal'   THEN 3
                ELSE 4 END")
            ->orderBy('needed_by')
            ->limit(15)
            ->get();
    }

    private function pendingChangeOrders()
    {
        // 2026-07-01 (Ali): guard against orphaned COs whose project was
        // deleted — the view's route() call needs both project + co or
        // it throws UrlGenerationException and crashes the whole page.
        return ChangeOrder::query()
            ->where('status', 'pending')
            ->whereHas('project')
            ->with('project:id,project_number,name')
            ->orderByDesc('date')
            ->limit(15)
            ->get();
    }
}
