@extends('layouts.app')

@section('title', 'Estimates - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Estimates</h1>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Estimate
            </button>
        </div>
    </div>

    <!-- Estimates Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table id="dataTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Total Amount</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Create Estimate</h2>
            <button type="button" onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div>
                <label for="create_name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="create_name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="create_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="create_description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label for="create_status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select id="create_status" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Status --</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('createModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitForm('createForm', window.BASE_URL+'/projects/{{ $project->id }}/estimates', 'POST', table, 'createModal')" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Edit Estimate</h2>
            <button type="button" onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_id" name="id">
            <div>
                <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="edit_description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select id="edit_status" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Status --</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('editModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitEditForm()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// 2026-05-01 (Brenda): URL prefix for the project-scoped estimates endpoints.
// Computed server-side via the url() helper so it always resolves correctly,
// even if window.BASE_URL is undefined or stale.
const ESTIMATE_BASE_URL = @json(url('/projects/' . $project->id . '/estimates'));
var table;
var currentEditId;

function openCreateModal() {
    document.getElementById('createForm').reset();
    openModal('createModal');
}

function editEstimate(id) {
    currentEditId = id;
    loadEdit(window.BASE_URL+`/projects/{{ $project->id }}/estimates/${id}/edit`, 'editForm', 'editModal', ['id', 'name', 'description', 'status']);
}

function viewEstimate(id) {
    window.location.href = window.BASE_URL+`/projects/{{ $project->id }}/estimates/${id}`;
}

function submitEditForm() {
    submitForm('editForm', window.BASE_URL+`/projects/{{ $project->id }}/estimates/${currentEditId}`, 'PUT', table, 'editModal');
}

$(document).ready(function() {
    table = $('#dataTable').DataTable({
        serverSide: true,
        ajax: {
            url: '{{ route("projects.estimates.index", $project) }}',
            type: 'GET',
            data: function(d) {
                return d;
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
            {
                data: 'total_amount',
                name: 'total_amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            {
                data: 'status',
                name: 'status',
                render: function(data) {
                    const statusColors = {
                        'draft': 'bg-gray-100 text-gray-800',
                        'submitted': 'bg-blue-100 text-blue-800',
                        'approved': 'bg-green-100 text-green-800',
                        'rejected': 'bg-red-100 text-red-800'
                    };
                    const statusClass = statusColors[data] || 'bg-gray-100 text-gray-800';
                    return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusClass}">
                        ${data.charAt(0).toUpperCase() + data.slice(1)}
                    </span>`;
                },
                className: 'text-center'
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-right',
                render: function(id) {
                    // 2026-05-01 (Brenda): "view estimate button is not working"
                    // on project 5403. Replaced the JS function call with a real
                    // <a> tag using ESTIMATE_BASE_URL (defined in the same script
                    // block before this DataTable init). Right-click→open-in-new-tab
                    // works, and there's no dependency on window.BASE_URL.
                    return `<div class="flex items-center justify-end gap-1">
                        <a href="${ESTIMATE_BASE_URL}/${id}" class="p-1 text-gray-400 hover:text-blue-600 inline-flex" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                        <button type="button" onclick="editEstimate(${id})" class="p-1 text-gray-400 hover:text-amber-600" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </button>
                        <button type="button" onclick="confirmDelete(ESTIMATE_BASE_URL+'/'+${id}, table)" class="p-1 text-gray-400 hover:text-red-600" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>`;
                }
            }
        ]
    });
});
</script>
@endpush

@endsection
