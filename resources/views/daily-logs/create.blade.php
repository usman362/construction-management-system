@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Create Daily Log</h1>
        <p class="text-gray-600 mb-6">Project: {{ $project->name ?? 'N/A' }}</p>

        <form method="POST" action="{{ route('projects.daily-logs.store', $project) }}" class="space-y-8">
            @csrf

            <!-- Log Date -->
            <div>
                <label for="log_date" class="block text-sm font-semibold text-gray-700 mb-2">Log Date *</label>
                <input type="date" name="log_date" id="log_date" value="{{ old('log_date', now()->toDateString()) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('log_date') border-red-500 @enderror">
                @error('log_date')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weather and Temperature -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="weather" class="block text-sm font-semibold text-gray-700 mb-2">Weather *</label>
                    <select name="weather" id="weather" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('weather') border-red-500 @enderror">
                        <option value="">Select weather condition</option>
                        <option value="sunny" {{ old('weather') === 'sunny' ? 'selected' : '' }}>Sunny</option>
                        <option value="cloudy" {{ old('weather') === 'cloudy' ? 'selected' : '' }}>Cloudy</option>
                        <option value="rainy" {{ old('weather') === 'rainy' ? 'selected' : '' }}>Rainy</option>
                        <option value="snowy" {{ old('weather') === 'snowy' ? 'selected' : '' }}>Snowy</option>
                        <option value="foggy" {{ old('weather') === 'foggy' ? 'selected' : '' }}>Foggy</option>
                        <option value="windy" {{ old('weather') === 'windy' ? 'selected' : '' }}>Windy</option>
                    </select>
                    @error('weather')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="temperature" class="block text-sm font-semibold text-gray-700 mb-2">Temperature (°F) *</label>
                    <input type="number" name="temperature" id="temperature" value="{{ old('temperature') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('temperature') border-red-500 @enderror">
                    @error('temperature')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">Notes *</label>
                <textarea name="notes" id="notes" rows="6" required placeholder="Enter daily notes about site activities, progress, incidents, etc." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Create Log
                </button>
                <a href="{{ route('projects.daily-logs.index', $project) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
