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
            <button onclick="openAddLineModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Budget Line
            </button>
        </div>
    </div>

    <!-- Budget Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table id="dataTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Cost Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Budget Amount</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Revised Amount</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Current Amount</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($budgetLines as $line)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $line->costCode->code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $line->description ?? $line->costCode->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right">${{ number_format($line->budget_amount, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right">${{ number_format($line->revised_amount ?? $line->budget_amount, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right font-semibold">${{ number_format($line->current_amount ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm flex items-center justify-center gap-1">
                            <a href="{{ route('projects.budget.edit', [$project, $line]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition" title="Edit">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </a>
                            <form id="delete-form-{{ $line->id }}" method="POST" action="{{ route('projects.budget.destroy', [$project, $line]) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" onclick="confirmDelete('delete-form-{{ $line->id }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition" title="Delete">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No budget lines found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 border-t-2">
                <tr>
                    <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">TOTAL:</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($budgetLines->sum('budget_amount'), 2) }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($budgetLines->sum('revised_amount') ?? $budgetLines->sum('budget_amount'), 2) }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($budgetLines->sum('current_amount'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Add Budget Line Modal -->
<div id="addLineModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Add Budget Line</h2>

        <form method="POST" action="{{ route('projects.budget.store', $project) }}">
            @csrf

            <div class="mb-4">
                <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Code *</label>
                <select name="cost_code_id" id="cost_code_id" required class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="">Select Cost Code</option>
                    @foreach ($costCodes as $code)
                        <option value="{{ $code->id }}">{{ $code->code }} - {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <input type="text" name="description" id="description" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="budget_amount" class="block text-sm font-medium text-gray-700 mb-2">Budget Amount *</label>
                <input type="number" name="budget_amount" id="budget_amount" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-6">
                <label for="revised_amount" class="block text-sm font-medium text-gray-700 mb-2">Revised Amount</label>
                <input type="number" name="revised_amount" id="revised_amount" step="0.01" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Add Line
                </button>
                <button type="button" onclick="closeAddLineModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddLineModal() {
        document.getElementById('addLineModal').classList.remove('hidden');
    }

    function closeAddLineModal() {
        document.getElementById('addLineModal').classList.add('hidden');
    }

    document.getElementById('addLineModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    function confirmDelete(formId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    $(document).ready(function() {
        $('#dataTable').DataTable({
            columnDefs: [{ orderable: false, targets: -1 }]
        });
    });
</script>
@endpush

@endsection
