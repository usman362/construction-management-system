@extends('layouts.app')

@section('title', 'Bulk Timesheet Entry')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-3xl font-bold mb-6">Bulk Timesheet Entry</h1>

        <form method="POST" action="{{ route('timesheets.bulk-store') }}">
            @csrf

            <!-- Header Section -->
            <div class="mb-6 p-6 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project *</label>
                        <select name="project_id" id="project_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('project_id') border-red-500 @enderror" onchange="reloadWithCrew()">
                            <option value="">Select Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="crew_id" class="block text-sm font-medium text-gray-700 mb-2">Crew *</label>
                        <select name="crew_id" id="crew_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('crew_id') border-red-500 @enderror" onchange="reloadWithCrew()">
                            <option value="">Select Crew</option>
                            @foreach ($crews as $crew)
                                <option value="{{ $crew->id }}" {{ request('crew_id') == $crew->id ? 'selected' : '' }}>
                                    {{ $crew->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('crew_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" name="date" id="date" required value="{{ old('date') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror">
                        @error('date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="shift_id" class="block text-sm font-medium text-gray-700 mb-2">Shift *</label>
                        <select name="shift_id" id="shift_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('shift_id') border-red-500 @enderror">
                            <option value="">Select Shift</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Phase Code (applies to all rows, can be overridden)</label>
                        <select name="cost_code_id" id="cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— Optional —</option>
                            @foreach ($costCodes ?? [] as $cc)
                                <option value="{{ $cc->id }}" {{ old('cost_code_id') == $cc->id ? 'selected' : '' }}>{{ $cc->code }} — {{ $cc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cost_type_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Type (default for all rows)</label>
                        <select name="cost_type_id" id="cost_type_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costTypes ?? [] as $ct)
                                <option value="{{ $ct->id }}" {{ old('cost_type_id') == $ct->id ? 'selected' : '' }}>{{ $ct->code }} — {{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Quick fill toolbar: applies hours/per-diem to the whole crew at once -->
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm font-semibold text-blue-900 mb-3">Quick fill (apply to all rows below)</p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Hours worked</label>
                        <input type="number" id="bulk_hours_worked" step="0.25" min="0" placeholder="e.g. 10" class="w-full border-blue-300 rounded-lg shadow-sm text-sm">
                        <p class="text-[11px] text-blue-700 mt-1">Splits into 8 Reg + excess OT (or &gt;16 → 8 Reg + 8 OT + DT)</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Per diem (all)</label>
                        <select id="bulk_per_diem" class="w-full border-blue-300 rounded-lg shadow-sm text-sm">
                            <option value="">— No change —</option>
                            <option value="1">Yes — pay per diem</option>
                            <option value="0">No — skip per diem</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Per diem amount ($)</label>
                        <input type="number" id="bulk_per_diem_amount" step="0.01" min="0" placeholder="default from project" class="w-full border-blue-300 rounded-lg shadow-sm text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="applyBulkFill()" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-sm">
                            Apply to all rows
                        </button>
                    </div>
                </div>
            </div>

            <!-- Crew Members Table -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Employee</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700" title="Enter hours worked. System splits into Reg/OT/DT automatically.">Hours Worked</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Reg</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">OT</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">DT</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Gate Log</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Lunch?</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Per Diem</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Per Diem $</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Cost Type</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($crewMembers ?? [] as $employee)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                    <input type="hidden" name="entries[{{ $loop->index }}][employee_id]" value="{{ $employee->id }}">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    {{-- Hours worked: typing a single number (e.g. 10) auto-distributes into Reg/OT/DT --}}
                                    <input type="number" step="0.25" min="0" placeholder="0" class="hours-worked w-20 border-gray-300 rounded text-center font-semibold" onchange="distributeHours(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][regular_hours]" step="0.5" value="0" class="w-16 border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][overtime_hours]" step="0.5" value="0" class="w-16 border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][double_time_hours]" step="0.5" value="0" class="w-16 border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][gate_log_hours]" step="0.25" placeholder="—" class="w-16 border-gray-300 rounded text-center text-xs">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="checkbox" name="entries[{{ $loop->index }}][work_through_lunch]" value="1" class="rounded border-gray-300 text-blue-600">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="checkbox" name="entries[{{ $loop->index }}][per_diem]" value="1" class="rounded border-gray-300 text-blue-600">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][per_diem_amount]" step="0.01" placeholder="default" class="w-20 border-gray-300 rounded text-center text-xs">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <select name="entries[{{ $loop->index }}][cost_type_id]" class="w-28 border-gray-300 rounded text-xs">
                                        <option value="">(default)</option>
                                        @foreach ($costTypes ?? [] as $ct)
                                            <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-gray-900">
                                    <span class="total">0</span> hrs
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                                    Select a crew to view members
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Create All Timesheets
                </button>
                <a href="{{ route('timesheets.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function reloadWithCrew() {
        var projectId = document.getElementById('project_id').value;
        var crewId = document.getElementById('crew_id').value;
        var url = '{{ route("timesheets.bulk-create") }}?';
        if (projectId) url += 'project_id=' + projectId + '&';
        if (crewId) url += 'crew_id=' + crewId;
        location.href = url;
    }

    function updateTotal(input) {
        const row = input.closest('tr');
        const regularHours = parseFloat(row.querySelector('input[name*="regular_hours"]').value) || 0;
        const overtimeHours = parseFloat(row.querySelector('input[name*="overtime_hours"]').value) || 0;
        const doubleTimeHours = parseFloat(row.querySelector('input[name*="double_time_hours"]').value) || 0;
        const total = regularHours + overtimeHours + doubleTimeHours;
        row.querySelector('.total').textContent = total.toFixed(2);
    }

    // Split a "hours worked" number into Reg (≤8) + OT (8–16) + DT (>16)
    // Example: 10 → 8 Reg + 2 OT.  6 → 6 Reg.  18 → 8 Reg + 8 OT + 2 DT.
    function splitHours(h) {
        h = parseFloat(h) || 0;
        const reg = Math.min(h, 8);
        const ot  = Math.max(0, Math.min(h, 16) - 8);
        const dt  = Math.max(0, h - 16);
        return { reg, ot, dt };
    }

    function distributeHours(input) {
        const row = input.closest('tr');
        const { reg, ot, dt } = splitHours(input.value);
        row.querySelector('input[name*="regular_hours"]').value     = reg;
        row.querySelector('input[name*="overtime_hours"]').value    = ot;
        row.querySelector('input[name*="double_time_hours"]').value = dt;
        updateTotal(input);
    }

    // Apply a single hours value / per-diem toggle to EVERY row in the table.
    function applyBulkFill() {
        const hoursWorked = document.getElementById('bulk_hours_worked').value;
        const perDiemSel  = document.getElementById('bulk_per_diem').value;
        const perDiemAmt  = document.getElementById('bulk_per_diem_amount').value;
        let touched = 0;

        document.querySelectorAll('.hours-worked').forEach((input) => {
            if (hoursWorked !== '') {
                input.value = hoursWorked;
                distributeHours(input);
                touched++;
            }
        });

        document.querySelectorAll('input[name*="[per_diem]"]').forEach((cb) => {
            if (perDiemSel === '1') cb.checked = true;
            else if (perDiemSel === '0') cb.checked = false;
        });

        if (perDiemAmt !== '') {
            document.querySelectorAll('input[name*="[per_diem_amount]"]').forEach((inp) => {
                inp.value = perDiemAmt;
            });
        }

        if (typeof Toast !== 'undefined') {
            Toast.fire({icon:'success', title:'Applied to all rows'});
        }
    }
</script>
@endsection
