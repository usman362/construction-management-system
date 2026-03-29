@extends('layouts.app')

@section('title', 'Commitments - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Commitments</h1>
        </div>
        <button type="button" onclick="openModal('createModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Commitment
        </button>
    </div>

    <!-- Commitments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table id="commitmentTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">#</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Vendor</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create Commitment</h3>
            <button type="button" onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="createForm" method="POST" action="{{ route('projects.commitments.store', $project) }}">
            @csrf

            <div class="mb-4">
                <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                <select id="vendor_id" name="vendor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select vendor</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                <input type="text" id="description" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <input type="number" id="amount" name="amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-1">Cost Code</label>
                <select id="cost_code_id" name="cost_code_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select cost code (optional)</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="po_number" class="block text-sm font-medium text-gray-700 mb-1">PO Number</label>
                <input type="text" id="po_number" name="po_number" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select status</option>
                    <option value="draft">Draft</option>
                    <option value="released">Released</option>
                    <option value="accepted">Accepted</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="submitForm('createForm', '{{ route('projects.commitments.store', $project) }}', 'POST', 'commitmentTable', 'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Commitment</h3>
            <button type="button" onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="editForm" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="edit_vendor_id" class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                <select id="edit_vendor_id" name="vendor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select vendor</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                <input type="text" id="edit_description" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="edit_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <input type="number" id="edit_amount" name="amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="mb-4">
                <label for="edit_cost_code_id" class="block text-sm font-medium text-gray-700 mb-1">Cost Code</label>
                <select id="edit_cost_code_id" name="cost_code_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select cost code (optional)</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="edit_po_number" class="block text-sm font-medium text-gray-700 mb-1">PO Number</label>
                <input type="text" id="edit_po_number" name="po_number" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-4">
                <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                <select id="edit_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select status</option>
                    <option value="draft">Draft</option>
                    <option value="released">Released</option>
                    <option value="accepted">Accepted</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="edit_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="edit_notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="editSubmitBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Commitment Details</h3>
            <button type="button" onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div id="viewContent" class="space-y-4">
            <!-- Content populated by JavaScript -->
        </div>

        <div class="flex gap-3 justify-end mt-6">
            <button type="button" onclick="closeModal('viewModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentEditCommitmentId = null;
let commitmentTableInstance = null;
let vendorsCache = [];
let costCodesCache = [];

// Initialize vendors and cost codes
function initializeSelects() {
    $.ajax({
        url: '{{ route('api.vendors.list', $project) }}',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            vendorsCache = data;
            populateVendorSelects(data);
        }
    });

    $.ajax({
        url: '{{ route('api.cost-codes.list', $project) }}',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            costCodesCache = data;
            populateCostCodeSelects(data);
        }
    });
}

function populateVendorSelects(vendors) {
    const createSelect = document.getElementById('vendor_id');
    const editSelect = document.getElementById('edit_vendor_id');
    vendors.forEach(vendor => {
        const option = document.createElement('option');
        option.value = vendor.id;
        option.textContent = vendor.name;
        createSelect.appendChild(option);
        editSelect.appendChild(option.cloneNode(true));
    });
}

function populateCostCodeSelects(costCodes) {
    const createSelect = document.getElementById('cost_code_id');
    const editSelect = document.getElementById('edit_cost_code_id');
    costCodes.forEach(code => {
        const option = document.createElement('option');
        option.value = code.id;
        option.textContent = code.code;
        createSelect.appendChild(option);
        editSelect.appendChild(option.cloneNode(true));
    });
}

function getStatusBadgeClass(status) {
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'released': 'bg-blue-100 text-blue-800',
        'accepted': 'bg-green-100 text-green-800',
        'completed': 'bg-purple-100 text-purple-800'
    };
    return statusColors[status] || 'bg-gray-100 text-gray-800';
}

function renderActions(id) {
    return `
        <div class="flex items-center justify-center gap-2">
            <button type="button" onclick="openViewModal(${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </button>
            <button type="button" onclick="openEditModal(${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition" title="Edit">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
                </svg>
            </button>
            <button type="button" onclick="confirmDelete('{{ route('projects.commitments.destroy', [$project, '__ID__']) }}', 'commitmentTable', ${id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition" title="Delete">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                </svg>
            </button>
        </div>
    `;
}

function openViewModal(id) {
    $.ajax({
        url: `{{ route('projects.commitments.show', [$project, '__ID__']) }}`.replace('__ID__', id),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            const statusBadge = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(data.status)}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>`;

            const content = `
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Commitment Number</p>
                    <p class="text-lg font-semibold text-gray-900">${data.commitment_number}</p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Vendor</p>
                    <p class="text-gray-900">${data.vendor?.name || '—'}</p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Description</p>
                    <p class="text-gray-900">${data.description}</p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Amount</p>
                    <p class="text-lg font-semibold text-gray-900">$${parseFloat(data.amount).toFixed(2)}</p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Status</p>
                    <p>${statusBadge}</p>
                </div>
                ${data.po_number ? `
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">PO Number</p>
                    <p class="text-gray-900">${data.po_number}</p>
                </div>
                ` : ''}
                ${data.notes ? `
                <div>
                    <p class="text-sm text-gray-600">Notes</p>
                    <p class="text-gray-900">${data.notes}</p>
                </div>
                ` : ''}
            `;

            document.getElementById('viewContent').innerHTML = content;
            openModal('viewModal');
        }
    });
}

function openEditModal(id) {
    $.ajax({
        url: `{{ route('projects.commitments.edit', [$project, '__ID__']) }}`.replace('__ID__', id),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            currentEditCommitmentId = id;
            document.getElementById('edit_vendor_id').value = data.vendor_id;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_cost_code_id').value = data.cost_code_id || '';
            document.getElementById('edit_po_number').value = data.po_number || '';
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_notes').value = data.notes || '';

            document.getElementById('editForm').action = `{{ route('projects.commitments.update', [$project, '__ID__']) }}`.replace('__ID__', id);
            openModal('editModal');
        }
    });
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function submitForm(formId, url, method, tableId, modalId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);

    $.ajax({
        url: url,
        method: method,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: formData,
        processData: false,
        contentType: false,
        success: function() {
            closeModal(modalId);
            form.reset();
            if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                $(`#${tableId}`).DataTable().ajax.reload();
            }
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: method === 'POST' ? 'Commitment created successfully' : 'Commitment updated successfully',
                timer: 2000
            });
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message || 'An error occurred'
            });
        }
    });
}

function confirmDelete(url, tableId, id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const deleteUrl = url.replace('__ID__', id);
            $.ajax({
                url: deleteUrl,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                        $(`#${tableId}`).DataTable().ajax.reload();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Commitment deleted successfully',
                        timer: 2000
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to delete commitment'
                    });
                }
            });
        }
    });
}

// Handle edit form submission
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editSubmitBtn').addEventListener('click', function() {
        const url = document.getElementById('editForm').action;
        submitForm('editForm', url, 'PUT', 'commitmentTable', 'editModal');
    });

    // Initialize DataTable
    commitmentTableInstance = $('#commitmentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projects.commitments.index', $project) }}',
        columns: [
            { data: 'commitment_number', name: 'commitment_number' },
            { data: 'vendor', name: 'vendor' },
            { data: 'description', name: 'description' },
            {
                data: 'amount',
                name: 'amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            {
                data: 'status',
                name: 'status',
                render: function(data) {
                    return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(data)}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                },
                className: 'text-center'
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return renderActions(data);
                },
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        language: {
            emptyTable: 'No commitments found.',
            processing: 'Loading...'
        }
    });

    // Initialize dropdowns
    initializeSelects();
});
</script>
@endpush

@endsection
