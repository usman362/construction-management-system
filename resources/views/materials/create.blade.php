@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Add Material</h1>

        <form method="POST" action="{{ route('materials.store') }}" class="space-y-8">
            @csrf

            <!-- Material Information -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Material Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                    <input type="text" name="category" id="category" value="{{ old('category') }}" required placeholder="e.g., Concrete, Steel, Lumber" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('category') border-red-500 @enderror">
                    @error('category')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Unit and Cost -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="unit_of_measure" class="block text-sm font-semibold text-gray-700 mb-2">Unit of Measure *</label>
                    <input type="text" name="unit_of_measure" id="unit_of_measure" value="{{ old('unit_of_measure') }}" required placeholder="e.g., Cubic Yard, Linear Foot, Pound" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('unit_of_measure') border-red-500 @enderror">
                    @error('unit_of_measure')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_cost" class="block text-sm font-semibold text-gray-700 mb-2">Unit Cost ($) *</label>
                    <input type="number" name="unit_cost" id="unit_cost" step="0.01" value="{{ old('unit_cost') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('unit_cost') border-red-500 @enderror">
                    @error('unit_cost')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Vendor -->
            <div>
                <label for="vendor_id" class="block text-sm font-semibold text-gray-700 mb-2">Vendor *</label>
                <select name="vendor_id" id="vendor_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('vendor_id') border-red-500 @enderror">
                    <option value="">Select a vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Add Material
                </button>
                <a href="{{ route('materials.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
