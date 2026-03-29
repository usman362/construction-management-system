@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Daily Log</h1>
                <p class="text-gray-600 mt-2">{{ $log->date?->format('l, F j, Y') ?? 'N/A' }}</p>
                <p class="text-gray-500 text-sm">Project: {{ $project->name ?? 'N/A' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('projects.daily-logs.index', $project) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Back
                </a>
            </div>
        </div>

        <!-- Log Information -->
        <div class="grid grid-cols-2 gap-8 mb-10">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Weather Information</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Weather Condition</p>
                        <p class="text-lg text-gray-800 capitalize">{{ $log->weather ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Temperature</p>
                        <p class="text-lg text-gray-800">{{ $log->temperature ?? 'N/A' }}°F</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Log Details</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Date</p>
                        <p class="text-lg text-gray-800">{{ $log->date?->format('m/d/Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Created By</p>
                        <p class="text-lg text-gray-800">{{ $log->created_by ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="bg-blue-50 p-8 rounded-lg border border-blue-200 mb-10">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Notes</h3>
            <div class="prose prose-sm max-w-none">
                <p class="text-gray-800 whitespace-pre-wrap">{{ $log->notes ?? 'No notes' }}</p>
            </div>
        </div>

        <!-- Metadata -->
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Metadata</h3>
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Created At</p>
                    <p class="text-gray-800">{{ $log->created_at?->format('m/d/Y g:i A') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Last Updated</p>
                    <p class="text-gray-800">{{ $log->updated_at?->format('m/d/Y g:i A') ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
