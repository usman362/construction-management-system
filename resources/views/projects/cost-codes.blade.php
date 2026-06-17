@extends('layouts.app')
@section('title', 'Phase Codes — ' . $project->name)
@section('content')
<div class="container mx-auto px-4 py-8" x-data="projectCostCodes()">

    <div class="mb-4">
        <a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Project</a>
        <h1 class="text-2xl font-bold mt-1">Phase Codes — {{ $project->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            Tick the cost codes that apply to this job. Once you've enabled even one,
            every dropdown on this project (timesheets, invoices, estimates, change
            orders, daily logs) will only show your selections — so it's much harder
            to key time to the wrong phase.
        </p>
        <p class="text-xs text-gray-400 mt-1 italic">
            If you leave this empty, all dropdowns keep showing the full global library
            (so legacy projects don't break).
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-2 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">

        <div class="flex items-center justify-between mb-4 gap-2 flex-wrap">
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-700"><strong x-text="selectedCount"></strong> of {{ $allCodes->count() }} codes enabled</span>
                <button @click="selectAll()" class="text-xs text-blue-600 hover:underline">Select all</button>
                <button @click="selectNone()" class="text-xs text-gray-500 hover:underline">Clear</button>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" x-model="search" placeholder="Filter codes…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                @if($otherProjects->isNotEmpty())
                    <select x-model="copyFromId" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">— Copy from another project —</option>
                        @foreach($otherProjects as $op)
                            <option value="{{ $op->id }}">{{ $op->project_number ? $op->project_number.' — ' : '' }}{{ $op->name }}</option>
                        @endforeach
                    </select>
                    <button @click="copyFrom()" :disabled="!copyFromId" class="text-xs bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white font-semibold px-3 py-1.5 rounded">Copy</button>
                @endif
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr class="text-left">
                        <th class="px-3 py-2 w-10"></th>
                        <th class="px-3 py-2 font-semibold text-gray-700 w-32">Code</th>
                        <th class="px-3 py-2 font-semibold text-gray-700">Name</th>
                        <th class="px-3 py-2 font-semibold text-gray-700 w-32">Cost Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allCodes as $c)
                        <tr class="border-b border-gray-100 hover:bg-gray-50"
                            x-show="!search || '{{ strtolower($c->code . ' ' . $c->name) }}'.includes(search.toLowerCase())">
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" value="{{ $c->id }}" x-model="selected"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-3 py-2 font-mono font-semibold text-gray-900">{{ $c->code }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $c->name }}</td>
                            <td class="px-3 py-2 text-xs text-gray-500">{{ $c->costType->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <button @click="save()" :disabled="saving"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold px-4 py-2 rounded-lg">
                <span x-show="!saving">Save Selection</span>
                <span x-show="saving">Saving…</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function projectCostCodes() {
    return {
        selected: @json($assigned->filter(fn ($a) => $a['is_active'])->keys()->values()->map(fn ($id) => (string) $id)),
        search:   '',
        saving:   false,
        copyFromId: '',

        get selectedCount() { return this.selected.length; },

        selectAll() {
            this.selected = Array.from(document.querySelectorAll('input[type=checkbox][value]')).map(el => el.value);
        },
        selectNone() { this.selected = []; },

        async save() {
            this.saving = true;
            try {
                const ids = this.selected.map(v => parseInt(v, 10)).filter(v => v > 0);
                const r = await fetch('{{ route("projects.cost-codes.sync", $project) }}', {
                    method: 'POST',
                    headers: { 'Accept':'application/json','Content-Type':'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ cost_code_ids: ids }),
                });
                if (!r.ok) { Toast.fire({icon:'error',title:'Save failed'}); return; }
                Toast.fire({icon:'success',title:'Saved.'});
                setTimeout(() => location.reload(), 600);
            } finally { this.saving = false; }
        },

        async copyFrom() {
            if (!this.copyFromId) return;
            const r = await fetch('{{ route("projects.cost-codes.copy-from", $project) }}', {
                method: 'POST',
                headers: { 'Accept':'application/json','Content-Type':'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ source_project_id: parseInt(this.copyFromId, 10) }),
            });
            if (!r.ok) { Toast.fire({icon:'error',title:'Copy failed'}); return; }
            location.reload();
        },
    };
}
</script>
@endpush
@endsection
