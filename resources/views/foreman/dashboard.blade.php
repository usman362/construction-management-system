@extends('layouts.app')
@section('title', 'My Crew Today')
@section('content')

<div class="max-w-3xl mx-auto px-4 py-6 space-y-5">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Crew Today</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $today->format('l, M j, Y') }}
            @if($myEmployee && $isForeman)
                · Foreman: <strong>{{ $myEmployee->first_name }} {{ $myEmployee->last_name }}</strong>
            @elseif(!$isForeman)
                · Showing all active crews (admin view)
            @endif
        </p>
    </div>

    @if($crews->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
            <p class="text-sm text-amber-800">No crews to show.</p>
            @if(!$myEmployee)
                <p class="text-xs text-amber-700 mt-1">Your login isn't linked to an employee record. Ask an admin to link it.</p>
            @elseif(!$isForeman)
                <p class="text-xs text-amber-700 mt-1">You're not assigned as a foreman on any active crew. If this is wrong, ask an admin to update the crew.</p>
            @endif
        </div>
    @endif

    @foreach($crews as $crew)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Crew header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-bold">{{ $crew->name }}</h2>
                        <p class="text-sm text-blue-100 mt-0.5">
                            {{ $crew->project?->project_number ?? '—' }} · {{ $crew->project?->name ?? '—' }}
                        </p>
                        @if($crew->project?->client)
                            <p class="text-xs text-blue-200">{{ $crew->project->client->name }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold">{{ $crew->members->count() }}</div>
                        <div class="text-xs text-blue-200 uppercase tracking-wide">Workers</div>
                    </div>
                </div>
                @if($crew->project?->address)
                    <div class="mt-2 text-xs text-blue-100">
                        📍 {{ trim($crew->project->address . ', ' . $crew->project->city . ', ' . $crew->project->state, ', ') }}
                    </div>
                @endif
            </div>

            {{-- Live status row --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                <div class="px-3 py-3 text-center">
                    <div class="text-2xl font-bold {{ $crew->live_punches->count() > 0 ? 'text-emerald-600' : 'text-gray-400' }}">{{ $crew->live_punches->count() }}</div>
                    <div class="text-[11px] uppercase text-gray-500 tracking-wider">On Clock</div>
                </div>
                <div class="px-3 py-3 text-center">
                    <div class="text-2xl font-bold {{ $crew->equipment_today->count() > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $crew->equipment_today->count() }}</div>
                    <div class="text-[11px] uppercase text-gray-500 tracking-wider">Equipment</div>
                </div>
                <div class="px-3 py-3 text-center">
                    <div class="text-2xl font-bold {{ $crew->open_rfis->count() > 0 ? 'text-rose-600' : 'text-gray-400' }}">{{ $crew->open_rfis->count() }}</div>
                    <div class="text-[11px] uppercase text-gray-500 tracking-wider">Open RFIs</div>
                </div>
            </div>

            {{-- Weather widget — JS will populate if API key set --}}
            @if($crew->project?->latitude && $crew->project?->longitude && $weatherApiKey)
                <div class="px-5 py-3 bg-amber-50 border-b border-amber-100 text-sm" data-weather-lat="{{ $crew->project->latitude }}" data-weather-lng="{{ $crew->project->longitude }}">
                    <span class="text-amber-800 font-semibold">Today's weather:</span>
                    <span class="weather-display text-amber-900">Loading…</span>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="px-5 py-3 grid grid-cols-2 gap-2 border-b border-gray-100">
                <a href="{{ route('time-clock.index') }}"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3 rounded-lg text-center">
                    Clock In/Out
                </a>
                @if($crew->daily_log_today)
                    <a href="{{ route('projects.daily-logs.show', [$crew->project, $crew->daily_log_today->id]) }}"
                       class="bg-green-100 text-green-800 text-sm font-semibold py-3 rounded-lg text-center border border-green-200">
                        ✓ Log Done — View
                    </a>
                @else
                    <a href="{{ route('projects.daily-logs.mobile-create', $crew->project) }}"
                       class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-3 rounded-lg text-center">
                        Log Today's Work
                    </a>
                @endif
                <a href="{{ route('projects.materials.quick-log', $crew->project) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-3 rounded-lg text-center">
                    Log Materials
                </a>
                <a href="{{ route('projects.rfis.index', $crew->project) }}"
                   class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold py-3 rounded-lg text-center">
                    RFIs / Submit New
                </a>
            </div>

            {{-- Crew roster --}}
            @if($crew->members->isNotEmpty())
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-xs font-bold uppercase text-gray-500 tracking-wider mb-2">Crew Roster</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($crew->members as $member)
                            @php
                                $isOnClock = $crew->live_punches->contains(fn ($p) => $p->employee_id === $member->employee_id);
                            @endphp
                            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs">
                                @if($isOnClock)
                                    <span class="flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                @endif
                                <span class="font-medium text-gray-900">{{ $member->employee->first_name ?? '?' }} {{ $member->employee->last_name ?? '' }}</span>
                                @if($member->employee?->employee_number)
                                    <span class="text-gray-400">#{{ $member->employee->employee_number }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Equipment dispatched --}}
            @if($crew->equipment_today->isNotEmpty())
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-xs font-bold uppercase text-gray-500 tracking-wider mb-2">Equipment On-Site</h3>
                    <ul class="space-y-1 text-sm text-gray-700">
                        @foreach($crew->equipment_today as $assignment)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                                {{ $assignment->equipment->name ?? '—' }}
                                @if($assignment->equipment?->type)
                                    <span class="text-xs text-gray-400">({{ $assignment->equipment->type }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Open RFIs --}}
            @if($crew->open_rfis->isNotEmpty())
                <div class="px-5 py-3 bg-rose-50">
                    <h3 class="text-xs font-bold uppercase text-rose-700 tracking-wider mb-2">⚠ Open RFIs to Watch</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach($crew->open_rfis as $rfi)
                            <li>
                                <a href="{{ route('projects.rfis.show', [$crew->project, $rfi->id]) }}" class="text-rose-800 hover:underline">
                                    <strong>{{ $rfi->rfi_number }}</strong> — {{ $rfi->subject }}
                                    <span class="text-xs text-rose-600">({{ $rfi->priority }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endforeach
</div>

@push('scripts')
@if($weatherApiKey)
<script>
// ─── Fetch today's weather for each crew with project lat/lng ─────
const FOREMAN_WEATHER_KEY = @json($weatherApiKey);

document.querySelectorAll('[data-weather-lat]').forEach(async (el) => {
    const lat = el.dataset.weatherLat;
    const lng = el.dataset.weatherLng;
    if (!lat || !lng) return;

    try {
        const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lng}&units=imperial&appid=${FOREMAN_WEATHER_KEY}`;
        const r = await fetch(url);
        if (!r.ok) throw new Error();
        const d = await r.json();
        const out = `${Math.round(d.main.temp)}°F · ${d.weather?.[0]?.description ?? ''} · wind ${Math.round(d.wind?.speed ?? 0)} mph`;
        el.querySelector('.weather-display').textContent = out;
    } catch (e) {
        el.querySelector('.weather-display').textContent = 'unavailable';
    }
});
</script>
@endif
@endpush

@endsection
