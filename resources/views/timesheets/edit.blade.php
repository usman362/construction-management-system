@extends('layouts.app')

@section('title', 'Edit Timesheet')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Timesheet</h1>

        <form method="POST" action="{{ route('timesheets.update', $timesheet) }}" x-data="timesheetForm()" @submit.prevent="submitForm">
            @csrf
            @method('PUT')

            <!-- Assignment Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Assignment</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select name="employee_id" id="employee_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('employee_id') border-red-500 @enderror">
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
                        @error('crew_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" name="date" id="date" required value="{{ $timesheet->date->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror">
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
                        @error('shift_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Hours Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Hours</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="regular_hours" class="block text-sm font-medium text-gray-700 mb-2">Regular Hours *</label>
                        <input type="number" name="regular_hours" id="regular_hours" step="0.5" required value="{{ $timesheet->regular_hours }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('regular_hours') border-red-500 @enderror">
                        @error('regular_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="overtime_hours" class="block text-sm font-medium text-gray-700 mb-2">Overtime Hours *</label>
                        <input type="number" name="overtime_hours" id="overtime_hours" step="0.5" required value="{{ $timesheet->overtime_hours }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('overtime_hours') border-red-500 @enderror">
                        @error('overtime_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="double_time_hours" class="block text-sm font-medium text-gray-700 mb-2">Double Time Hours *</label>
                        <input type="number" name="double_time_hours" id="double_time_hours" step="0.5" required value="{{ $timesheet->double_time_hours }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('double_time_hours') border-red-500 @enderror">
                        @error('double_time_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-3 pt-2 border-t-2">
                        <label for="total_hours" class="block text-sm font-medium text-gray-700 mb-2">Total Hours</label>
                        <input type="number" id="total_hours" disabled value="{{ $timesheet->total_hours }}" step="0.5" class="w-full border-gray-300 rounded-lg shadow-sm bg-gray-100">
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
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

            <!-- Form Actions -->
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
    function timesheetForm() {
        return {
            calculateTotal() {
                const regular = parseFloat(document.getElementById('regular_hours').value) || 0;
                const overtime = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const doubleTime = parseFloat(document.getElementById('double_time_hours').value) || 0;
                const total = regular + overtime + doubleTime;
                document.getElementById('total_hours').value = total.toFixed(2);
            },
            submitForm() {
                this.calculateTotal();
                document.querySelector('form').submit();
            }
        }
    }
</script>
@endsection
