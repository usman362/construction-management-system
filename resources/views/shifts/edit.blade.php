@extends('layouts.app')

@section('title', 'Edit Shift')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-3xl font-bold text-gray-900">Edit Shift</h1>

    <form action="{{ route('shifts.update', $shift) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Shift Details -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 border-b pb-2">Shift Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Shift Name</label>
                <input
                    type="text"
                    name="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                    value="{{ old('name', $shift->name) }}"
                >
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                    <input
                        type="time"
                        name="start_time"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_time') border-red-500 @enderror"
                        value="{{ old('start_time', $shift->start_time?->format('H:i')) }}"
                    >
                    @error('start_time')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                    <input
                        type="time"
                        name="end_time"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_time') border-red-500 @enderror"
                        value="{{ old('end_time', $shift->end_time?->format('H:i')) }}"
                    >
                    @error('end_time')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hours Per Day</label>
                    <input
                        type="number"
                        name="hours_per_day"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hours_per_day') border-red-500 @enderror"
                        value="{{ old('hours_per_day', $shift->hours_per_day) }}"
                    >
                    @error('hours_per_day')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Multiplier</label>
                    <input
                        type="number"
                        name="multiplier"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('multiplier') border-red-500 @enderror"
                        value="{{ old('multiplier', $shift->multiplier) }}"
                    >
                    @error('multiplier')
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
                Update Shift
            </button>
            <a
                href="{{ route('shifts.index') }}"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium"
            >
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
