@extends('layouts.app')

@section('title', 'Estimate Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('projects.estimates.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Estimates</a>
        <div class="space-x-2">
            <button type="button" onclick="editEstimateShow()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('projects.estimates.destroy', [$project, $estimate]) }}', null, '{{ route('projects.estimates.index', $project) }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <!-- Estimate Header -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PROJECT</h3>
                <p class="text-lg font-bold text-gray-900">{{ $project->name }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">ESTIMATE NUMBER</h3>
                <p class="text-lg font-bold text-gray-900">{{ $estimate->estimate_number }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">NAME</h3>
                <p class="text-lg font-bold text-gray-900">{{ $estimate->name }}</p>
            </div>
        </div>

        @if ($estimate->description)
            <div class="pt-8 border-t">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">DESCRIPTION</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $estimate->description }}</p>
            </div>
        @endif
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-4 bg-white border border-gray-200 shadow-sm rounded-lg p-4">
            <p class="font-semibold text-gray-900">Import complete</p>
            <p class="text-sm text-gray-600 mt-1">Created: <span class="font-semibold text-green-700">{{ $result['created'] ?? 0 }}</span>, Skipped: <span class="font-semibold text-amber-700">{{ $result['skipped'] ?? 0 }}</span></p>
            @if(!empty($result['errors']))
                <details class="mt-2"><summary class="text-xs text-red-700 cursor-pointer">Errors ({{ count($result['errors']) }})</summary>
                    <ul class="mt-1 text-xs text-red-600 max-h-40 overflow-auto">
                        @foreach($result['errors'] as $err)
                            <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <!-- Line Items Table -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Line Items</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.estimates.lines.import.template', [$project, $estimate]) }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-3 py-2 rounded-lg shadow-sm border border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Template
                </a>
                <button type="button" onclick="openModal('importEstLinesModal')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-3 py-2 rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/></svg>
                    Import CSV
                </button>
            </div>
        </div>

        <!-- Import Modal -->
        <div id="importEstLinesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('importEstLinesModal')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Import Estimate Lines</h3>
                    <button type="button" onclick="closeModal('importEstLinesModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('projects.estimates.lines.import', [$project, $estimate]) }}" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-900">
                        <p class="font-semibold mb-1">CSV format</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Columns: cost_code, description, quantity, unit, unit_cost, labor_hours</li>
                            <li>cost_code must match an existing Cost Code.</li>
                            <li>Amount is calculated as quantity × unit_cost.</li>
                        </ul>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CSV File *</label>
                        <input type="file" name="file" accept=".csv,.txt" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" onclick="closeModal('importEstLinesModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Cost Code</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Qty</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Unit</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Unit Cost</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Labor Hrs</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($estimate->lines as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->costCode?->code ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->unit }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 font-semibold">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->labor_hours ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-center space-x-2">
                                <button type="button" onclick="confirmDelete('{{ route('projects.estimates.remove-line', [$project, $estimate, $item]) }}', null, window.location.href)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">No line items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">TOTAL:</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($estimate->lines->sum('amount') ?? 0, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Add Line Item Button -->
    <div class="mb-6">
        <button onclick="openAddLineModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Line Item
        </button>
    </div>

    <!-- Status Badge -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Status</h3>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'sent' => 'bg-blue-100 text-blue-800',
                        'accepted' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                    ];
                    $statusClass = $statusColors[$estimate->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                    {{ ucfirst($estimate->status) }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Estimate Modal -->
<div id="editEstimateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Edit Estimate</h2>
            <button type="button" onclick="closeModal('editEstimateModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editEstimateForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('editEstimateModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitEditEstimate()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Line Item Modal -->
<div id="addLineModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Add Line Item</h2>
        <form id="addLineForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cost Code *</label>
                <select name="cost_code_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select Cost Code</option>
                    @foreach ($costCodes ?? [] as $code)
                        <option value="{{ $code->id }}">{{ $code->code }} - {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Qty *</label>
                <input type="number" name="quantity" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                <input type="text" name="unit" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Cost *</label>
                <input type="number" name="unit_cost" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Labor Hours</label>
                <input type="number" name="labor_hours" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="submitAddLine()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">Add Item</button>
                <button type="button" onclick="closeModal('addLineModal')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function editEstimateShow() {
    $.get('{{ route("projects.estimates.edit", [$project, $estimate]) }}', function(d) {
        var f = document.getElementById('editEstimateForm');
        f.querySelector('[name="name"]').value = d.name || '';
        f.querySelector('[name="description"]').value = d.description || '';
        f.querySelector('[name="status"]').value = d.status || 'draft';
        openModal('editEstimateModal');
    });
}

function submitEditEstimate() {
    submitForm('editEstimateForm', '{{ route("projects.estimates.update", [$project, $estimate]) }}', 'PUT', null, 'editEstimateModal');
}

function openAddLineModal() {
    document.getElementById('addLineForm').reset();
    openModal('addLineModal');
}

function submitAddLine() {
    var form = document.getElementById('addLineForm');
    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { data[k] = v; });

    $.ajax({
        url: '{{ route("projects.estimates.add-line", [$project, $estimate]) }}',
        type: 'POST',
        data: data,
        success: function(res) {
            closeModal('addLineModal');
            window.location.reload();
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                Swal.fire({icon: 'error', title: 'Validation Error', html: msg});
            } else {
                Swal.fire({icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error adding line item'});
            }
        }
    });
}
</script>
@endpush
@endsection
