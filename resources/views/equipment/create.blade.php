@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Add Equipment</h1>

        <form method="POST" action="{{ route('equipment.store') }}" class="space-y-8">
            @csrf

            <!-- Equipment Information -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Equipment Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">Equipment Type *</label>
                    <input type="text" name="type" id="type" value="{{ old('type') }}" required placeholder="e.g., Crane, Excavator" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror">
                    @error('type')
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

            <!-- Rental Rates -->
            <div class="border-t pt-6">
                <h3 class="font-semibold text-gray-800 mb-4">Rental Rates</h3>
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <label for="daily_rate" class="block text-sm font-semibold text-gray-700 mb-2">Daily Rate ($)</label>
                        <input type="number" name="daily_rate" id="daily_rate" step="0.01" value="{{ old('daily_rate', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('daily_rate') border-red-500 @enderror">
                        @error('daily_rate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="weekly_rate" class="block text-sm font-semibold text-gray-700 mb-2">Weekly Rate ($)</label>
                        <input type="number" name="weekly_rate" id="weekly_rate" step="0.01" value="{{ old('weekly_rate', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('weekly_rate') border-red-500 @enderror">
                        @error('weekly_rate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="monthly_rate" class="block text-sm font-semibold text-gray-700 mb-2">Monthly Rate ($)</label>
                        <input type="number" name="monthly_rate" id="monthly_rate" step="0.01" value="{{ old('monthly_rate', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('monthly_rate') border-red-500 @enderror">
                        @error('monthly_rate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
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

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                <select name="status" id="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('status') border-red-500 @enderror">
                    <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="in_use" {{ old('status') === 'in_use' ? 'selected' : '' }}>In Use</option>
                    <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Add Equipment
                </button>
                <a href="{{ route('equipment.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
