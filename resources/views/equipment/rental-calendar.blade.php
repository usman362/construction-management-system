@extends('layouts.app')
@section('title', 'Equipment Rental Calendar')
@section('content')

<div class="max-w-full mx-auto px-4 py-6 space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Equipment Rental Calendar</h1>
        <p class="text-sm text-gray-500 mt-1">
            Bar timeline of every active rental + assignment. Color-coded by how close the rental is to the off-rent date.
            Email alerts go out 7 / 3 / 1 day(s) before each due date.
        </p>
    </div>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Active Rentals</p>
            <p class="text-xl font-bold text-gray-900">{{ $summary['total'] }}</p>
        </div>
        <div class="bg-rose-100 rounded-lg border border-rose-300 p-3">
            <p class="text-[10px] uppercase font-bold text-rose-800 tracking-wider">Overdue</p>
            <p class="text-xl font-bold text-rose-900">{{ $summary['overdue'] }}</p>
        </div>
        <div class="bg-red-50 rounded-lg border border-red-200 p-3">
            <p class="text-[10px] uppercase font-bold text-red-700 tracking-wider">Due ≤2 days</p>
            <p class="text-xl font-bold text-red-900">{{ $summary['red'] }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg border border-amber-200 p-3">
            <p class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Due ≤7 days</p>
            <p class="text-xl font-bold text-amber-900">{{ $summary['amber'] }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">No Due Date</p>
            <p class="text-xl font-bold text-gray-700">{{ $summary['no_due_date'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All types</option>
                    @foreach(['rented' => 'Rented (3rd party)', 'owned' => 'Owned', 'third_party' => 'Third Party'] as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Project</label>
                <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All projects</option>
                    @foreach($allProjects as $p)
                        <option value="{{ $p->id }}" @selected((int)($filters['project_id'] ?? 0) === $p->id)>{{ $p->project_number }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
                <a href="{{ route('equipment.rental-calendar') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
            </div>
        </div>
    </form>

    {{-- Calendar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($assignments->isEmpty())
            <div class="py-16 text-center text-gray-400 text-sm">
                No active rentals. Equipment that's been checked out via QR or assigned to a project will appear here.
            </div>
        @else
            {{-- Date axis header --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-2 text-xs">
                    <div class="col-span-3 font-bold text-gray-700 uppercase tracking-wider">Equipment / Project</div>
                    <div class="col-span-9 relative h-5">
                        @foreach($axisTicks as $tick)
                            <div class="absolute top-0 text-[10px] text-gray-500"
                                 style="left: {{ $tick['offset_pct'] }}%; transform: translateX(-50%);">
                                {{ $tick['label'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Rows --}}
            <div class="divide-y divide-gray-100">
                @foreach($assignments as $a)
                    <div class="px-4 py-3 hover:bg-blue-50/30">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            {{-- Left label --}}
                            <div class="col-span-3 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ $a->equipment->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-500 truncate">
                                    {{ $a->project->project_number ?? '—' }}
                                    @if($a->equipment?->type)
                                        · {{ ucwords(str_replace('_', ' ', $a->equipment->type)) }}
                                    @endif
                                </p>
                            </div>

                            {{-- Bar timeline --}}
                            <div class="col-span-9 relative h-7 bg-gray-50 rounded">
                                {{-- Today marker (vertical line) --}}
                                @if($todayOffsetPct !== null)
                                    <div class="absolute top-0 bottom-0 w-px bg-blue-500 z-10"
                                         style="left: {{ $todayOffsetPct }}%;"
                                         title="Today"></div>
                                @endif

                                {{-- The rental bar --}}
                                @php
                                    $barClass = match($a->urgency) {
                                        'overdue' => 'bg-rose-700 border-rose-900 text-white',
                                        'red'     => 'bg-red-500 border-red-700 text-white',
                                        'amber'   => 'bg-amber-400 border-amber-600 text-amber-900',
                                        default   => 'bg-emerald-500 border-emerald-700 text-white',
                                    };
                                @endphp
                                <div class="absolute top-1 bottom-1 rounded border {{ $barClass }} flex items-center px-2 text-[10px] font-semibold whitespace-nowrap overflow-hidden"
                                     style="left: {{ $a->bar_offset_pct }}%; width: {{ $a->bar_width_pct }}%;"
                                     title="{{ $a->equipment->name ?? '' }} → {{ $a->expected_return_date ? $a->expected_return_date->format('M j, Y') : 'no due date' }}">
                                    @if($a->expected_return_date)
                                        {{ $a->expected_return_date->format('M j') }}
                                        @if($a->urgency_label)
                                            <span class="ml-1 opacity-75">· {{ $a->urgency_label }}</span>
                                        @endif
                                    @else
                                        ongoing
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-900">
        <strong>Tip:</strong> Set the <em>Expected Return</em> date when you check equipment out (via QR scan or the assign form). The calendar uses that date to draw the bar's right edge and to schedule email alerts. Equipment without a due date shows as a faded "ongoing" bar.
    </div>
</div>

@endsection
