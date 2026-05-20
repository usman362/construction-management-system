<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CrewController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        $projects = Project::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();
        $shifts = Shift::orderBy('name')->get();

        return view('crews.index', [
            'projects' => $projects,
            'employees' => $employees,
            'shifts' => $shifts,
        ]);
    }

    private function dataTable(Request $request): JsonResponse
    {
        // Get total records count before filtering
        $totalRecords = Crew::count();

        // Build query with relationships
        $query = Crew::with(['project', 'foreman', 'shift'])
            ->withCount('members');

        // Apply search filter
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('foreman', function ($sq) use ($search) {
                        $sq->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    })
                    ->orWhereHas('project', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply project filter if present
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Get filtered records count
        $recordsFiltered = $query->count();

        // Apply ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        // Column order matches crews index DataTable: name, foreman, project, shift, members, actions
        $columns = [
            0 => 'name',
            1 => 'foreman_id',
            2 => 'project_id',
            3 => 'shift_id',
            4 => 'members_count',
        ];
        if (isset($columns[$orderColumnIndex])) {
            $orderColumn = $columns[$orderColumnIndex];

            if ($orderColumn === 'members_count') {
                $query->orderBy('members_count', $orderDirection);
            } else {
                $query->orderBy($orderColumn, $orderDirection);
            }
        }

        // Apply pagination
        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
        $crews = $query->offset($start)->limit($length)->get();

        // Format data for DataTables
        $data = $crews->map(fn($crew) => [
            'id' => $crew->id,
            'name' => $crew->name,
            'foreman_id' => $crew->foreman_id,
            'foreman_name' => $crew->foreman
                ? trim($crew->foreman->first_name.' '.$crew->foreman->last_name)
                : '',
            'project_id' => $crew->project_id,
            'project_name' => $crew->project->name ?? '',
            'shift_id' => $crew->shift_id,
            'shift_name' => $crew->shift?->name ?? '',
            'members_count' => $crew->members_count,
        ]);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'foreman_id' => 'required|exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'description' => 'nullable|string',
        ]);

        $crew = Crew::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Crew created successfully.',
            'crew' => $crew,
        ], 201);
    }

    public function show(Crew $crew): View
    {
        $crew->load([
            'project',
            'foreman',
            'shift',
            // 2026-05-20: only show ACTIVE members on the crew page.
            // Soft-removed members (removed_date set) stay in the DB for
            // history but disappear from the roster table.
            'members' => function ($q) {
                $q->whereNull('removed_date')->with(['employee.craft']);
            },
        ]);
        $crew->loadCount(['members as members_count' => fn ($q) => $q->whereNull('removed_date')]);

        return view('crews.show', [
            'crew' => $crew,
            'projects' => Project::orderBy('name')->get(),
            'employees' => Employee::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
        ]);
    }

    public function edit(Crew $crew): JsonResponse
    {
        $crew->load(['project', 'foreman', 'shift']);
        $projects = Project::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();
        $shifts = Shift::orderBy('name')->get();

        return response()->json([
            'crew' => $crew,
            'projects' => $projects,
            'employees' => $employees,
            'shifts' => $shifts,
        ]);
    }

    public function update(Request $request, Crew $crew): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'foreman_id' => 'required|exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'description' => 'nullable|string',
        ]);

        $crew->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Crew updated successfully.',
            'crew' => $crew->fresh(),
        ]);
    }

    public function destroy(Crew $crew): JsonResponse
    {
        $crew->delete();

        return response()->json([
            'success' => true,
            'message' => 'Crew deleted successfully.',
        ]);
    }

    public function addMember(Request $request, Crew $crew): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        // crew_members.assigned_date is NOT NULL; syncWithoutDetaching wouldn't
        // set it, so we pass it explicitly. Using `attach()` via firstOrCreate
        // on CrewMember so re-adding the same employee is a no-op.
        \App\Models\CrewMember::firstOrCreate(
            [
                'crew_id' => $crew->id,
                'employee_id' => $validated['employee_id'],
            ],
            [
                'assigned_date' => now()->toDateString(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Employee added to crew.',
        ]);
    }

    /**
     * 2026-05-20 (Brenda bug report): "I am trying to update my crews and
     * it will not let me remove employees."
     *
     * The old implementation called $crew->employees()->detach($id) — but
     * the route binds `{crewMember}` as the CrewMember pivot-row id, not
     * the employee_id. detach() expected the related-key id, so it never
     * matched anything and the click silently no-op'd.
     *
     * Fix: route-model-bind the CrewMember directly, soft-remove by
     * stamping `removed_date = today` (the schema's intended pattern —
     * the foreman dashboard already filters with whereNull('removed_date')
     * so the row disappears from the active roster but the audit trail
     * stays intact).
     */
    public function removeMember(Request $request, Crew $crew, CrewMember $crewMember): JsonResponse
    {
        // Guard against cross-crew tampering.
        if ($crewMember->crew_id !== $crew->id) {
            return response()->json([
                'success' => false,
                'message' => 'That member doesn\'t belong to this crew.',
            ], 422);
        }

        if ($crewMember->removed_date) {
            return response()->json([
                'success' => true,
                'message' => 'Employee was already removed.',
            ]);
        }

        $crewMember->update(['removed_date' => now()->toDateString()]);

        return response()->json([
            'success' => true,
            'message' => 'Employee removed from crew.',
        ]);
    }
}
