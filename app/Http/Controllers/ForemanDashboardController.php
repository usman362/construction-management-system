<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\DailyLog;
use App\Models\Employee;
use App\Models\EquipmentAssignment;
use App\Models\Project;
use App\Models\Rfi;
use App\Models\Setting;
use App\Models\TimeClockEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mobile-first foreman dashboard — "What does my crew need to know today?"
 *
 * The page is scoped to the foreman who's logged in. We resolve them by
 * matching the User's email against employees.email (same pattern as the
 * mobile time clock). Then we surface every Crew where this employee is
 * the foreman_id and roll up the most actionable info per crew:
 *   - Active members (CrewMember rows where removed_date is null)
 *   - Currently clocked-in members (open TimeClockEntry rows)
 *   - Equipment currently assigned to this crew's project
 *   - Project info (number, address, geofence center)
 *   - Today's weather (if API key set)
 *   - Open RFIs on the project that field staff submitted
 *   - Daily log status (logged today? not yet?)
 *
 * If the user is admin/PM (not a foreman), we instead show ALL active
 * crews so an office user can see field activity in one place.
 */
class ForemanDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->startOfDay();

        // Resolve the user's employee record (foreman_id is FK to employees).
        $myEmployee = Employee::where('email', $user->email)->first();

        // Pick which crews to show:
        //   - Foreman: just the crews they lead
        //   - Admin/PM/Accountant: ALL active crews (oversight view)
        $crewQuery = Crew::query()
            ->with([
                'project:id,project_number,name,address,city,state,latitude,longitude,geofence_radius_m',
                'project.client:id,name',
                'foreman:id,first_name,last_name',
                'shift',
                'members' => fn ($q) => $q->whereNull('removed_date')->with('employee:id,first_name,last_name,employee_number'),
            ])
            ->where('is_active', true);

        $isForeman = $myEmployee && Crew::where('foreman_id', $myEmployee->id)->exists();
        if ($isForeman && !$user->isAdmin() && !$user->isProjectManager()) {
            $crewQuery->where('foreman_id', $myEmployee->id);
        }

        $crews = $crewQuery->orderBy('name')->get();

        // Pull supplementary data per crew project in one batch — avoid N+1.
        $projectIds = $crews->pluck('project_id')->filter()->unique();

        // Live clock-ins by employee for these projects
        $openPunches = TimeClockEntry::query()
            ->where('status', 'open')
            ->whereIn('project_id', $projectIds)
            ->with('employee:id,first_name,last_name')
            ->get()
            ->groupBy('project_id');

        // Equipment assigned (and not yet returned) per project
        $equipmentByProject = EquipmentAssignment::query()
            ->whereNull('returned_date')
            ->whereIn('project_id', $projectIds)
            ->with('equipment:id,name,type')
            ->get()
            ->groupBy('project_id');

        // Did each project get a daily log today? Used to nudge "log not done."
        $dailyLogsTodayByProject = DailyLog::query()
            ->whereIn('project_id', $projectIds)
            ->whereDate('date', $today->toDateString())
            ->get(['id', 'project_id'])
            ->keyBy('project_id');

        // Open RFIs per project (field-actionable)
        $openRfisByProject = Rfi::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', ['submitted', 'in_review'])
            ->get(['id', 'project_id', 'rfi_number', 'subject', 'priority'])
            ->groupBy('project_id');

        // Decorate each crew with its rollup data.
        $crews->each(function ($crew) use ($openPunches, $equipmentByProject, $dailyLogsTodayByProject, $openRfisByProject) {
            $crew->live_punches      = $openPunches[$crew->project_id] ?? collect();
            $crew->equipment_today   = $equipmentByProject[$crew->project_id] ?? collect();
            $crew->daily_log_today   = $dailyLogsTodayByProject->get($crew->project_id);
            $crew->open_rfis         = $openRfisByProject[$crew->project_id] ?? collect();
        });

        return view('foreman.dashboard', [
            'crews'         => $crews,
            'myEmployee'    => $myEmployee,
            'isForeman'     => $isForeman,
            'today'         => $today,
            'weatherApiKey' => Setting::get('weather_api_key', ''),
        ]);
    }
}
