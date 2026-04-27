<?php

namespace App\Http\Controllers;

use App\Models\EquipmentAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rental Calendar — Gantt-style bar view of every active rental.
 *
 * Brenda 04.28.2026: "For the third party equipment I rent on POs, can we
 * build a bar calendar that shows the rental duration and have it email me
 * when it's getting close to the off rent date?"
 *
 * The page surfaces all CURRENTLY-OPEN assignments (returned_date IS NULL)
 * — owned equipment usually has no expected_return_date so it just shows
 * as an ongoing bar; rentals with a return date show as fixed-width bars
 * color-coded by urgency:
 *   - 7+ days out:  green
 *   - 3-7 days:     amber
 *   - 0-2 days:     red
 *   - past due:     dark red (already racking up extra fees)
 */
class RentalCalendarController extends Controller
{
    public function index(Request $request): View
    {
        // Pull every open assignment with the equipment + project loaded.
        // A "rental" here = any open assignment, but the alert email + the
        // urgency badge only kicks in when expected_return_date is set.
        $assignments = EquipmentAssignment::query()
            ->whereNull('returned_date')
            ->with(['equipment:id,name,model_number,type,daily_rate', 'project:id,project_number,name'])
            ->orderBy('expected_return_date')
            ->get();

        // Filters
        if ($type = $request->input('type')) {
            $assignments = $assignments->filter(fn ($a) => $a->equipment?->type === $type);
        }
        if ($projectId = $request->input('project_id')) {
            $assignments = $assignments->filter(fn ($a) => $a->project_id == $projectId);
        }

        // Calendar window: span from earliest assigned_date to latest
        // expected_return_date (or 30 days from today, whichever is later).
        // We render N day-cells and position each rental bar via
        // CSS percentage-of-row-width.
        $today = \Carbon\Carbon::today();
        $minStart = $assignments->min('assigned_date');
        $maxEnd   = $assignments->max(fn ($a) => $a->expected_return_date ?? $today->copy()->addDays(30));

        $calendarStart = $minStart ? \Carbon\Carbon::parse($minStart) : $today->copy()->subDays(7);
        $calendarEnd   = $maxEnd ? \Carbon\Carbon::parse($maxEnd)->max($today->copy()->addDays(30))
                                 : $today->copy()->addDays(60);
        // Clamp the calendar to start no earlier than 7 days ago so the
        // visible window stays useful for "what's due NOW or soon."
        $calendarStart = $calendarStart->max($today->copy()->subDays(14));
        $totalDays = max(1, $calendarStart->diffInDays($calendarEnd));

        // Decorate each row with positioning + urgency.
        foreach ($assignments as $a) {
            $start = \Carbon\Carbon::parse($a->assigned_date);
            $end   = $a->expected_return_date
                ? \Carbon\Carbon::parse($a->expected_return_date)
                : $calendarEnd;   // open-ended bars run to the end of the calendar

            // Clamp to visible window
            $effStart = $start->copy()->max($calendarStart);
            $effEnd   = $end->copy()->min($calendarEnd);

            $a->bar_offset_pct = round(($calendarStart->diffInDays($effStart) / $totalDays) * 100, 2);
            $a->bar_width_pct  = max(0.5, round(($effStart->diffInDays($effEnd) / $totalDays) * 100, 2));

            $a->urgency = 'green';
            $a->urgency_label = '';
            if ($a->expected_return_date) {
                $daysToReturn = (int) floor($today->diffInDays($a->expected_return_date, false));
                if ($daysToReturn < 0) {
                    $a->urgency = 'overdue';
                    $a->urgency_label = abs($daysToReturn) . 'd overdue';
                } elseif ($daysToReturn <= 2) {
                    $a->urgency = 'red';
                    $a->urgency_label = $daysToReturn . 'd left';
                } elseif ($daysToReturn <= 7) {
                    $a->urgency = 'amber';
                    $a->urgency_label = $daysToReturn . 'd left';
                } else {
                    $a->urgency_label = $daysToReturn . 'd left';
                }
            }
        }

        // Today marker offset
        $todayOffsetPct = $today->between($calendarStart, $calendarEnd)
            ? round(($calendarStart->diffInDays($today) / $totalDays) * 100, 2)
            : null;

        // Build "Mon Apr 28 / Tue Apr 29..." header ticks for the bar axis,
        // throttled to ~1 label per week so the header stays readable on
        // long windows.
        $axisTicks = [];
        $tickStep = max(1, (int) ceil($totalDays / 12));
        for ($d = $calendarStart->copy(); $d->lte($calendarEnd); $d->addDays($tickStep)) {
            $axisTicks[] = [
                'date'       => $d->copy(),
                'label'      => $d->format('M j'),
                'offset_pct' => round(($calendarStart->diffInDays($d) / $totalDays) * 100, 2),
            ];
        }

        // Summary
        $summary = [
            'total'    => $assignments->count(),
            'overdue'  => $assignments->where('urgency', 'overdue')->count(),
            'red'      => $assignments->where('urgency', 'red')->count(),
            'amber'    => $assignments->where('urgency', 'amber')->count(),
            'no_due_date' => $assignments->whereNull('expected_return_date')->count(),
        ];

        return view('equipment.rental-calendar', [
            'assignments'    => $assignments->values(),
            'calendarStart'  => $calendarStart,
            'calendarEnd'    => $calendarEnd,
            'totalDays'      => $totalDays,
            'todayOffsetPct' => $todayOffsetPct,
            'axisTicks'      => $axisTicks,
            'summary'        => $summary,
            'allProjects'    => \App\Models\Project::orderBy('project_number')->get(['id', 'project_number', 'name']),
            'filters'        => $request->only(['type', 'project_id']),
        ]);
    }
}
