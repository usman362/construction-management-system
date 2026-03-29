@extends('layouts.app')

@section('title', 'New Craft')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-3xl font-bold text-gray-900">New Craft</h1>

    <form action="{{ route('crafts.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Craft Details -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 border-b pb-2">Craft Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                <input
                    type="text"
                    name="code"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                    value="{{ old('code') }}"
                >
                @error('code')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input
                    type="text"
                    name="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                    value="{{ old('name') }}"
                >
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea
                    name="description"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base Hourly Rate</label>
                    <input
                        type="number"
                        name="base_hourly_rate"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('base_hourly_rate') border-red-500 @enderror"
                        value="{{ old('base_hourly_rate') }}"
                    >
                    @error('base_hourly_rate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Multiplier</label>
                    <input
                        type="number"
                        name="overtime_multiplier"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('overtime_multiplier') border-red-500 @enderror"
                        value="{{ old('overtime_multiplier', '1.5') }}"
                    >
                    @error('overtime_multiplier')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Billable Rate</label>
                    <input
                        type="number"
                        name="billable_rate"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('billable_rate') border-red-500 @enderror"
                        value="{{ old('billable_rate') }}"
                    >
                    @error('billable_rate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
            <button
                type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
            >
                Create Craft
            </button>
            <a
                href="{{ route('crafts.index') }}"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium"
            >
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
