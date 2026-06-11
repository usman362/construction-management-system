@extends('layouts.app')
@section('title', 'Drawings — ' . $project->name)
@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Project</a>
            <h1 class="text-2xl font-bold mt-1">Drawings — {{ $project->name }}</h1>
            <p class="text-xs text-gray-500">{{ $counts['current'] }} current · {{ $counts['superseded'] }} superseded</p>
        </div>
        <button onclick="openModal('uploadDrawingModal')"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Upload Drawing
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-2 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Sheet # or title"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Discipline</label>
            <select name="discipline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach($disciplines as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['discipline'] ?? '') === $code)>{{ $code }} — {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Current (default)</option>
                <option value="current"    @selected(($filters['status'] ?? '') === 'current')>Current only</option>
                <option value="superseded" @selected(($filters['status'] ?? '') === 'superseded')>Superseded only</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">Filter</button>
            <a href="{{ route('projects.drawings.index', $project) }}" class="text-sm text-gray-600 hover:underline">Reset</a>
        </div>
    </form>

    {{-- Drawing log table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b border-gray-200">
                <tr class="text-left">
                    <th class="px-4 py-2 font-semibold text-gray-700">Sheet #</th>
                    <th class="px-4 py-2 font-semibold text-gray-700">Title</th>
                    <th class="px-4 py-2 font-semibold text-gray-700">Discipline</th>
                    <th class="px-4 py-2 font-semibold text-gray-700 text-center">Revision</th>
                    <th class="px-4 py-2 font-semibold text-gray-700 text-center">Status</th>
                    <th class="px-4 py-2 font-semibold text-gray-700">Uploaded</th>
                    <th class="px-4 py-2 font-semibold text-gray-700 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drawings as $d)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono font-semibold text-gray-900">{{ $d->sheet_number }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $d->sheet_title }}</td>
                        <td class="px-4 py-2 text-gray-600 text-xs">
                            @if($d->discipline)
                                <span class="inline-block bg-gray-100 border border-gray-200 rounded px-2 py-0.5 font-mono">{{ $d->discipline }}</span>
                                {{ $disciplines[$d->discipline] ?? '' }}
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center font-mono">{{ $d->revision }}</td>
                        <td class="px-4 py-2 text-center">
                            @if($d->status === 'current')
                                <span class="inline-block bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded">Current</span>
                            @else
                                <span class="inline-block bg-gray-200 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded line-through">Superseded</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ $d->created_at->format('M j, Y') }}<br>
                            <span class="text-gray-400">by {{ $d->uploader->name ?? 'Unknown' }}</span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('projects.drawings.preview', [$project, $d]) }}" target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-xs font-semibold mr-2">View</a>
                            <a href="{{ route('projects.drawings.show', [$project, $d]) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold mr-2">History</a>
                            <a href="{{ route('projects.drawings.download', [$project, $d]) }}"
                               class="text-gray-600 hover:text-gray-800 text-xs font-semibold mr-2">Download</a>
                            <button onclick="deleteDrawing({{ $d->id }})" class="text-red-500 hover:text-red-700 text-xs font-semibold">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No drawings yet. Upload your first sheet to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $drawings->links() }}</div>
</div>

{{-- Upload modal --}}
<div id="uploadDrawingModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-xl">
        <form action="{{ route('projects.drawings.store', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold">Upload Drawing</h3>
                <button type="button" onclick="closeModal('uploadDrawingModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sheet # *</label>
                        <input type="text" name="sheet_number" required placeholder="A-101" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Revision</label>
                        <input type="text" name="revision" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sheet Title *</label>
                    <input type="text" name="sheet_title" required placeholder="First Floor Plan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Discipline</label>
                    <select name="discipline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach($disciplines as $code => $label)
                            <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">PDF File *</label>
                    <input type="file" name="file" required accept="application/pdf" class="w-full text-sm">
                    <p class="text-xs text-gray-400 mt-1">Max 100MB. If a current revision of this sheet # exists, it will be auto-marked as Superseded.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closeModal('uploadDrawingModal')" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">Upload</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function deleteDrawing(id) {
    Swal.fire({
        title: 'Delete this drawing?',
        text: 'If this is the current revision, the next most-recent revision (if any) will become current.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(window.BASE_URL + '/projects/{{ $project->id }}/drawings/' + id, {
            method: 'DELETE',
            headers: { 'Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        }).then(() => location.reload());
    });
}
</script>
@endpush

@endsection
