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
                                @if(in_array($e->status, ['closed', 'open']))
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
</script>
@endpush

@endsection
