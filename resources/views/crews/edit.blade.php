@extends('layouts.app')

@section('title', 'Edit Crew')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-3xl font-bold text-gray-900">Edit Crew</h1>

    <form action="{{ route('crews.update', $crew) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Crew Details -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 border-b pb-2">Crew Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Crew Name</label>
                <input
                    type="text"
                    name="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                    value="{{ old('name', $crew->name) }}"
                >
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
                <select
                    name="project_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('project_id') border-red-500 @enderror"
                >
                    <option value="">Select Project</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id', $crew->project_id) == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foreman</label>
                <select
                    name="foreman_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('foreman_id') border-red-500 @enderror"
                >
                    <option value="">Select Foreman</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('foreman_id', $crew->foreman_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </option>
                    @endforeach
                </select>
                @error('foreman_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Shift</label>
                <select
                    name="shift_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('shift_id') border-red-500 @enderror"
                >
                    <option value="">Select Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id', $crew->shift_id) == $shift->id ? 'selected' : '' }}>
                            {{ $shift->name }}
                        </option>
                    @endforeach
                </select>
                @error('shift_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
            <button
                type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
            >
                Update Crew
            </button>
            <a
                href="{{ route('crews.show', $crew) }}"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium"
            >
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
