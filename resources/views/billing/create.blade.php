@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Create Billing Invoice</h1>

        <form method="POST" action="{{ route('billing.store') }}" class="space-y-8">
            @csrf

            <!-- Project Selection -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="project_id" class="block text-sm font-semibold text-gray-700 mb-2">Project *</label>
                    {{-- 2026-04-30 (Brenda): show project NUMBER first so the
                         payroll clerk can scan/type by job number (the way she
                         already keys POs and timesheets). --}}
                    <select name="project_id" id="project_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('project_id') border-red-500 @enderror">
                        <option value="">Select a project</option>
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->project_number ?? '—' }} — {{ $proj->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="invoice_number" class="block text-sm font-semibold text-gray-700 mb-2">Invoice Number *</label>
                    <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('invoice_number') border-red-500 @enderror">
                    @error('invoice_number')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Billing Period -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="billing_period_start" class="block text-sm font-semibold text-gray-700 mb-2">Billing Period Start *</label>
                    <input type="date" name="billing_period_start" id="billing_period_start" value="{{ old('billing_period_start') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('billing_period_start') border-red-500 @enderror">
                    @error('billing_period_start')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_period_end" class="block text-sm font-semibold text-gray-700 mb-2">Billing Period End *</label>
                    <input type="date" name="billing_period_end" id="billing_period_end" value="{{ old('billing_period_end') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('billing_period_end') border-red-500 @enderror">
                    @error('billing_period_end')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Amount Fields -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="labor_amount" class="block text-sm font-semibold text-gray-700 mb-2">Labor Amount</label>
                    <input type="number" name="labor_amount" id="labor_amount" step="0.01" value="{{ old('labor_amount', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('labor_amount') border-red-500 @enderror">
                    @error('labor_amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="material_amount" class="block text-sm font-semibold text-gray-700 mb-2">Material Amount</label>
                    <input type="number" name="material_amount" id="material_amount" step="0.01" value="{{ old('material_amount', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('material_amount') border-red-500 @enderror">
                    @error('material_amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="equipment_amount" class="block text-sm font-semibold text-gray-700 mb-2">Equipment Amount</label>
                    <input type="number" name="equipment_amount" id="equipment_amount" step="0.01" value="{{ old('equipment_amount', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('equipment_amount') border-red-500 @enderror">
                    @error('equipment_amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subcontractor_amount" class="block text-sm font-semibold text-gray-700 mb-2">Subcontractor Amount</label>
                    <input type="number" name="subcontractor_amount" id="subcontractor_amount" step="0.01" value="{{ old('subcontractor_amount', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('subcontractor_amount') border-red-500 @enderror">
                    @error('subcontractor_amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="other_amount" class="block text-sm font-semibold text-gray-700 mb-2">Other Amount</label>
                    <input type="number" name="other_amount" id="other_amount" step="0.01" value="{{ old('other_amount', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('other_amount') border-red-500 @enderror">
                    @error('other_amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tax_rate" class="block text-sm font-semibold text-gray-700 mb-2">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" id="tax_rate" step="0.01" value="{{ old('tax_rate', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tax_rate') border-red-500 @enderror">
                    @error('tax_rate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Calculated Summary (Read-only) -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200 grid grid-cols-4 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Subtotal</p>
                    <p id="subtotal" class="text-2xl font-bold text-blue-600">$0.00</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Tax Amount</p>
                    <p id="tax-amount" class="text-2xl font-bold text-blue-600">$0.00</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Total Amount</p>
                    <p id="total-amount" class="text-2xl font-bold text-blue-600">$0.00</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Hidden Total Field</p>
                    <input type="hidden" name="total_amount" id="total-amount-field" value="0">
                </div>
            </div>

            <!-- Generate from Timesheets Button -->
            <div class="flex gap-4">
                <button type="button" id="generate-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                    Generate from Timesheets
                </button>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Create Invoice
                </button>
                <a href="{{ route('billing.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const laborInput = document.getElementById('labor_amount');
        const materialInput = document.getElementById('material_amount');
        const equipmentInput = document.getElementById('equipment_amount');
        const subcontractorInput = document.getElementById('subcontractor_amount');
        const otherInput = document.getElementById('other_amount');
        const taxRateInput = document.getElementById('tax_rate');
        const subtotalEl = document.getElementById('subtotal');
        const taxAmountEl = document.getElementById('tax-amount');
        const totalAmountEl = document.getElementById('total-amount');
        const totalAmountField = document.getElementById('total-amount-field');

        function calculateTotals() {
            const labor = parseFloat(laborInput.value) || 0;
            const material = parseFloat(materialInput.value) || 0;
            const equipment = parseFloat(equipmentInput.value) || 0;
            const subcontractor = parseFloat(subcontractorInput.value) || 0;
            const other = parseFloat(otherInput.value) || 0;
            const taxRate = parseFloat(taxRateInput.value) || 0;

            const subtotal = labor + material + equipment + subcontractor + other;
            const taxAmount = subtotal * (taxRate / 100);
            const total = subtotal + taxAmount;

            subtotalEl.textContent = '$' + subtotal.toFixed(2);
            taxAmountEl.textContent = '$' + taxAmount.toFixed(2);
            totalAmountEl.textContent = '$' + total.toFixed(2);
            totalAmountField.value = total.toFixed(2);
        }

        laborInput.addEventListener('input', calculateTotals);
        materialInput.addEventListener('input', calculateTotals);
        equipmentInput.addEventListener('input', calculateTotals);
        subcontractorInput.addEventListener('input', calculateTotals);
        otherInput.addEventListener('input', calculateTotals);
        taxRateInput.addEventListener('input', calculateTotals);

        document.getElementById('generate-btn').addEventListener('click', function() {
            alert('Generate from Timesheets feature - populate amounts from project timesheets');
            // This would make an API call to populate the amounts from timesheets
        });

        calculateTotals();
    });
</script>
@endsection
