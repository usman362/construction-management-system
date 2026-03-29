@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Daily Logs</h1>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openModal('addModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                + Add
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Daily Logs DataTable -->
        <div class="overflow-x-auto">
            <table id="dailyLogsTable" class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Date</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Weather</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Temperature</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Notes</th>
                        <th class="border border-gray-300 px-4 py-2 text-center font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Add Daily Log</h2>
        <form id="addForm">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="add_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="add_date" name="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="add_weather" class="block text-sm font-medium text-gray-700 mb-1">Weather</label>
                    <input type="text" id="add_weather" name="weather" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Sunny, Cloudy">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="add_temperature" class="block text-sm font-medium text-gray-700 mb-1">Temperature (°F)</label>
                    <input type="number" id="add_temperature" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="mb-4">
                <label for="add_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="add_notes" name="notes" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-4">
                <label for="add_visitors" class="block text-sm font-medium text-gray-700 mb-1">Visitors</label>
                <input type="text" id="add_visitors" name="visitors" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label for="add_safety_issues" class="block text-sm font-medium text-gray-700 mb-1">Safety Issues</label>
                <textarea id="add_safety_issues" name="safety_issues" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-6">
                <label for="add_delays" class="block text-sm font-medium text-gray-700 mb-1">Delays</label>
                <textarea id="add_delays" name="delays" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('addModal')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="button" onclick="submitForm('addForm', '{{ route('projects.daily-logs.store', $project) }}', 'POST', dailyLogsTable, 'addModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Edit Daily Log</h2>
        <form id="editForm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="edit_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="edit_date" name="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="edit_weather" class="block text-sm font-medium text-gray-700 mb-1">Weather</label>
                    <input type="text" id="edit_weather" name="weather" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Sunny, Cloudy">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="edit_temperature" class="block text-sm font-medium text-gray-700 mb-1">Temperature (°F)</label>
                    <input type="number" id="edit_temperature" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="mb-4">
                <label for="edit_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="edit_notes" name="notes" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-4">
                <label for="edit_visitors" class="block text-sm font-medium text-gray-700 mb-1">Visitors</label>
                <input type="text" id="edit_visitors" name="visitors" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label for="edit_safety_issues" class="block text-sm font-medium text-gray-700 mb-1">Safety Issues</label>
                <textarea id="edit_safety_issues" name="safety_issues" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-6">
                <label for="edit_delays" class="block text-sm font-medium text-gray-700 mb-1">Delays</label>
                <textarea id="edit_delays" name="delays" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('editModal')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="button" id="editSubmitBtn" onclick="submitForm('editForm', '', 'PUT', dailyLogsTable, 'editModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
var dailyLogsTable;

$(document).ready(function() {
    dailyLogsTable = $('#dailyLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projects.daily-logs.index', $project) }}',
            type: 'GET',
        },
        columns: [
            { data: 'date', name: 'date' },
            { data: 'weather', name: 'weather' },
            { data: 'temperature', name: 'temperature', render: function(data) {
                return data ? data + '°F' : 'N/A';
            }},
            { data: 'notes', name: 'notes' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, render: function(id) {
                return `
                    <div class="flex gap-1 items-center justify-center">
                        <button type="button" onclick="viewDailyLog(${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <button type="button" onclick="editDailyLog(${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition" title="Edit">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </button>
                        <button type="button" onclick="deleteDailyLog(${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition" title="Delete">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 2.991a1.125 1.125 0 00-1.06-.85h-1.36a1.125 1.125 0 00-1.06.85l-.822 4.822m0 0L7.822 3.65M9.26 9v12.75m0 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h3.75c.621 0 1.125-.504 1.125-1.125v-1.5m0 0c0 .621.504 1.125 1.125 1.125h1.5m-1.5 0c-.621 0-1.125-.504-1.125-1.125"/></svg>
                        </button>
                    </div>
                `;
            }}
        ],
        columnDefs: [
            { orderable: false, targets: -1 }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        language: {
            emptyTable: "No daily logs found.",
            processing: "Loading..."
        }
    });
});

function deleteDailyLog(id) {
    confirmDelete('/projects/{{ $project->id }}/daily-logs/' + id, dailyLogsTable);
}

function editDailyLog(id) {
    $.get('/projects/{{ $project->id }}/daily-logs/' + id + '/edit', function(d) {
        document.getElementById('edit_date').value = d.date || '';
        document.getElementById('edit_weather').value = d.weather || '';
        document.getElementById('edit_temperature').value = d.temperature || '';
        document.getElementById('edit_notes').value = d.notes || '';
        document.getElementById('edit_visitors').value = d.visitors || '';
        document.getElementById('edit_safety_issues').value = d.safety_issues || '';
        document.getElementById('edit_delays').value = d.delays || '';

        document.getElementById('editSubmitBtn').onclick = function() {
            submitForm('editForm', '/projects/{{ $project->id }}/daily-logs/' + id, 'PUT', dailyLogsTable, 'editModal');
        };
        openModal('editModal');
    });
}

function viewDailyLog(id) {
    window.location.href = '/projects/{{ $project->id }}/daily-logs/' + id;
}
</script>
@endpush
@endsection
