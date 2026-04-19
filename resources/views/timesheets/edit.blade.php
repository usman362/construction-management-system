@extends('layouts.app')

@section('title', 'Edit Timesheet')

@section('content')
@php
    $allocation = $timesheet->costAllocations->first();
    $currentPerDiem = (float) ($allocation->per_diem_amount ?? 0);
    $currentTotal = (float) $timesheet->regular_hours + (float) $timesheet->overtime_hours + (float) $timesheet->double_time_hours;
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Timesheet</h1>

        <form method="POST" action="{{ route('timesheets.update', $timesheet) }}" id="timesheetForm">
            @csrf
            @method('PUT')

            <!-- Assignment Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Assignment</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select name="employee_id" id="employee_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('employee_id') border-red-500 @enderror" onchange="refreshWeekHours()">
                            <option value="">Select Employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ $timesheet->employee_id == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project *</label>
                        <select name="project_id" id="project_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('project_id') border-red-500 @enderror">
                            <option value="">Select Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ $timesheet->project_id == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="crew_id" class="block text-sm font-medium text-gray-700 mb-2">Crew (Optional)</label>
                        <select name="crew_id" id="crew_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Select Crew</option>
                            @foreach ($crews as $crew)
                                <option value="{{ $crew->id }}" {{ $timesheet->crew_id == $crew->id ? 'selected' : '' }}>
                                    {{ $crew->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Phase Code</label>
                        <select name="cost_code_id" id="cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costCodes ?? [] as $cc)
                                <option value="{{ $cc->id }}" {{ $timesheet->cost_code_id == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->code }} — {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cost_type_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Type</label>
                        <select name="cost_type_id" id="cost_type_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costTypes ?? [] as $ct)
                                <option value="{{ $ct->id }}" {{ $timesheet->cost_type_id == $ct->id ? 'selected' : '' }}>{{ $ct->code }} — {{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" name="date" id="date" required value="{{ $timesheet->date->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror" onchange="refreshWeekHours()">
                        @error('date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="shift_id" class="block text-sm font-medium text-gray-700 mb-2">Shift *</label>
                        <select name="shift_id" id="shift_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('shift_id') border-red-500 @enderror">
                            <option value="">Select Shift</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ $timesheet->shift_id == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Hours Section -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h2 class="text-xl font-semibold mb-3">Hours</h2>
                <p class="text-[12px] text-blue-800 mb-3">
                    Current split:
                    <strong>{{ $timesheet->regular_hours }}</strong> Reg +
                    <strong>{{ $timesheet->overtime_hours }}</strong> OT +
                    <strong>{{ $timesheet->double_time_hours }}</strong> DT
                    ({{ $currentTotal }} total).
                    Leave "Hours Worked" blank to keep this split; type a new total to re-split using the weekly 40-hr rule.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="hours_worked" class="block text-sm font-medium text-blue-900 mb-2">Hours Worked (re-split)</label>
                        <input type="number" name="hours_worked" id="hours_worked" step="0.25" min="0" value="" placeholder="{{ $currentTotal }}" class="w-full border-blue-300 rounded-lg shadow-sm text-lg font-semibold" onchange="updatePreview()">
                        <p class="text-[11px] text-blue-700 mt-1">OT after 40 hrs/week (Mon–Sun).</p>
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 mb-3">
                            <input type="checkbox" name="force_overtime" id="force_overtime" value="1" class="rounded border-amber-400 text-amber-600" {{ $timesheet->force_overtime ? 'checked' : '' }} onchange="updatePreview()">
                            <span class="text-sm font-medium text-amber-900">Force Overtime</span>
                        </label>
                    </div>

                    <div class="bg-white rounded-lg p-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Week so far:</span> <span id="week_so_far" class="font-semibold">—</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Regular:</span>  <span id="reg_preview" class="font-semibold text-gray-800">{{ $timesheet->regular_hours }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Overtime:</span> <span id="ot_preview"  class="font-semibold text-amber-700">{{ $timesheet->overtime_hours }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Double:</span>   <span id="dt_preview"  class="font-semibold text-gray-800">{{ $timesheet->double_time_hours }}</span></div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="inline-flex items-center gap-2 mt-6">
                            <input type="checkbox" name="per_diem" id="per_diem" value="1" class="rounded border-gray-300 text-blue-600" {{ $currentPerDiem > 0 ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Pay per diem</span>
                        </label>
                    </div>
                    <div>
                        <label for="per_diem_amount" class="block text-sm font-medium text-gray-700 mb-2">Per diem amount ($)</label>
                        <input type="number" name="per_diem_amount" id="per_diem_amount" step="0.01" min="0" value="{{ $currentPerDiem > 0 ? $currentPerDiem : '' }}" placeholder="default from project" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 mb-3">
                            <input type="checkbox" name="is_billable" value="1" class="rounded border-gray-300 text-blue-600" {{ $timesheet->is_billable ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Billable</span>
                        </label>
                    </div>
                </div>

                <details class="mt-4" {{ $timesheet->force_overtime ? 'open' : '' }}>
                    <summary class="cursor-pointer text-xs text-gray-600 hover:text-gray-900">Manual override (enter Reg/OT/DT directly)</summary>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Regular</label>
                            <input type="number" name="regular_hours" step="0.25" min="0" value="{{ $timesheet->regular_hours }}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Overtime</label>
                            <input type="number" name="overtime_hours" step="0.25" min="0" value="{{ $timesheet->overtime_hours }}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Double Time</label>
                            <input type="number" name="double_time_hours" step="0.25" min="0" value="{{ $timesheet->double_time_hours }}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                        <p class="md:col-span-3 text-[11px] text-gray-500">Clear "Hours Worked" to use these fields directly.</p>
                    </div>
                </details>
            </div>

            <!-- Site-Specific Fields -->
            <div class="mb-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                <h2 class="text-xl font-semibold mb-4">Site-Specific Fields</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gate Log Hours (Nucor)</label>
                        <input type="number" step="0.25" name="gate_log_hours" value="{{ $timesheet->gate_log_hours }}" placeholder="e.g. 10.5" class="w-full border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="work_through_lunch" value="1" class="rounded border-gray-300 text-blue-600" {{ $timesheet->work_through_lunch ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Worked through lunch (Nucor)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Notes</h2>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm @error('notes') border-red-500 @enderror">{{ $timesheet->notes }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Update Timesheet
                </button>
                <a href="{{ route('timesheets.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const WEEK_HOURS_URL = @json(route('timesheets.week-hours'));
    const WEEKLY_OT_THRESHOLD = 40;
    const CURRENT_TIMESHEET_ID = {{ $timesheet->id }};
    let weekSoFar = 0;

    async function refreshWeekHours() {
        const empId = document.getElementById('employee_id').value;
        const date  = document.getElementById('date').value;
        const sfEl  = document.getElementById('week_so_far');
        if (!empId || !date) { sfEl.textContent = '—'; return; }

        try {
            const res = await fetch(`${WEEK_HOURS_URL}?employee_id=${empId}&date=${encodeURIComponent(date)}&exclude_id=${CURRENT_TIMESHEET_ID}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            weekSoFar = parseFloat(data.week_hours_before || 0);
            sfEl.textContent = weekSoFar.toFixed(2) + ' hrs';
            sfEl.className = 'font-semibold ' + (weekSoFar >= 40 ? 'text-amber-600' : 'text-gray-800');
            updatePreview();
        } catch (e) { /* ignore */ }
    }

    function updatePreview() {
        const hours   = parseFloat(document.getElementById('hours_worked').value) || 0;
        const forceOT = document.getElementById('force_overtime').checked;
        let reg = 0, ot = 0, dt = 0;
        if (forceOT) {
            ot = hours;
        } else {
            const cap = Math.max(0, WEEKLY_OT_THRESHOLD - weekSoFar);
            reg = Math.min(hours, cap);
            ot  = Math.max(0, hours - reg);
        }
        document.getElementById('reg_preview').textContent = reg.toFixed(2);
        document.getElementById('ot_preview').textContent  = ot.toFixed(2);
        document.getElementById('dt_preview').textContent  = dt.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', refreshWeekHours);
</script>
@endsection
