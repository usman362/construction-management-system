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
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name / tag / serial / location"
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
            <select name="location" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Any location</option>
                @foreach($locations as $l)
                    <option value="{{ $l }}" @selected(($filters['location'] ?? '') === $l)>{{ $l }}</option>
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
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Location</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Currently With</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">Receipt</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($tools as $tool)
                        <tr class="hover:bg-blue-50/30">
                            <td class="px-3 py-2 text-gray-900 font-medium">{{ $tool->name }}</td>
                            <td class="px-3 py-2 text-gray-600 font-mono text-xs">{{ $tool->asset_tag ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $tool->category ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700 text-xs">{{ $tool->location ?? '—' }}</td>
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
                            <td class="px-3 py-2 text-center text-xs">
                                @if($tool->purchase_ticket_path)
                                    <a href="{{ route('tools.purchase-ticket', $tool) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-900" title="{{ $tool->purchase_ticket_name }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                        View
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-xs whitespace-nowrap">
                                @if($tool->currentAssignment)
                                    <button onclick="returnTool({{ $tool->id }})" class="text-blue-600 hover:text-blue-800">Return</button>
                                @elseif($tool->status === 'available')
                                    <button onclick="openIssueModal({{ $tool->id }})" class="text-emerald-600 hover:text-emerald-800">Issue</button>
                                @endif
                                <button onclick='openEditModal(@json($tool))' class="text-gray-600 hover:text-gray-800 ml-2">Edit</button>
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
<div id="addToolModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="addToolModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[92vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Add Tool</h3>
        <form id="addToolForm" class="space-y-3" enctype="multipart/form-data">
            @csrf
            <input type="text" name="name" required placeholder="Tool name (e.g. DeWalt 60V drill)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="asset_tag" placeholder="Asset tag (optional)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="text" name="serial_number" placeholder="Serial #" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="category" placeholder="Category (e.g. Power, Hand)" list="catList" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="text" name="location" placeholder="Location (e.g. Yard, Truck 5, Shop)" list="locList" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <datalist id="catList">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            <datalist id="locList">@foreach($locations as $l)<option value="{{ $l }}">@endforeach</datalist>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" step="0.01" name="replacement_cost" placeholder="Replacement cost ($)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="date" name="purchase_date" placeholder="Purchase date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Purchase ticket / receipt (PDF or photo)</label>
                <input type="file" name="purchase_ticket" accept=".pdf,image/*" class="w-full text-sm">
            </div>
            <textarea name="notes" rows="2" placeholder="Notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('addToolModal')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="saveTool()" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit tool modal --}}
<div id="editToolModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="editToolModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[92vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Edit Tool</h3>
        <form id="editToolForm" class="space-y-3" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" id="editToolId">
            <input type="text" id="edit_name" name="name" required placeholder="Tool name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <div class="grid grid-cols-2 gap-2">
                <input type="text" id="edit_asset_tag" name="asset_tag" placeholder="Asset tag" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="text" id="edit_serial_number" name="serial_number" placeholder="Serial #" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" id="edit_category" name="category" placeholder="Category" list="catList" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="text" id="edit_location" name="location" placeholder="Location" list="locList" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" step="0.01" id="edit_replacement_cost" name="replacement_cost" placeholder="Replacement cost ($)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="date" id="edit_purchase_date" name="purchase_date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Purchase ticket / receipt</label>
                <div id="editTicketCurrent" class="hidden mb-1 text-xs text-gray-600 flex items-center gap-2">
                    <span>Current:</span>
                    <a id="editTicketLink" href="#" target="_blank" class="text-blue-700 hover:underline"></a>
                    <button type="button" onclick="removeTicket()" class="text-red-600 hover:text-red-800 text-xs">remove</button>
                </div>
                <input type="file" name="purchase_ticket" accept=".pdf,image/*" class="w-full text-sm">
                <p class="text-[11px] text-gray-500 mt-1">Uploading a new file replaces the old one.</p>
            </div>
            <select id="edit_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                @foreach(['available', 'issued', 'lost', 'retired'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <textarea id="edit_notes" name="notes" rows="2" placeholder="Notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('editToolModal')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="updateTool()" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">Save changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Issue tool modal --}}
<div id="issueToolModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="issueToolModal">
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

// 2026-05-12 (Brenda): edit-tool flow with location + receipt upload.
let editingToolId = null;
function openEditModal(tool) {
    editingToolId = tool.id;
    document.getElementById('editToolId').value = tool.id;
    document.getElementById('edit_name').value             = tool.name || '';
    document.getElementById('edit_asset_tag').value        = tool.asset_tag || '';
    document.getElementById('edit_serial_number').value    = tool.serial_number || '';
    document.getElementById('edit_category').value         = tool.category || '';
    document.getElementById('edit_location').value         = tool.location || '';
    document.getElementById('edit_replacement_cost').value = tool.replacement_cost || '';
    document.getElementById('edit_purchase_date').value    = tool.purchase_date ? String(tool.purchase_date).substring(0,10) : '';
    document.getElementById('edit_status').value           = tool.status || 'available';
    document.getElementById('edit_notes').value            = tool.notes || '';
    // Reset file input
    const fileInput = document.querySelector('#editToolForm input[type=file]');
    if (fileInput) fileInput.value = '';
    // Current ticket link
    const cur = document.getElementById('editTicketCurrent');
    const link = document.getElementById('editTicketLink');
    if (tool.purchase_ticket_path) {
        cur.classList.remove('hidden');
        link.href = window.BASE_URL + '/tools/' + tool.id + '/purchase-ticket';
        link.textContent = tool.purchase_ticket_name || 'View attached file';
    } else {
        cur.classList.add('hidden');
    }
    openModal('editToolModal');
}

async function updateTool() {
    const form = document.getElementById('editToolForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    // Laravel reads `_method=PUT` POST body as a PUT — saves us from
    // having to multipart-PUT manually (browsers don't support that).
    const r = await fetch(window.BASE_URL + '/tools/' + editingToolId, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: fd,
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Update failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    location.reload();
}

async function removeTicket() {
    if (!editingToolId) return;
    if (!confirm('Remove the attached receipt from this tool?')) return;
    const r = await fetch(window.BASE_URL + '/tools/' + editingToolId + '/purchase-ticket', {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Remove failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    document.getElementById('editTicketCurrent').classList.add('hidden');
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
