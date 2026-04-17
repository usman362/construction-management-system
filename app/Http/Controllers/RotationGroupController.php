<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RotationGroup;
use App\Models\RotationSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RotationGroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('rotation-groups.index', [
            'projects' => Project::orderBy('name')->get(['id', 'name', 'project_number']),
        ]);
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = RotationGroup::with(['project', 'employees']);
        $totalRecords = RotationGroup::count();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('project', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }
        $filteredRecords = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(fn($g) => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'project' => $g->project?->name . ($g->project?->project_number ? ' (' . $g->project->project_number . ')' : ''),
                'pattern' => str_replace('_', ' ', $g->pattern),
                'current_shift' => $g->current_shift,
                'employees_count' => $g->employees->count(),
                'is_active' => $g->is_active,
                'actions' => $g->id,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $group = RotationGroup::create($validated);
        $this->generateSchedule($group);
        return response()->json(['message' => 'Rotation group created']);
    }

    public function show(RotationGroup $rotationGroup): JsonResponse
    {
        $rotationGroup->load(['project', 'schedule' => fn($q) => $q->orderBy('week_ending_date'), 'employees']);
        return response()->json($rotationGroup);
    }

    public function edit(RotationGroup $rotationGroup): JsonResponse
    {
        return response()->json($rotationGroup);
    }

    public function update(Request $request, RotationGroup $rotationGroup): JsonResponse
    {
        $validated = $request->validate($this->rules($rotationGroup->id));
        $patternChanged = $validated['pattern'] !== $rotationGroup->pattern;
        $rotationGroup->update($validated);
        if ($patternChanged) {
            $rotationGroup->schedule()->delete();
            $this->generateSchedule($rotationGroup);
        }
        return response()->json(['message' => 'Rotation group updated']);
    }

    public function destroy(RotationGroup $rotationGroup): JsonResponse
    {
        $rotationGroup->schedule()->delete();
        $rotationGroup->delete();
        return response()->json(['message' => 'Rotation group deleted']);
    }

    private function rules(?int $ignoreId = null): array
    {
        $unique = $ignoreId ? "unique:rotation_groups,code,{$ignoreId}" : 'unique:rotation_groups';
        return [
            'project_id'    => 'required|exists:projects,id',
            'code'          => "required|{$unique}|string|max:50",
            'name'          => 'required|string|max:100',
            'pattern'       => 'required|in:4_on_4_off,8_on_8_off_rotating,4_on_3_off,custom',
            'current_shift' => 'nullable|string|in:day,night,off',
            'notes'         => 'nullable|string|max:500',
            'is_active'     => 'nullable|boolean',
        ];
    }

    /**
     * Build a 26-week schedule starting from this coming Sunday so the field
     * team can see upcoming on/off weeks without re-entering by hand.
     */
    private function generateSchedule(RotationGroup $group): void
    {
        $start = Carbon::now()->startOfWeek(Carbon::SUNDAY)->endOfWeek(Carbon::SATURDAY);
        $working = $group->current_shift !== 'off';
        $shiftType = $group->current_shift === 'night' ? 'night' : 'day';

        for ($i = 0; $i < 26; $i++) {
            $weekEnding = $start->copy()->addWeeks($i);

            // Decide working/off + shift type based on the pattern.
            // 4_on_4_off: alternate every week (1 week on / 1 off). Approximation — real
            //             pattern is 4 days on, 4 off but Excel rolls weekly.
            // 8_on_8_off_rotating: 1 wk day shift → 1 wk off → 1 wk night → 1 wk off (repeat)
            // 4_on_3_off: always working (4 days worked + 3 off in-week)
            [$isWorking, $shift] = match ($group->pattern) {
                '4_on_4_off'          => [$i % 2 === 0, 'day'],
                '8_on_8_off_rotating' => match ($i % 4) {
                    0 => [true,  'day'],
                    1 => [false, null],
                    2 => [true,  'night'],
                    default => [false, null],
                },
                '4_on_3_off' => [true, 'day'],
                default      => [$working, $shiftType],
            };

            RotationSchedule::create([
                'rotation_group_id' => $group->id,
                'week_ending_date'  => $weekEnding->toDateString(),
                'is_working'        => $isWorking,
                'shift_type'        => $shift,
            ]);
        }
    }
}
