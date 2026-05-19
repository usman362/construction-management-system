@extends('layouts.app')
@section('title', 'Time Clock Review')
@section('content')

@php
    $statusColor = [
        'open'      => 'bg-green-100 text-green-800 border-green-200',
        'closed'    => 'bg-blue-100 text-blue-800 border-blue-200',
        'converted' => 'bg-purple-100 text-purple-800 border-purple-200',
        'voided'    => 'bg-gray-200 text-gray-700 border-gray-300',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Time Clock Review</h1>
            <p class="text-sm text-gray-500 mt-1">Field clock-in / clock-out punches. Convert closed punches into approvable timesheet rows.</p>
        </div>
        <button type="button" id="convertBtn" onclick="convertSelected()" disabled class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
            Convert to Timesheet(s) (<span id="selCount">0</span>)
        </button>
    </div>

    <div id="tcStatus" class="hidden rounded-lg p-3 text-sm"></div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Project</label>
                <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((int)($filters['project_id'] ?? 0) === $p->id)>
                            {{ $p->project_number }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">User</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((int)($filters['user_id'] ?? 0) === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($statusLabels as $k => $l)
                        <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                    @endforeach
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
            <div class="flex items-end">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="outside_geofence" value="1" class="h-4 w-4 mr-2" @checked(!empty($filters['outside_geofence']))>
                    Outside geofence only
                </label>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
            <a href="{{ route('time-clock.admin') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($entries->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">No punches match these filters.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 w-8"><input type="checkbox" id="selAll" onclick="toggleAll(this)"></th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">User / Employee</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Project</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Clocked In</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Clocked Out</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Hours</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Cost Code <span class="text-[10px] text-gray-400 font-normal">(assign)</span></th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Geofence</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($entries as $e)
                        <tr class="hover:bg-gray-50 {{ $e->within_geofence === false ? 'bg-amber-50/40' : '' }}">
                            <td class="px-3 py-2">
                                @if($e->status === 'closed')
                                    <input type="checkbox" class="tc-check" value="{{ $e->id }}" onchange="refreshSelection()">
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900">{{ $e->user->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $e->employee ? $e->employee->first_name . ' ' . $e->employee->last_name : '— no employee —' }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs font-mono text-gray-700">{{ $e->project->project_number ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <div>{{ $e->clock_in_at->format('M j, g:iA') }}</div>
                                @if($e->clock_in_lat)
                                    <div class="text-[10px] text-gray-500 font-mono">{{ number_format((float)$e->clock_in_lat, 5) }}, {{ number_format((float)$e->clock_in_lng, 5) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                {{ $e->clock_out_at ? $e->clock_out_at->format('M j, g:iA') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-medium">{{ $e->hours !== null ? number_format((float)$e->hours, 2) : '—' }}</td>
                            <td class="px-3 py-2">
                                @php $hasAllocs = $e->allocations->isNotEmpty(); @endphp
                                @if($hasAllocs)
                                    {{-- 2026-05-12 (Brenda — shop crew multi-job): when a
                                         punch has been split across multiple jobs, the
                                         single cost-code dropdown is replaced with a
                                         summary of the splits. --}}
                                    <div class="text-[11px] text-blue-800 font-semibold">{{ $e->allocations->count() }} job split{{ $e->allocations->count() === 1 ? '' : 's' }}</div>
                                    <ul class="text-[10px] text-gray-600 mt-0.5 space-y-0.5">
                                        @foreach($e->allocations as $a)
                                            <li class="truncate"><span class="font-mono">{{ $a->project->project_number ?? '—' }}</span>{{ $a->costCode ? ' · ' . $a->costCode->code : '' }} — {{ number_format((float)$a->hours, 2) }}h</li>
                                        @endforeach
                                    </ul>
                                @elseif(in_array($e->status, ['closed', 'open']))
                                    <select class="tc-code w-full border border-gray-300 rounded px-2 py-1 text-xs"
                                            data-entry-id="{{ $e->id }}"
                                            onchange="updateCostCode(this)">
                                        <option value="">— none —</option>
                                        @foreach($costCodes as $c)
                                            <option value="{{ $c->id }}" @selected($e->cost_code_id === $c->id)>
                                                {{ $c->code }} — {{ $c->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if(!$e->cost_code_id && $e->status === 'closed')
                                        <div class="text-[10px] text-amber-700 mt-0.5">⚠ needs code</div>
                                    @endif
                                @elseif($e->costCode)
                                    <span class="text-xs font-mono text-gray-700">{{ $e->costCode->code }}</span>
                                    <div class="text-[10px] text-gray-500">{{ $e->costCode->name }}</div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                                {{-- "Split across jobs" toggle. Only available while
                                     punch is still editable (closed but not yet
                                     converted). JSON built in a PHP block above
                                     so Blade's parser doesn't choke on the multi-
                                     line array inside an attribute. --}}
                                @if($e->status === 'closed')
                                    @php
                                        $splitPayload = json_encode([
                                            'id'                   => $e->id,
                                            'hours'                => (float) $e->hours,
                                            'default_project_id'   => $e->project_id,
                                            'default_cost_code_id' => $e->cost_code_id,
                                            'allocations'          => $e->allocations->map(fn ($a) => [
                                                'project_id'   => $a->project_id,
                                                'cost_code_id' => $a->cost_code_id,
                                                'hours'        => (float) $a->hours,
                                                'notes'        => $a->notes,
                                            ])->values(),
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                    @endphp
                                    <button type="button"
                                            onclick="openSplitModal({{ $splitPayload }})"
                                            class="mt-1 text-[10px] text-blue-700 hover:text-blue-900 underline">
                                        {{ $hasAllocs ? 'Edit split' : 'Split across jobs' }}
                                    </button>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs">
                                @if($e->within_geofence === true)
                                    <span class="text-green-700 font-semibold">✓ On site</span>
                                    <div class="text-gray-500">{{ $e->distance_m ?? 0 }} m</div>
                                @elseif($e->within_geofence === false)
                                    <span class="text-amber-700 font-semibold">⚠ Outside</span>
                                    <div class="text-gray-500">{{ $e->distance_m ?? '—' }} m</div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $statusColor[$e->status] ?? 'bg-gray-100' }}">
                                    {{ ucfirst($e->status) }}
                                </span>
                                @if($e->timesheet)
                                    <div class="text-[10px] text-gray-500 mt-0.5">TS #{{ $e->timesheet->id }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if(in_array($e->status, ['open', 'closed']))
                                    <button type="button" onclick="voidEntry({{ $e->id }})" class="text-red-600 hover:text-red-800 text-xs">Void</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

function refreshSelection() {
    const checked = document.querySelectorAll('.tc-check:checked').length;
    document.getElementById('selCount').textContent = checked;
    document.getElementById('convertBtn').disabled = checked === 0;
}

function toggleAll(cb) {
    document.querySelectorAll('.tc-check').forEach(c => c.checked = cb.checked);
    refreshSelection();
}

function showTcStatus(kind, msg) {
    const el = document.getElementById('tcStatus');
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800');
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

async function convertSelected() {
    const ids = Array.from(document.querySelectorAll('.tc-check:checked')).map(c => parseInt(c.value));
    if (!ids.length) return;

    const r = await fetch('{{ route('time-clock.convert') }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ entry_ids: ids }),
    });
    const body = await r.json();
    if (!r.ok) { showTcStatus('error', body.message || 'Conversion failed.'); return; }
    showTcStatus('ok', body.message);
    setTimeout(() => location.reload(), 900);
}

async function updateCostCode(sel) {
    const id = sel.dataset.entryId;
    const val = sel.value || null;
    const r = await fetch(window.BASE_URL + '/admin/time-clock/' + id, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ cost_code_id: val }),
    });
    const body = await r.json();
    if (!r.ok) { showTcStatus('error', body.message || 'Update failed.'); return; }
    showTcStatus('ok', 'Cost code saved.');

    // Clear the "needs code" warning below the select if a code is now set.
    const warn = sel.parentElement.querySelector('div.text-amber-700');
    if (warn && val) warn.remove();
}

async function voidEntry(id) {
    if (!confirm('Void this punch?')) return;
    const r = await fetch(window.BASE_URL + '/admin/time-clock/' + id + '/void', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const body = await r.json();
    if (!r.ok) { showTcStatus('error', body.message || 'Void failed.'); return; }
    showTcStatus('ok', body.message);
    setTimeout(() => location.reload(), 600);
}

// ─── 2026-05-12 (Brenda — shop-crew multi-job split) ─────────────
// Opens a modal anchored to a specific punch. The foreman builds a
// list of (Project, Cost Code, Hours) rows summing to the punch's
// total. Save persists allocations via the API; convertToTimesheet
// then emits one timesheet per allocation.
const TC_PROJECTS = @json($projects->map(fn($p) => ['id' => $p->id, 'project_number' => $p->project_number, 'name' => $p->name])->values());
const TC_COST_CODES = @json($costCodes->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])->values());

let splitEntry = null;       // {id, hours, default_project_id, default_cost_code_id}
let splitRows  = [];         // [{project_id, cost_code_id, hours, notes}]

function openSplitModal(entry) {
    splitEntry = entry;
    splitRows = entry.allocations && entry.allocations.length
        ? entry.allocations.map(a => ({ project_id: a.project_id || '', cost_code_id: a.cost_code_id || '', hours: a.hours || 0, notes: a.notes || '' }))
        : [{ project_id: entry.default_project_id || '', cost_code_id: entry.default_cost_code_id || '', hours: entry.hours || 0, notes: '' }];
    renderSplitRows();
    document.getElementById('splitModal').classList.remove('hidden');
}

function closeSplitModal() {
    document.getElementById('splitModal').classList.add('hidden');
    splitEntry = null; splitRows = [];
}

function renderSplitRows() {
    const tbody = document.getElementById('splitRows');
    const optsP = ['<option value="">— pick job —</option>'].concat(
        TC_PROJECTS.map(p => `<option value="${p.id}">${p.project_number} — ${escapeHtml(p.name)}</option>`)
    ).join('');
    const optsC = ['<option value="">— none —</option>'].concat(
        TC_COST_CODES.map(c => `<option value="${c.id}">${c.code} — ${escapeHtml(c.name)}</option>`)
    ).join('');

    tbody.innerHTML = splitRows.map((r, i) => `
        <tr>
            <td class="px-1 py-1">
                <select onchange="updateSplitRow(${i}, 'project_id', this.value)" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">${optsP}</select>
            </td>
            <td class="px-1 py-1">
                <select onchange="updateSplitRow(${i}, 'cost_code_id', this.value)" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">${optsC}</select>
            </td>
            <td class="px-1 py-1">
                <input type="number" step="0.25" min="0" value="${r.hours}" onchange="updateSplitRow(${i}, 'hours', this.value)" class="w-20 border border-gray-300 rounded px-2 py-1 text-xs text-right">
            </td>
            <td class="px-1 py-1">
                <input type="text" placeholder="notes (optional)" value="${escapeHtml(r.notes || '')}" onchange="updateSplitRow(${i}, 'notes', this.value)" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
            </td>
            <td class="px-1 py-1 text-center">
                <button type="button" onclick="removeSplitRow(${i})" class="text-red-600 hover:text-red-800 text-xs">✕</button>
            </td>
        </tr>
    `).join('');

    // Pre-select existing values (innerHTML re-render resets selects)
    splitRows.forEach((r, i) => {
        const row = tbody.children[i];
        if (row) {
            if (r.project_id)   row.querySelector('select:nth-of-type(1)').value = String(r.project_id);
            if (r.cost_code_id) row.querySelector('select:nth-of-type(2)').value = String(r.cost_code_id);
        }
    });

    // Footer totals
    const sum = splitRows.reduce((s, r) => s + (parseFloat(r.hours) || 0), 0);
    const target = parseFloat(splitEntry?.hours || 0);
    const diff = sum - target;
    const sumEl = document.getElementById('splitSum');
    sumEl.textContent = `Total: ${sum.toFixed(2)} hrs (punch was ${target.toFixed(2)} hrs)`;
    sumEl.className = 'text-xs ' + (Math.abs(diff) > 0.05 ? 'text-amber-700 font-semibold' : 'text-emerald-700');
}

function updateSplitRow(i, field, value) {
    if (field === 'hours') value = value === '' ? 0 : parseFloat(value);
    if (field === 'project_id' || field === 'cost_code_id') value = value ? parseInt(value) : null;
    splitRows[i][field] = value;
    renderSplitRows();
}
function removeSplitRow(i) { splitRows.splice(i, 1); renderSplitRows(); }
function addSplitRow()     { splitRows.push({ project_id: '', cost_code_id: '', hours: 0, notes: '' }); renderSplitRows(); }

async function saveSplit() {
    const allocations = splitRows.filter(r => r.project_id && r.hours > 0).map(r => ({
        project_id:   parseInt(r.project_id),
        cost_code_id: r.cost_code_id ? parseInt(r.cost_code_id) : null,
        hours:        parseFloat(r.hours),
        notes:        r.notes || null,
    }));
    if (!allocations.length) { showTcStatus('error', 'Add at least one allocation row with a project + hours.'); return; }

    const r = await fetch(window.BASE_URL + '/admin/time-clock/' + splitEntry.id + '/allocations', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ allocations }),
    });
    const body = await r.json();
    if (!r.ok) { showTcStatus('error', body.message || 'Save failed.'); return; }
    let msg = body.message;
    if (body.mismatch) msg += ' ' + body.mismatch;
    showTcStatus('ok', msg);
    closeSplitModal();
    setTimeout(() => location.reload(), 800);
}

async function clearSplit() {
    if (!confirm('Remove all job splits from this punch? It will revert to single-job mode.')) return;
    const r = await fetch(window.BASE_URL + '/admin/time-clock/' + splitEntry.id + '/allocations', {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const body = await r.json();
    if (!r.ok) { showTcStatus('error', body.message || 'Clear failed.'); return; }
    showTcStatus('ok', body.message);
    closeSplitModal();
    setTimeout(() => location.reload(), 600);
}

function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}
</script>
@endpush

{{-- ───── Split Across Jobs modal ─────
     Brenda 2026-05-12 — shop crew works multiple jobs in a day without
     re-badging. Foreman uses this to split the single punch across
     however many job/cost-code slices the day actually held. --}}
<div id="splitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this) closeSplitModal()">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-t-xl">
            <div>
                <h3 class="text-lg font-bold">Split Punch Across Jobs</h3>
                <p class="text-xs text-blue-100 mt-0.5">For shop crew or anyone who rotated across multiple jobs without re-badging.</p>
            </div>
            <button onclick="closeSplitModal()" class="text-blue-100 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-5 overflow-y-auto flex-1">
            <p class="text-sm text-gray-600 mb-3">Add a row for every job the worker touched today. Hours can be in quarter-hour steps (0.25, 0.5, etc.). Each row will become its own timesheet when you convert this punch.</p>

            <table class="w-full text-xs">
                <thead class="text-gray-500 border-b">
                    <tr>
                        <th class="text-left px-1 py-1 w-2/5">Job</th>
                        <th class="text-left px-1 py-1 w-1/4">Cost Code</th>
                        <th class="text-right px-1 py-1 w-20">Hours</th>
                        <th class="text-left px-1 py-1">Notes</th>
                        <th class="px-1 py-1 w-8"></th>
                    </tr>
                </thead>
                <tbody id="splitRows"></tbody>
            </table>

            <button type="button" onclick="addSplitRow()" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add another job
            </button>
        </div>

        <div class="flex items-center justify-between gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <span id="splitSum" class="text-xs text-gray-500"></span>
            <div class="flex gap-2 ml-auto">
                <button type="button" onclick="clearSplit()" class="px-3 py-2 text-sm bg-white border border-gray-300 text-red-700 hover:bg-red-50 rounded-lg">Remove all splits</button>
                <button type="button" onclick="closeSplitModal()" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">Cancel</button>
                <button type="button" onclick="saveSplit()" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Save splits</button>
            </div>
        </div>
    </div>
</div>

@endsection
