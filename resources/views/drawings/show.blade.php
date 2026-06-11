@extends('layouts.app')
@section('title', 'Drawing ' . $drawing->sheet_number)
@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="mb-4">
        <a href="{{ route('projects.drawings.index', $project) }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Drawing Log</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold font-mono">{{ $drawing->sheet_number }}</h1>
                    @if($drawing->status === 'current')
                        <span class="inline-block bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-1 rounded">Current</span>
                    @else
                        <span class="inline-block bg-gray-200 text-gray-600 text-xs font-semibold px-2 py-1 rounded">Superseded</span>
                    @endif
                    <span class="text-sm text-gray-500">Rev <strong class="text-gray-700 font-mono">{{ $drawing->revision }}</strong></span>
                </div>
                <h2 class="text-lg text-gray-700 mt-1">{{ $drawing->sheet_title }}</h2>
                @if($drawing->discipline)
                    <p class="text-sm text-gray-500 mt-1">{{ $drawing->discipline }} — {{ \App\Models\Drawing::DISCIPLINES[$drawing->discipline] ?? '' }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('projects.drawings.preview', [$project, $drawing]) }}" target="_blank"
                   class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-3 py-2 rounded-lg">Open PDF</a>
                <a href="{{ route('projects.drawings.download', [$project, $drawing]) }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold px-3 py-2 rounded-lg">Download</a>
            </div>
        </div>

        @if($drawing->notes)
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 mt-3">
                <strong class="text-xs uppercase text-gray-500">Notes:</strong> {{ $drawing->notes }}
            </div>
        @endif

        <p class="text-xs text-gray-500 mt-3">
            Uploaded {{ $drawing->created_at->format('M j, Y g:i A') }} by {{ $drawing->uploader->name ?? 'Unknown' }}
            @if($drawing->superseded_at)
                · Superseded {{ $drawing->superseded_at->format('M j, Y g:i A') }}
            @endif
        </p>
    </div>

    {{-- Inline preview --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <iframe src="{{ route('projects.drawings.preview', [$project, $drawing]) }}"
                class="w-full" style="height: 800px; border: 0;"></iframe>
    </div>

    {{-- Revision history --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-3">Revision History — {{ $drawing->sheet_number }}</h3>
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr class="text-left">
                    <th class="px-3 py-2 font-semibold">Revision</th>
                    <th class="px-3 py-2 font-semibold">Status</th>
                    <th class="px-3 py-2 font-semibold">Uploaded</th>
                    <th class="px-3 py-2 font-semibold">By</th>
                    <th class="px-3 py-2 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $rev)
                    <tr class="border-b border-gray-100 @if($rev->id === $drawing->id) bg-yellow-50 @endif">
                        <td class="px-3 py-2 font-mono font-semibold">{{ $rev->revision }}</td>
                        <td class="px-3 py-2">
                            @if($rev->status === 'current')
                                <span class="inline-block bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded">Current</span>
                            @else
                                <span class="inline-block bg-gray-200 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded">Superseded</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $rev->created_at->format('M j, Y g:i A') }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $rev->uploader->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('projects.drawings.preview', [$project, $rev]) }}" target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-xs font-semibold mr-2">View</a>
                            <a href="{{ route('projects.drawings.download', [$project, $rev]) }}"
                               class="text-gray-600 hover:text-gray-800 text-xs font-semibold">Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
