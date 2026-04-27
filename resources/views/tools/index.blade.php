@extends('layouts.app')
@section('title', 'Tools')
@section('content')

<div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tools</h1>
            <p class="text-sm text-gray-500 mt-1">Hand tools, ladders, and small equipment. Track who has what, when it's due back.</p>
        </div>
        <button onclick="openModal('addToolModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm">
            + Add Tool
        </button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name / asset tag / serial"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Any status</option>
                @foreach(['available', 'issued', 'lost', 'retired'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Any category</option>
                @foreach($categories as $c)
                    <option value="{{ $c }}" @selected(($filters['category'] ?? '') === $c)>{{ $c }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
                <a href="{{ route('tools.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
            </div>
        </div>
    </form>

    {{-- Tools list --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($tools->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">No tools yet — click "Add Tool" to get started.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Tool</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Asset Tag</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Category</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Currently With</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($tools as $tool)
                        <tr class="hover:bg-blue-50/30">
                            <td class="px-3 py-2 text-gray-900 font-medium">{{ $tool->name }}</td>
                            <td class="px-3 py-2 text-gray-600 font-mono text-xs">{{ $tool->asset_tag ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $tool->category ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded
                                    @switch($tool->status)
                                        @case('available') bg-green-100 text-green-800 @break
                                        @case('issued')    bg-amber-100 text-amber-800 @break
                                        @case('lost')      bg-red-100   text-red-800   @break
                                        @case('retired')   bg-gray-200  text-gray-700  @break
                                    @endswitch">
                                    {{ ucfirst($tool->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-700">
                                @if($tool->currentAssignment)
                                    <strong>{{ $tool->currentAssignment->employee?->first_name }} {{ $tool->currentAssignment->employee?->last_name }}</strong>
                                    @if($tool->currentAssignment->project)
                                        <br><span class="text-gray-500">{{ $tool->currentAssignment->project->project_number }}</span>
                                    @endif
                                    @if($tool->currentAssignment->due_back_date)
                                        <br><span class="text-amber-700">Due {{ $tool->currentAssignment->due_back_date->format('M j') }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-xs">
                                @if($tool->currentAssignment)
                                    <button onclick="returnTool({{ $tool->id }})" class="text-blue-600 hover:text-blue-800">Return</button>
                                @elseif($tool->status === 'available')
                                    <button onclick="openIssueModal({{ $tool->id }})" class="text-emerald-600 hover:text-emerald-800">Issue</button>
                                @endif
                                <button onclick="confirmDelete(`{{ url('/tools/' . $tool->id) }}`, null, '{{ route('tools.index') }}')" class="text-red-600 hover:text-red-800 ml-2">×</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">{{ $tools->links() }}</div>
        @endif
    </div>
</div>

{{-- Add tool modal --}}
<div id="addToolModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('addToolModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Add Tool</h3>
        <form id="addToolForm" class="space-y-3">
            @csrf
            <input type="text" name="name" required placeholder="Tool name (e.g. DeWalt 60V drill)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="text" name="asset_tag" placeholder="Asset tag (optional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="text" name="category" placeholder="Category (e.g. Power, Hand, Ladder)" list="catList" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <datalist id="catList">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            <input type="text" name="serial_number" placeholder="Serial #" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="number" step="0.01" name="replacement_cost" placeholder="Replacement cost ($)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <textarea name="notes" rows="2" placeholder="Notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('addToolModal')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="saveTool()" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Issue tool modal --}}
<div id="issueToolModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('issueToolModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Issue Tool</h3>
        <form id="issueToolForm" class="space-y-3">
            @csrf
            <input type="hidden" id="issueToolId">
            <select name="employee_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">— Pick employee —</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }} {{ $e->employee_number ? '(#' . $e->employee_number . ')' : '' }}</option>
                @endforeach
            </select>
            <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">— Project (optional) —</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                @endforeach
            </select>
            <input type="date" name="due_back_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <textarea name="notes" rows="2" placeholder="Notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('issueToolModal')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="issueTool()" class="px-3 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg">Issue</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
async function saveTool() {
    const form = document.getElementById('addToolForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    const r = await fetch('{{ route("tools.store") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: fd,
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Save failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    location.reload();
}

function openIssueModal(toolId) {
    document.getElementById('issueToolId').value = toolId;
    openModal('issueToolModal');
}

async function issueTool() {
    const form = document.getElementById('issueToolForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    const toolId = document.getElementById('issueToolId').value;
    const r = await fetch(window.BASE_URL + '/tools/' + toolId + '/issue', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: fd,
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Issue failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    location.reload();
}

async function returnTool(toolId) {
    if (!confirm('Mark this tool as returned?')) return;
    const r = await fetch(window.BASE_URL + '/tools/' + toolId + '/return', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Return failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    location.reload();
}
</script>
@endpush
@endsection
