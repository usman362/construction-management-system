<?php

namespace App\Http\Controllers;

use App\Models\CostCode;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TimeClockEntry;
use App\Models\Timesheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TimeClockController extends Controller
{
    /**
     * Mobile "My Time" page — shows current open punch (if any) and recent
     * history, plus project picker + big clock-in button.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $openEntry = TimeClockEntry::with(['project', 'employee', 'costCode'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        $recent = TimeClockEntry::with(['project', 'employee', 'costCode'])
            ->where('user_id', $user->id)
            ->orderByDesc('clock_in_at')
            ->limit(20)
            ->get();

        // Active projects to pick from on clock-in.
        $projects = Project::whereIn('status', ['active', 'awarded', 'bidding'])
            ->orderBy('name')
            ->get(['id', 'name', 'project_number', 'latitude', 'longitude', 'geofence_radius_m']);

        // If a user has a matching employee (by email), pre-fill it. Otherwise
        // the UI shows an employee picker — common for supervisors logging crew time.
        $matchedEmployee = Employee::where('email', $user->email)->first();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number']);

        $costCodes = CostCode::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        return view('time-clock.index', compact(
            'openEntry', 'recent', 'projects', 'matchedEmployee', 'employees', 'costCodes'
        ));
    }

    /**
     * Start a new punch. Captures GPS + compares to project geofence.
     * Returns JSON so the mobile UI can update without reloading.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'employee_id' => 'nullable|exists:employees,id',
            'cost_code_id'=> 'nullable|exists:cost_codes,id',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
            'accuracy_m'  => 'nullable|integer|min:0|max:65535',
            'notes'       => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // One open punch at a time — prevents forgotten clock-outs piling up.
        $existingOpen = TimeClockEntry::where('user_id', $user->id)->where('status', 'open')->first();
        if ($existingOpen) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an open punch. Clock out first.',
                'entry'   => $existingOpen->load(['project', 'employee']),
            ], 422);
        }

        $project = Project::find($data['project_id']);
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        $distance   = $project->distanceToMeters($lat, $lng);
        $within     = $project->isWithinGeofence($lat, $lng);

        $entry = TimeClockEntry::create([
            'user_id'             => $user->id,
            'employee_id'         => $data['employee_id'] ?? null,
            'project_id'          => $project->id,
            'cost_code_id'        => $data['cost_code_id'] ?? null,
            'clock_in_at'         => now(),
            'clock_in_lat'        => $lat,
            'clock_in_lng'        => $lng,
            'clock_in_accuracy_m' => $data['accuracy_m'] ?? null,
            'within_geofence'     => $within,
            'distance_m'          => $distance,
            'notes'               => $data['notes'] ?? null,
            'status'              => 'open',
        ]);

        return response()->json([
            'success'         => true,
            'message'         => 'Clocked in.',
            'entry'           => $entry->load(['project', 'employee', 'costCode']),
            'within_geofence' => $within,
            'distance_m'      => $distance,
        ], 201);
    }

    /**
     * Close the given open entry. Stamps clock-out GPS + computes hours.
     */
    public function clockOut(Request $request, TimeClockEntry $entry): JsonResponse
    {
        $data = $request->validate([
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
            'accuracy_m' => 'nullable|integer|min:0|max:65535',
            'notes'      => 'nullable|string|max:500',
        ]);

        abort_unless($entry->user_id === $request->user()->id, 403, 'That punch is not yours.');
        abort_unless($entry->status === 'open', 422, 'This punch is not open.');

        if (!empty($data['notes'])) {
            $entry->notes = trim(($entry->notes ? $entry->notes . "\n" : '') . $data['notes']);
        }

        $entry->closeOut(
            isset($data['lat']) ? (float) $data['lat'] : null,
            isset($data['lng']) ? (float) $data['lng'] : null,
            $data['accuracy_m'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Clocked out. ' . number_format((float) $entry->hours, 2) . ' h recorded.',
            'entry'   => $entry->fresh(['project', 'employee']),
        ]);
    }

    /**
     * Admin / PM review page — filterable table of all punches with geofence flags.
     */
    public function adminIndex(Request $request): View
    {
        $query = TimeClockEntry::with(['user', 'employee', 'project', 'costCode', 'timesheet']);

        if ($projectId = $request->input('project_id')) $query->where('project_id', $projectId);
        if ($userId    = $request->input('user_id'))    $query->where('user_id', $userId);
        if ($status    = $request->input('status'))     $query->where('status', $status);
        if ($request->boolean('outside_geofence'))      $query->where('within_geofence', false);
        if ($from = $request->input('from'))            $query->whereDate('clock_in_at', '>=', $from);
        if ($to   = $request->input('to'))              $query->whereDate('clock_in_at', '<=', $to);

        $entries = $query->orderByDesc('clock_in_at')->paginate(30)->withQueryString();

        return view('time-clock.admin', [
            'entries'   => $entries,
            'projects'  => Project::orderBy('name')->get(['id', 'name', 'project_number']),
            'users'     => \App\Models\User::orderBy('name')->get(['id', 'name']),
            'statusLabels' => TimeClockEntry::$statusLabels,
            'filters'   => $request->only(['project_id', 'user_id', 'status', 'outside_geofence', 'from', 'to']),
        ]);
    }

    /**
     * Convert one or more closed punches into a single-day timesheet.
     * Groups selected entries by (employee, project, date); each group
     * becomes one Timesheet row with summed hours.
     */
    public function convertToTimesheet(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'entry_ids'   => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:time_clock_entries,id',
        ]);

        $entries = TimeClockEntry::whereIn('id', $data['entry_ids'])
            ->where('status', 'closed')
            ->whereNotNull('employee_id')
            ->get();

        if ($entries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No convertible entries found. Entries must be closed and have an employee.',
            ], 422);
        }

        $created = 0;
        DB::transaction(function () use ($entries, $request, &$created) {
            $groups = $entries->groupBy(fn ($e) => $e->employee_id . '|' . $e->project_id . '|' . $e->clock_in_at->toDateString());

            foreach ($groups as $group) {
                $first = $group->first();
                $totalHours = (float) $group->sum('hours');
                $regular    = min(8, $totalHours);
                $overtime   = max(0, $totalHours - 8);

                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $first->employee_id,
                        'project_id'  => $first->project_id,
                        'date'        => $first->clock_in_at->toDateString(),
                    ],
                    [
                        'regular_hours'  => $regular,
                        'overtime_hours' => $overtime,
                        'total_hours'    => $totalHours,
                        'status'         => 'submitted',
                        'notes'          => 'Auto-generated from mobile clock punches.',
                    ]
                );

                $group->each(fn ($e) => $e->update([
                    'timesheet_id' => $timesheet->id,
                    'status'       => 'converted',
                ]));

                $created++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$created} timesheet(s) created / updated from " . $entries->count() . ' punch(es).',
        ]);
    }

    /**
     * Void an open or closed punch (mistake, test entry, etc.). Converted
     * punches must be voided by first deleting the owning timesheet.
     */
    public function void(TimeClockEntry $entry): JsonResponse
    {
        if ($entry->status === 'converted') {
            return response()->json(['success' => false, 'message' => 'Cannot void a converted punch. Delete the timesheet first.'], 422);
        }
        $entry->update(['status' => 'voided']);
        return response()->json(['success' => true, 'message' => 'Punch voided.']);
    }
}
