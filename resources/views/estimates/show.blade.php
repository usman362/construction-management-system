@extends('layouts.app')

@section('title', 'Estimate Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('projects.estimates.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Estimates</a>
        <div class="space-x-2">
            <a href="{{ route('projects.estimates.edit', [$project, $estimate]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</a>
            <form id="delete-estimate-form" method="POST" action="{{ route('projects.estimates.destroy', [$project, $estimate]) }}" style="display:inline;">
                @csrf
                @method('DELETE')
            </form>
            <button type="button" onclick="confirmDelete('delete-estimate-form')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
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

    <!-- Line Items Table -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Line Items</h2>

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
                    @forelse ($estimate->lineItems ?? [] as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->costCode->code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->unit }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 font-semibold">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->labor_hours ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-center space-x-2">
                                <a href="{{ route('projects.estimates.update-line', [$project, $estimate, $item]) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form id="delete-line-{{ $item->id }}" method="POST" action="{{ route('projects.estimates.remove-line', [$project, $estimate, $item]) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" onclick="confirmDelete('delete-line-{{ $item->id }}')" class="text-red-600 hover:text-red-900">Delete</button>
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
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($estimate->lineItems->sum('amount') ?? 0, 2) }}</td>
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

<!-- Add Line Item Modal -->
<div id="addLineModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Add Line Item</h2>

        <form method="POST" action="{{ route('projects.estimates.add-line', [$project, $estimate]) }}">
            @csrf

            <div class="mb-4">
                <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Code *</label>
                <select name="cost_code_id" id="cost_code_id" required class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="">Select Cost Code</option>
                    @foreach ($costCodes ?? [] as $code)
                        <option value="{{ $code->id }}">{{ $code->code }} - {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <input type="text" name="description" id="description" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Qty *</label>
                <input type="number" name="quantity" id="quantity" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                <input type="text" name="unit" id="unit" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-4">
                <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost *</label>
                <input type="number" name="unit_cost" id="unit_cost" step="0.01" required class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-6">
                <label for="labor_hours" class="block text-sm font-medium text-gray-700 mb-2">Labor Hours</label>
                <input type="number" name="labor_hours" id="labor_hours" step="0.5" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Add Item
                </button>
                <button type="button" onclick="closeAddLineModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

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
</script>
@endsection
