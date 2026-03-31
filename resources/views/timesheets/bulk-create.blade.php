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
                <div class="mt-4 max-w-md">
                    <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost code (applies to all rows)</label>
                    <select name="cost_code_id" id="cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="">— Optional —</option>
                        @foreach ($costCodes ?? [] as $cc)
                            <option value="{{ $cc->id }}" {{ old('cost_code_id') == $cc->id ? 'selected' : '' }}>{{ $cc->code }} — {{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Crew Members Table -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Employee</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Regular Hours</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Overtime Hours</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Double Time Hours</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($crewMembers ?? [] as $employee)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                    <input type="hidden" name="entries[{{ $loop->index }}][employee_id]" value="{{ $employee->id }}">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][regular_hours]" step="0.5" value="0" class="w-full border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][overtime_hours]" step="0.5" value="0" class="w-full border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" name="entries[{{ $loop->index }}][double_time_hours]" step="0.5" value="0" class="w-full border-gray-300 rounded text-center" onchange="updateTotal(this)">
                                </td>
                                <td class="px-6 py-4 text-center text-sm font-semibold text-gray-900">
                                    <span class="total">0</span> hrs
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
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
</script>
@endsection
