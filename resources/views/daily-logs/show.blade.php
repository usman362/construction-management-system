@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Daily Log</h1>
                <p class="text-gray-600 mt-2">{{ $dailyLog->date?->format('l, F j, Y') ?? 'N/A' }}</p>
                <p class="text-gray-500 text-sm">Project: {{ $project->name ?? 'N/A' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('projects.daily-logs.index', $project) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Back
                </a>
            </div>
        </div>

        <!-- Weather + Safety summary -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Weather</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Condition</p>
                        <p class="text-gray-800 capitalize">{{ $dailyLog->weather ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Temp</p>
                        <p class="text-gray-800">{{ $dailyLog->temperature !== null ? $dailyLog->temperature.'°F' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">High / Low</p>
                        <p class="text-gray-800">
                            {{ $dailyLog->temperature_high !== null ? $dailyLog->temperature_high.'°' : '—' }}
                            /
                            {{ $dailyLog->temperature_low !== null ? $dailyLog->temperature_low.'°' : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Wind</p>
                        <p class="text-gray-800">{{ $dailyLog->wind_speed ?: '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-semibold text-gray-600 uppercase">Precipitation</p>
                        <p class="text-gray-800">{{ $dailyLog->precipitation ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Safety</h3>
                <div class="flex gap-4 mb-4">
                    <div class="flex-1 text-center p-3 rounded-lg border {{ $dailyLog->incidents_count > 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' }}">
                        <div class="text-2xl font-bold {{ $dailyLog->incidents_count > 0 ? 'text-red-700' : 'text-green-700' }}">{{ $dailyLog->incidents_count ?? 0 }}</div>
                        <div class="text-xs font-semibold text-gray-600 uppercase mt-1">Incidents</div>
                    </div>
                    <div class="flex-1 text-center p-3 rounded-lg border {{ $dailyLog->near_misses_count > 0 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200' }}">
                        <div class="text-2xl font-bold {{ $dailyLog->near_misses_count > 0 ? 'text-amber-700' : 'text-green-700' }}">{{ $dailyLog->near_misses_count ?? 0 }}</div>
                        <div class="text-xs font-semibold text-gray-600 uppercase mt-1">Near Misses</div>
                    </div>
                </div>
                @if($dailyLog->safety_issues)
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Notes</p>
                        <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $dailyLog->safety_issues }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-blue-50 p-6 rounded-lg border border-blue-200 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3">Notes</h3>
            <p class="text-gray-800 whitespace-pre-wrap">{{ $dailyLog->notes ?? 'No notes' }}</p>
        </div>

        @if($dailyLog->visitors)
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Visitors</h3>
                <p class="text-gray-800 whitespace-pre-wrap">{{ $dailyLog->visitors }}</p>
            </div>
        @endif

        @if($dailyLog->delays)
            <div class="bg-amber-50 p-6 rounded-lg border border-amber-200 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Delays</h3>
                <p class="text-gray-800 whitespace-pre-wrap">{{ $dailyLog->delays }}</p>
            </div>
        @endif

        <!-- Photo Gallery -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Photos ({{ $dailyLog->photos->count() }})</h3>
                <button type="button" onclick="document.getElementById('photoUploadInput').click()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    + Upload Photos
                </button>
                <input type="file" id="photoUploadInput" multiple accept="image/*" class="hidden" onchange="uploadPhotos(this)">
            </div>

            <div id="uploadStatus" class="hidden mb-4 p-3 rounded bg-blue-50 border border-blue-200 text-sm text-blue-800"></div>

            @if($dailyLog->photos->isEmpty())
                <p class="text-sm text-gray-500 italic">No photos uploaded yet.</p>
            @else
                <div id="photoGrid" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($dailyLog->photos as $photo)
                        <div class="relative group rounded-lg overflow-hidden border border-gray-200 bg-gray-50" id="photo-{{ $photo->id }}">
                            <a href="{{ route('documents.download', $photo) }}" target="_blank" class="block aspect-square">
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-200 hover:from-blue-200 hover:to-blue-300 transition">
                                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                </div>
                            </a>
                            <div class="p-2 text-xs">
                                <div class="truncate text-gray-700 font-medium" title="{{ $photo->title }}">{{ $photo->title }}</div>
                                <div class="text-gray-500 truncate" title="{{ $photo->file_name }}">{{ $photo->file_name }}</div>
                            </div>
                            <button type="button" onclick="deletePhoto({{ $photo->id }})" class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Metadata -->
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Metadata</h3>
            <div class="grid grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Created By</p>
                    <p class="text-gray-800">{{ $dailyLog->creator?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Created At</p>
                    <p class="text-gray-800">{{ $dailyLog->created_at?->format('m/d/Y g:i A') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Last Updated</p>
                    <p class="text-gray-800">{{ $dailyLog->updated_at?->format('m/d/Y g:i A') ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const DL_ID = {{ $dailyLog->id }};
const UPLOAD_URL = '{{ route("documents.store") }}';
const CSRF = '{{ csrf_token() }}';

async function uploadPhotos(input) {
    if (!input.files.length) return;

    const statusEl = document.getElementById('uploadStatus');
    statusEl.classList.remove('hidden');
    let uploaded = 0;
    const total = input.files.length;

    for (const file of input.files) {
        statusEl.textContent = `Uploading ${uploaded + 1} of ${total}: ${file.name}...`;

        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('documentable_type', 'App\\Models\\DailyLog');
        fd.append('documentable_id', DL_ID);
        fd.append('category', 'photo');
        fd.append('title', file.name.replace(/\.[^/.]+$/, ''));
        fd.append('file', file);

        try {
            const res = await fetch(UPLOAD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                statusEl.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-800');
                statusEl.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                statusEl.textContent = `Upload failed: ${err.message || res.statusText}`;
                return;
            }
            uploaded++;
        } catch (e) {
            statusEl.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-800');
            statusEl.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            statusEl.textContent = `Upload error: ${e.message}`;
            return;
        }
    }

    statusEl.textContent = `${uploaded} photo(s) uploaded. Refreshing...`;
    setTimeout(() => window.location.reload(), 500);
}

function deletePhoto(id) {
    if (!confirm('Delete this photo?')) return;
    fetch(window.BASE_URL + '/documents/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    }).then(res => {
        if (res.ok) {
            const el = document.getElementById('photo-' + id);
            if (el) el.remove();
        } else {
            alert('Delete failed.');
        }
    });
}
</script>
@endpush
@endsection
