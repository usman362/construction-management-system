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
                        <input type="date" name="date" id="date" required value="{{ old('date') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror" onchange="refreshWeekHours()">
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
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <label for="cost_type_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Type (crew-level fallback)</label>
                        {{-- Crew-level Cost Type is only used when a row's dropdown is blank.
                             Individual rows now pre-fill from each employee's default_cost_type_id
                             (Employee file → Default Cost Type), so this is mostly a safety net. --}}
                        <select name="cost_type_id" id="cost_type_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costTypes ?? [] as $ct)
                                <option value="{{ $ct->id }}" {{ old('cost_type_id') == $ct->id ? 'selected' : '' }}>{{ $ct->code }} — {{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="work_order_number" class="block text-sm font-medium text-gray-700 mb-2">Work Order # <span class="text-gray-400 font-normal">(applies to all rows, optional)</span></label>
                        {{-- Shop's internal WO — client asked for this spot on both the
                             single and bulk timesheet forms. Free-text; per-row override
                             available in each row below. --}}
                        <input type="text" name="work_order_number" id="work_order_number" maxlength="100" value="{{ old('work_order_number') }}" placeholder="e.g. WO-12345" class="w-full border-gray-300 rounded-lg shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Quick fill toolbar: applies hours/per-diem/force-OT to the whole crew at once -->
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm font-semibold text-blue-900 mb-3">Quick fill (apply to all rows below)</p>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Hours worked</label>
                        <input type="number" id="bulk_hours_worked" step="0.25" min="0" placeholder="e.g. 10" class="w-full border-blue-300 rounded-lg shadow-sm text-sm">
                        <p class="text-[11px] text-blue-700 mt-1">Splits OT only after 40 hrs/week (Mon–Sun).</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Force OT (all)</label>
                        <select id="bulk_force_ot" class="w-full border-blue-300 rounded-lg shadow-sm text-sm">
                            <option value="">— No change —</option>
                            <option value="1">Yes — all hours as OT</option>
                            <option value="0">No — use weekly rule</option>
                        </select>
                        <p class="text-[11px] text-blue-700 mt-1">For holidays, weekend premium, etc.</p>
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
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700" title="Enter hours worked. System splits into Reg/OT using the weekly 40-hr rule.">Hours Worked</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700" title="Tick to treat this entry as OT regardless of the weekly total.">Force OT</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Reg</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">OT</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">DT</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Gate Log</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Lunch?</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Per Diem</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Per Diem $</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Cost Type</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700" title="Shop's internal WO # — overrides the top-level value for this row only.">Work Order #</th>
                            <th class="px-3 py-3 text-center text-sm font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($crewMembers ?? [] as $employee)
                            <tr class="border-b hover:bg-gray-50" data-employee-id="{{ $employee->id }}">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    <div>{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                    <div class="week-hours-badge text-[11px] text-gray-500 mt-0.5">Week so far: —</div>
                                    <input type="hidden" name="entries[{{ $loop->index }}][employee_id]" value="{{ $employee->id }}" class="employee-id">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    {{-- Hours worked: server splits Reg/OT using 40-hr/week rule --}}
                                    <input type="number" step="0.25" min="0" placeholder="0" name="entries[{{ $loop->index }}][hours_worked]" class="hours-worked w-20 border-gray-300 rounded text-center font-semibold" onchange="distributeHours(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="checkbox" name="entries[{{ $loop->index }}][force_overtime]" value="1" class="force-ot rounded border-gray-300 text-amber-600" onchange="distributeHours(this)">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="reg-preview text-sm text-gray-700">0</span>
                                    <input type="hidden" name="entries[{{ $loop->index }}][regular_hours]" value="0" class="reg-input">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="ot-preview text-sm text-gray-700">0</span>
                                    <input type="hidden" name="entries[{{ $loop->index }}][overtime_hours]" value="0" class="ot-input">
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="dt-preview text-sm text-gray-700">0</span>
                                    <input type="hidden" name="entries[{{ $loop->index }}][double_time_hours]" value="0" class="dt-input">
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
                                    {{-- Pre-selected from the employee's Default Cost Type
                                         (Employee file → Default Cost Type). User can still
                                         override per row; leaving blank falls back to the
                                         crew-level cost_type_id at the top of the form. --}}
                                    <select name="entries[{{ $loop->index }}][cost_type_id]" class="w-28 border-gray-300 rounded text-xs">
                                        <option value="">(default)</option>
                                        @foreach ($costTypes ?? [] as $ct)
                                            <option value="{{ $ct->id }}" {{ $employee->default_cost_type_id == $ct->id ? 'selected' : '' }}>{{ $ct->code }} — {{ $ct->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <input type="text" name="entries[{{ $loop->index }}][work_order_number]" maxlength="100" placeholder="—" class="w-24 border-gray-300 rounded text-center text-xs">
                                </td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-gray-900">
                                    <span class="total">0</span> hrs
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-4 text-center text-gray-500">
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
    const WEEK_HOURS_URL = @json(route('timesheets.week-hours'));
    const WEEKLY_OT_THRESHOLD = 40;

    // Map of employee_id → hours already logged this week (Mon–Sun)
    // Pre-fetched once whenever the date changes so per-row splits happen
    // instantly without a round-trip on every keystroke.
    let weekHoursMap = {};

    function reloadWithCrew() {
        const projectId = document.getElementById('project_id').value;
        const crewId = document.getElementById('crew_id').value;
        let url = '{{ route("timesheets.bulk-create") }}?';
        if (projectId) url += 'project_id=' + projectId + '&';
        if (crewId) url += 'crew_id=' + crewId;
        location.href = url;
    }

    // Re-split hours for one row using the client-side version of the
    // weekly-40 rule against the pre-fetched weekHoursMap. This mirrors
    // App\Services\OvertimeCalculator::splitWeekly exactly.
    function distributeHours(inputEl) {
        const row = inputEl.closest('tr');
        if (!row) return;
        const empId = row.querySelector('.employee-id').value;
        const hours = parseFloat(row.querySelector('.hours-worked').value) || 0;
        const forceOT = row.querySelector('.force-ot').checked;
        const weekSoFar = parseFloat(weekHoursMap[empId] || 0);

        let reg = 0, ot = 0, dt = 0;
        if (forceOT) {
            ot = hours;
        } else {
            const regCapacity = Math.max(0, WEEKLY_OT_THRESHOLD - weekSoFar);
            reg = Math.min(hours, regCapacity);
            ot = Math.max(0, hours - reg);
        }

        row.querySelector('.reg-input').value = reg.toFixed(2);
        row.querySelector('.ot-input').value  = ot.toFixed(2);
        row.querySelector('.dt-input').value  = dt.toFixed(2);
        row.querySelector('.reg-preview').textContent = reg.toFixed(2);
        row.querySelector('.ot-preview').textContent  = ot.toFixed(2);
        row.querySelector('.dt-preview').textContent  = dt.toFixed(2);
        row.querySelector('.total').textContent       = (reg + ot + dt).toFixed(2);
    }

    // When the date changes (or on first page load with crew members),
    // fetch each crew member's already-logged week total so the split
    // is accurate.
    async function refreshWeekHours() {
        const date = document.getElementById('date').value;
        const rows = Array.from(document.querySelectorAll('tr[data-employee-id]'));
        if (!date || rows.length === 0) return;

        const empIds = rows.map(r => r.dataset.employeeId);
        await Promise.all(empIds.map(async (id) => {
            try {
                const res = await fetch(`${WEEK_HOURS_URL}?employee_id=${id}&date=${encodeURIComponent(date)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                weekHoursMap[id] = data.week_hours_before ?? 0;
                const row = document.querySelector(`tr[data-employee-id="${id}"]`);
                if (row) {
                    const badge = row.querySelector('.week-hours-badge');
                    if (badge) {
                        badge.textContent = `Week so far: ${Number(data.week_hours_before).toFixed(2)} hrs`;
                        badge.className = 'week-hours-badge text-[11px] mt-0.5 ' +
                            (data.week_hours_before >= 40 ? 'text-amber-600 font-semibold' : 'text-gray-500');
                    }
                }
                // Re-run the split in case the user has already typed hours.
                const row2 = document.querySelector(`tr[data-employee-id="${id}"]`);
                if (row2) {
                    const hw = row2.querySelector('.hours-worked');
                    if (hw && hw.value) distributeHours(hw);
                }
            } catch (e) { /* network issue — leave weekHoursMap[id] undefined → treated as 0 */ }
        }));
    }

    // Push a quick-fill value from the blue toolbar down into every row.
    function applyBulkFill() {
        const hoursWorked = document.getElementById('bulk_hours_worked').value;
        const forceOtSel  = document.getElementById('bulk_force_ot').value;
        const perDiemSel  = document.getElementById('bulk_per_diem').value;
        const perDiemAmt  = document.getElementById('bulk_per_diem_amount').value;

        document.querySelectorAll('tr[data-employee-id]').forEach((row) => {
            const hoursInput = row.querySelector('.hours-worked');
            const forceOtInput = row.querySelector('.force-ot');

            if (hoursWorked !== '' && hoursInput) {
                hoursInput.value = hoursWorked;
            }
            if (forceOtSel === '1' && forceOtInput) forceOtInput.checked = true;
            else if (forceOtSel === '0' && forceOtInput) forceOtInput.checked = false;

            if (hoursInput) distributeHours(hoursInput);
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

    // First load: if the date field is pre-filled (old() or defaults), fetch.
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('date').value) refreshWeekHours();
    });
</script>
@endsection
