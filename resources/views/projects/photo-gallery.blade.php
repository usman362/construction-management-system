@extends('layouts.app')
@section('title', 'Photo Gallery — ' . $project->name)
@section('content')

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to {{ $project->project_number }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Photo Gallery</h1>
            <p class="text-sm text-gray-500 mt-1">
                Every photo attached to this project — across daily logs, RFIs, change orders, and direct uploads.
                Total: <strong>{{ $totalPhotoCount }}</strong> photo(s).
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Source</label>
                <select name="source" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All sources</option>
                    <option value="daily_log"    @selected(($filters['source'] ?? '') === 'daily_log')>Daily Logs</option>
                    <option value="rfi"          @selected(($filters['source'] ?? '') === 'rfi')>RFIs</option>
                    <option value="change_order" @selected(($filters['source'] ?? '') === 'change_order')>Change Orders</option>
                    <option value="project"      @selected(($filters['source'] ?? '') === 'project')>Project Documents</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
                <a href="{{ route('projects.photos.index', $project) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
            </div>
        </div>
    </form>

    {{-- Gallery grid --}}
    @if($photos->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            <p class="text-sm text-gray-500">No photos match these filters.</p>
            <p class="text-xs text-gray-400 mt-1">Photos uploaded to daily logs, RFIs, and change orders show up here automatically.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($photos as $photo)
                <a href="{{ route('documents.download', $photo) }}"
                   target="_blank"
                   class="group relative bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition">
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img src="{{ route('documents.download', $photo) }}"
                             alt="{{ $photo->title ?? $photo->file_name }}"
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-2">
                        <p class="text-[11px] text-gray-700 truncate font-medium">{{ $photo->title ?? $photo->file_name }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5 flex items-center justify-between">
                            <span>{{ $photo->source_label }}</span>
                            <span>{{ $photo->created_at?->format('M j') }}</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-3">
            {{ $photos->links() }}
        </div>
    @endif
</div>

@endsection
