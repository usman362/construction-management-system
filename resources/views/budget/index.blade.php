@extends('layouts.app')

@section('title', 'Budget - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Budget Lines</h1>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Budget Line
            </button>
        </div>
    </div>

    <!-- Budget Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table id="budgetTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Cost Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Original Amount</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Current Amount</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Add Budget Line</h2>

        <form id="createForm" method="POST" action="{{ route('projects.budget.store', $project) }}">
            @csrf

            <div class="mb-4">
                <label for="create_cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Code</label>
                <select name="cost_code_id" id="create_cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="">Select Cost Code</option>
                    @foreach($costCodes as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="create_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <input type="text" name="description" id="create_description" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="create_budget_amount" class="block text-sm font-medium text-gray-700 mb-2">Budget Amount *</label>
                <input type="number" name="budget_amount" id="create_budget_amount" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Add Line
                </button>
                <button type="button" onclick="closeCreateModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Edit Budget Line</h2>

        <form id="editForm">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="edit_cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Code</label>
                <select name="cost_code_id" id="edit_cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="">Select Cost Code</option>
                    @foreach($costCodes as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <input type="text" name="description" id="edit_description" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="edit_budget_amount" class="block text-sm font-medium text-gray-700 mb-2">Budget Amount *</label>
                <input type="number" name="budget_amount" id="edit_budget_amount" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-6">
                <label for="edit_revised_amount" class="block text-sm font-medium text-gray-700 mb-2">Revised Amount *</label>
                <input type="number" name="revised_amount" id="edit_revised_amount" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Update Line
                </button>
                <button type="button" onclick="closeEditModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Budget Line Details</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cost Code</label>
                <p id="view_cost_code" class="text-gray-900">—</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <p id="view_description" class="text-gray-900">—</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Original Amount</label>
                <p id="view_original_amount" class="text-gray-900 font-semibold">—</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Amount</label>
                <p id="view_current_amount" class="text-gray-900 font-semibold">—</p>
            </div>
        </div>

        <div class="mt-6">
            <button type="button" onclick="closeViewModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded w-full">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentBudgetId = null;
    const projectId = {{ $project->id }};

    // Modal functions
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createForm').reset();
    }

    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editForm').reset();
        currentBudgetId = null;
    }

    function openViewModal() {
        document.getElementById('viewModal').classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    // Close modal on background click
    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) closeViewModal();
    });

    // Edit budget line
    function editBudgetLine(id) {
        currentBudgetId = id;
        $.get(`${window.BASE_URL}/projects/${projectId}/budget/${id}/edit`, function(data) {
            $('#edit_cost_code_id').val(data.cost_code_id || '');
            $('#edit_description').val(data.description || '');
            $('#edit_budget_amount').val(data.budget_amount || '');
            $('#edit_revised_amount').val(data.revised_amount || '');
            openEditModal();
        });
    }

    // View budget line
    function viewBudgetLine(id) {
        $.get(`${window.BASE_URL}/projects/${projectId}/budget/${id}/edit`, function(data) {
            $('#view_cost_code').text(data.cost_code?.code || '—');
            $('#view_description').text(data.description || '—');
            $('#view_original_amount').text('$' + parseFloat(data.budget_amount || 0).toFixed(2));
            $('#view_current_amount').text('$' + parseFloat(data.revised_amount || data.budget_amount || 0).toFixed(2));
            openViewModal();
        });
    }

    // Delete budget line
    function deleteBudgetLine(id) {
        confirmDelete(`${window.BASE_URL}/projects/${projectId}/budget/${id}`, table);
    }

    // Form submission
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('createForm', '{{ route("projects.budget.store", $project) }}', 'POST', table, 'createModal');
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('editForm', `${window.BASE_URL}/projects/${projectId}/budget/${currentBudgetId}`, 'PUT', table, 'editModal');
    });

    // Initialize DataTable
    let table = $('#budgetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("projects.budget.index", $project) }}',
            type: 'GET'
        },
        columns: [
            { data: 'cost_code', name: 'cost_code' },
            { data: 'description', name: 'description' },
            {
                data: 'original_amount',
                name: 'original_amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            {
                data: 'current_amount',
                name: 'current_amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right font-semibold'
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" onclick="viewBudgetLine(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </button>
                            <button type="button" onclick="editBudgetLine(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition" title="Edit">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path></svg>
                            </button>
                            <button type="button" onclick="deleteBudgetLine(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition" title="Delete">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
                            </button>
                        </div>
                    `;
                },
                className: 'text-center'
            }
        ],
        language: {
            emptyTable: "No budget lines found."
        }
    });
</script>
@endpush

@endsection
