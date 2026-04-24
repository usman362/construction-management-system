@extends('layouts.app')
@section('title', 'My Time')
@section('content')

<div class="max-w-xl mx-auto px-4 py-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Time</h1>
        <p class="text-sm text-gray-500 mt-1">Clock in and out from the field. Your location is captured for geofence verification.</p>
    </div>

    <div id="gpsStatus" class="hidden rounded-lg p-3 text-sm"></div>
    <div id="actionStatus" class="hidden rounded-lg p-3 text-sm"></div>

    {{-- ─── Open Punch Panel ─────────────────────────────── --}}
    @if($openEntry)
        <div class="bg-green-50 border-2 border-green-300 rounded-xl p-5 space-y-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-600 text-white text-xs font-bold rounded-full uppercase tracking-wider">
                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> On the clock
                </span>
            </div>
            <div class="space-y-1">
                <div class="text-lg font-bold text-gray-900">{{ $openEntry->project->project_number }} — {{ $openEntry->project->name }}</div>
                @if($openEntry->employee)
                    <div class="text-sm text-gray-700">as {{ $openEntry->employee->first_name }} {{ $openEntry->employee->last_name }}</div>
                @endif
                @if($openEntry->costCode)
                    <div class="text-xs font-mono text-gray-600">{{ $openEntry->costCode->code }} — {{ $openEntry->costCode->name }}</div>
                @endif
                <div class="text-sm text-gray-700">Clocked in at <span class="font-semibold">{{ $openEntry->clock_in_at->format('g:i A') }}</span></div>
                <div class="text-xs text-gray-500">
                    @if($openEntry->within_geofence === true)
                        <span class="text-green-700 font-semibold">✓ On site</span> ({{ $openEntry->distance_m ?? 0 }} m from center)
                    @elseif($openEntry->within_geofence === false)
                        <span class="text-amber-700 font-semibold">⚠ Outside geofence</span> ({{ $openEntry->distance_m ?? '—' }} m from center)
                    @else
                        <span class="text-gray-500">Geofence not configured</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500" id="elapsed" data-start="{{ $openEntry->clock_in_at->toIso8601String() }}">—</div>
            </div>

            <button type="button" onclick="doClockOut({{ $openEntry->id }})" class="w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-lg py-5 rounded-xl shadow-md transition">
                Clock Out
            </button>
        </div>
    @else
        {{-- ─── Clock In Panel ──────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Start a new punch</h3>

            {{-- Worker identity: locked to the logged-in user's matched employee.
                 If no match, the "Clock In" button is disabled — they must contact
                 their supervisor, not pick someone else from a list. --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Clocking in as</label>
                @if($myEmployee)
                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <div class="text-base font-semibold text-gray-900">
                            {{ $myEmployee->first_name }} {{ $myEmployee->last_name }}
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                        Your login is not linked to an employee profile.
                        Please contact your supervisor before clocking in.
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                <select id="ciProjectId" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base" {{ $myEmployee ? '' : 'disabled' }}>
                    <option value="">— Select project —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" data-lat="{{ $p->latitude }}" data-lng="{{ $p->longitude }}" data-radius="{{ $p->geofence_radius_m }}">
                            {{ $p->project_number }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea id="ciNotes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. starting pour at slab B" {{ $myEmployee ? '' : 'disabled' }}></textarea>
            </div>

            <div id="projectGeoFeedback" class="hidden text-xs rounded-lg p-2"></div>

            <button type="button" onclick="doClockIn()" class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-lg py-5 rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $myEmployee ? '' : 'disabled' }}>
                Clock In
            </button>
            <p class="text-xs text-gray-500 text-center">Your supervisor assigns the cost code later during review — just clock in and out.</p>
        </div>
    @endif

    {{-- ─── Recent History ──────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-3">Recent punches</h3>
        @if($recent->isEmpty())
            <p class="text-sm text-gray-500">No punches yet. Clock in above.</p>
        @else
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach($recent as $r)
                    <li class="py-2 flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-900 truncate">{{ $r->project->project_number ?? '—' }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $r->clock_in_at->format('M j, g:iA') }}
                                @if($r->clock_out_at) → {{ $r->clock_out_at->format('g:iA') }} @endif
                                @if($r->hours !== null) · {{ number_format((float)$r->hours, 2) }}h @endif
                            </div>
                        </div>
                        <div class="text-right">
                            @php
                                $statusColor = [
                                    'open'      => 'bg-green-100 text-green-800',
                                    'closed'    => 'bg-blue-100 text-blue-800',
                                    'converted' => 'bg-purple-100 text-purple-800',
                                    'voided'    => 'bg-gray-200 text-gray-700',
                                ][$r->status] ?? 'bg-gray-100';
                            @endphp
                            <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $statusColor }}">{{ ucfirst($r->status) }}</span>
                            @if($r->within_geofence === false)
                                <div class="text-[10px] text-amber-700 mt-0.5">⚠ outside</div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@push('scripts')
<script>
const TC_BASE = window.BASE_URL + '/my-time';
const CSRF = '{{ csrf_token() }}';

let currentPosition = null; // { lat, lng, accuracy }

// Acquire GPS once on load — the button action re-reads if stale.
function updateGps(quiet = false) {
    const el = document.getElementById('gpsStatus');
    if (!navigator.geolocation) {
        el.className = 'rounded-lg p-3 text-sm bg-amber-50 border border-amber-200 text-amber-800';
        el.textContent = 'GPS not available on this device. You can still clock in, but geofence checks will be skipped.';
        el.classList.remove('hidden');
        return;
    }
    if (!quiet) {
        el.className = 'rounded-lg p-3 text-sm bg-gray-50 border border-gray-200 text-gray-700';
        el.textContent = 'Getting location…';
        el.classList.remove('hidden');
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            currentPosition = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                accuracy: Math.round(pos.coords.accuracy || 0),
            };
            el.className = 'rounded-lg p-3 text-sm bg-green-50 border border-green-200 text-green-800';
            el.textContent = '📍 Location captured (accuracy ±' + currentPosition.accuracy + ' m)';
            el.classList.remove('hidden');
            updateProjectGeoFeedback();
        },
        (err) => {
            el.className = 'rounded-lg p-3 text-sm bg-amber-50 border border-amber-200 text-amber-800';
            el.textContent = 'Could not get location: ' + err.message + '. Geofence check will be skipped.';
            el.classList.remove('hidden');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}

function haversineM(a, b) {
    const R = 6371000;
    const toRad = (d) => d * Math.PI / 180;
    const dLat = toRad(b.lat - a.lat);
    const dLng = toRad(b.lng - a.lng);
    const la = toRad(a.lat), lb = toRad(b.lat);
    const h = Math.sin(dLat/2)**2 + Math.cos(la) * Math.cos(lb) * Math.sin(dLng/2)**2;
    return Math.round(2 * R * Math.asin(Math.sqrt(h)));
}

function updateProjectGeoFeedback() {
    const sel = document.getElementById('ciProjectId');
    const fb = document.getElementById('projectGeoFeedback');
    if (!sel || !fb) return;
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) { fb.classList.add('hidden'); return; }
    const pLat = parseFloat(opt.dataset.lat), pLng = parseFloat(opt.dataset.lng), radius = parseInt(opt.dataset.radius);
    if (!pLat || !pLng || !radius || !currentPosition) { fb.classList.add('hidden'); return; }
    const d = haversineM(currentPosition, { lat: pLat, lng: pLng });
    if (d <= radius) {
        fb.className = 'text-xs rounded-lg p-2 bg-green-50 border border-green-200 text-green-800';
        fb.textContent = '✓ You are on site (' + d + ' m from project center, within ' + radius + ' m).';
    } else {
        fb.className = 'text-xs rounded-lg p-2 bg-amber-50 border border-amber-200 text-amber-800';
        fb.textContent = '⚠ You are ' + d + ' m from project center (geofence is ' + radius + ' m). Clock-in will still record but be flagged.';
    }
    fb.classList.remove('hidden');
}

function showActionStatus(kind, msg) {
    const el = document.getElementById('actionStatus');
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function doClockIn() {
    const projSel = document.getElementById('ciProjectId');
    const notes   = document.getElementById('ciNotes');
    const payload = {
        project_id: projSel?.value,
        notes:      notes?.value || null,
        lat:        currentPosition?.lat ?? null,
        lng:        currentPosition?.lng ?? null,
        accuracy_m: currentPosition?.accuracy ?? null,
    };
    if (!payload.project_id) { showActionStatus('error', 'Select a project first.'); return; }

    const r = await fetch(TC_BASE + '/clock-in', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    });
    const body = await r.json();
    if (!r.ok) { showActionStatus('error', body.message || 'Clock-in failed.'); return; }
    showActionStatus('ok', body.message || 'Clocked in.');
    setTimeout(() => location.reload(), 600);
}

async function doClockOut(entryId) {
    // Refresh GPS on clock-out for accurate exit coords.
    await new Promise((resolve) => {
        if (!navigator.geolocation) return resolve();
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                currentPosition = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: Math.round(pos.coords.accuracy || 0) };
                resolve();
            },
            () => resolve(),
            { enableHighAccuracy: true, timeout: 5000 }
        );
    });

    const payload = {
        lat:        currentPosition?.lat ?? null,
        lng:        currentPosition?.lng ?? null,
        accuracy_m: currentPosition?.accuracy ?? null,
    };
    const r = await fetch(TC_BASE + '/' + entryId + '/clock-out', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    });
    const body = await r.json();
    if (!r.ok) { showActionStatus('error', body.message || 'Clock-out failed.'); return; }
    showActionStatus('ok', body.message || 'Clocked out.');
    setTimeout(() => location.reload(), 600);
}

// Live elapsed timer for open punch.
function tickElapsed() {
    const el = document.getElementById('elapsed');
    if (!el) return;
    const start = new Date(el.dataset.start);
    const ms = Date.now() - start.getTime();
    const h = Math.floor(ms / 3600000);
    const m = Math.floor((ms % 3600000) / 60000);
    el.textContent = 'Elapsed: ' + h + 'h ' + m + 'm';
}

document.addEventListener('DOMContentLoaded', function () {
    updateGps();
    const pSel = document.getElementById('ciProjectId');
    if (pSel) pSel.addEventListener('change', updateProjectGeoFeedback);
    if (document.getElementById('elapsed')) {
        tickElapsed();
        setInterval(tickElapsed, 30000);
    }
});
</script>
@endpush

@endsection
