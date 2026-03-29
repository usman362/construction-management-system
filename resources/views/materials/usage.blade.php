@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Record Material Usage</h1>

        <form method="POST" action="{{ route('materials.record-usage') }}" class="space-y-8">
            @csrf

            <!-- Project Selection -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="project_id" class="block text-sm font-semibold text-gray-700 mb-2">Project *</label>
                    <select name="project_id" id="project_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('project_id') border-red-500 @enderror">
                        <option value="">Select a project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }} ({{ $project->project_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="material_id" class="block text-sm font-semibold text-gray-700 mb-2">Material *</label>
                    <select name="material_id" id="material_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('material_id') border-red-500 @enderror">
                        <option value="">Select a material</option>
                        @foreach($materials as $material)
                            <option value="{{ $material->id }}" {{ old('material_id') == $material->id ? 'selected' : '' }}>
                                {{ $material->name }} ({{ $material->unit_of_measure }})
                            </option>
                        @endforeach
                    </select>
                    @error('material_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Cost Code and Date -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="cost_code_id" class="block text-sm font-semibold text-gray-700 mb-2">Cost Code *</label>
                    <select name="cost_code_id" id="cost_code_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cost_code_id') border-red-500 @enderror">
                        <option value="">Select a cost code</option>
                        @foreach($costCodes as $code)
                            <option value="{{ $code->id }}" {{ old('cost_code_id') == $code->id ? 'selected' : '' }}>
                                {{ $code->code }} - {{ $code->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cost_code_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Quantity and Cost -->
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" id="quantity" step="0.01" value="{{ old('quantity') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('quantity') border-red-500 @enderror">
                    @error('quantity')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_cost" class="block text-sm font-semibold text-gray-700 mb-2">Unit Cost ($)</label>
                    <input type="number" name="unit_cost" id="unit_cost" step="0.01" value="{{ old('unit_cost') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('unit_cost') border-red-500 @enderror">
                    @error('unit_cost')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="total_cost" class="block text-sm font-semibold text-gray-700 mb-2">Total Cost ($)</label>
                    <input type="number" name="total_cost" id="total_cost" step="0.01" value="{{ old('total_cost', 0) }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Optional notes about this material usage" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Record Usage
                </button>
                <a href="{{ route('materials.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.getElementById('quantity');
        const unitCostInput = document.getElementById('unit_cost');
        const totalCostInput = document.getElementById('total_cost');
        const materialSelect = document.getElementById('material_id');

        function calculateTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const unitCost = parseFloat(unitCostInput.value) || 0;
            const total = quantity * unitCost;
            totalCostInput.value = total.toFixed(2);
        }

        quantityInput.addEventListener('input', calculateTotal);
        unitCostInput.addEventListener('input', calculateTotal);

        materialSelect.addEventListener('change', function() {
            // Optionally populate unit cost from material data
            const selectedOption = this.options[this.selectedIndex];
            // This would require data attributes or an API call to get unit cost
        });

        calculateTotal();
    });
</script>
@endsection
