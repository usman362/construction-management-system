@extends('layouts.app')

@section('title', 'Monday Morning')

@section('content')
{{--
    Brenda's Monday Morning Dashboard (Phase 3, 2026-05-12).
    A single-screen weekly-rhythm dashboard:
      - Last week's labor cost per project vs budget (red/yellow/green)
      - Pending timesheet approvals (with 1-click "approve last week" CTA)
      - Anomalies (labor booked without a daily log)
      - Equipment 3+ weeks past expected return (rental clock burning)
      - This week's projected payroll
      - Open RFIs + pending Change Orders
--}}

@php
    $burnBg = ['red' => 'bg-rose-50 border-rose-200', 'yellow' => 'bg-amber-50 border-amber-200', 'green' => 'bg-emerald-50 border-emerald-200', 'unknown' => 'bg-gray-50 border-gray-200'];
    $burnDot = ['red' => 'bg-rose-500', 'yellow' => 'bg-amber-500', 'green' => 'bg-emerald-500', 'unknown' => 'bg-gray-400'];
    $burnText = ['red' => 'text-rose-700', 'yellow' => 'text-amber-700', 'green' => 'text-emerald-700', 'unknown' => 'text-gray-500'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                Monday Morning
                <span class="text-sm font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Weekly Review</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $now->format('l, M j, Y') }}
                · Last week: {{ $lastWeek['start']->format('M j') }} – {{ $lastWeek['end']->format('M j') }}
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; Main dashboard</a>
    </div>

    {{-- ───── KPI strip ───── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Last Week Labor</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">${{ number_format(collect($laborRollup)->sum('total_cost'), 0) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format(collect($laborRollup)->sum('total_hours'), 1) }} hours · {{ count($laborRollup) }} projects</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Pending Approval</div>
            <div class="text-2xl font-bold {{ $approvals['count'] > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-1">{{ $approvals['count'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($approvals['hours'], 1) }} hrs · ${{ number_format($approvals['cost'], 0) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">This Week Projected</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($projectedThisWeek['projected_total'], 0) }}</div>
            <div class="text-xs text-gray-500 mt-1">${{ number_format($projectedThisWeek['booked_so_far'], 0) }} booked + ${{ number_format($projectedThisWeek['projected_remaining'], 0) }} projected</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Anomalies</div>
            <div class="text-2xl font-bold {{ count($anomalies) > 0 ? 'text-rose-700' : 'text-emerald-700' }} mt-1">{{ count($anomalies) }}</div>
            <div class="text-xs text-gray-500 mt-1">Labor with no daily log</div>
        </div>
    </div>

    {{-- ───── Pending approvals — 1-click bulk-approve last week ───── --}}
    @if($approvals['count'] > 0)
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-base font-bold text-amber-900">
                    {{ $approvals['count'] }} timesheet(s) waiting on you from last week
                </h2>
                <p class="text-sm text-amber-800 mt-0.5">
                    {{ number_format($approvals['hours'], 1) }} hours · ${{ number_format($approvals['cost'], 0) }} cost
                </p>
            </div>
            @auth
                @if(auth()->user()?->canApproveTimesheets())
                <button type="button" onclick="approveLastWeek()" id="approveLastWeekBtn"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg shadow-sm">
                    Approve All from Last Week
                </button>
                @endif
            @endauth
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-amber-800 uppercase">
                    <tr><th class="text-left py-1">Date</th><th class="text-left">Employee</th><th class="text-left">Project</th><th class="text-right">Hours</th><th class="text-right">Cost</th></tr>
                </thead>
                <tbody class="divide-y divide-amber-100">
                    @foreach($approvals['sample'] as $ts)
                        <tr>
                            <td class="py-1.5">{{ optional($ts->date)->format('M j') }}</td>
                            <td>{{ trim(($ts->employee->first_name ?? '') . ' ' . ($ts->employee->last_name ?? '')) ?: '—' }}</td>
                            <td>{{ $ts->project->project_number ?? '—' }}</td>
                            <td class="text-right">{{ number_format($ts->total_hours, 2) }}</td>
                            <td class="text-right">${{ number_format($ts->total_cost, 0) }}</td>
                        </tr>
                    @endforeach
                    @if($approvals['count'] > $approvals['sample']->count())
                        <tr><td colspan="5" class="py-1.5 text-xs text-amber-700">…and {{ $approvals['count'] - $approvals['sample']->count() }} more (visible after approval).</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ───── Labor by project last week ───── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-gray-900">Last Week Labor by Project</h2>
            <span class="text-xs text-gray-500">
                <span class="inline-block w-2 h-2 rounded-full bg-rose-500 align-middle mr-1"></span> &gt;10% burn
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500 align-middle mx-1 ml-3"></span> 5-10%
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 align-middle mx-1 ml-3"></span> &lt;5%
            </span>
        </div>
        @if(count($laborRollup) === 0)
            <p class="text-sm text-gray-500 py-2">No labor booked last week.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 uppercase border-b">
                        <tr>
                            <th class="text-left py-2">Project</th>
                            <th class="text-right">Hours (Reg/OT/DT)</th>
                            <th class="text-right">Cost</th>
                            <th class="text-right">Budget</th>
                            <th class="text-right">Week %</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($laborRollup as $row)
                            <tr class="{{ $burnBg[$row->burn_state] }} border-l-4 {{ ['red'=>'border-l-rose-500','yellow'=>'border-l-amber-500','green'=>'border-l-emerald-500','unknown'=>'border-l-gray-300'][$row->burn_state] }}">
                                <td class="py-2 pl-3">
                                    <a href="{{ route('projects.show', $row->project) }}" class="font-medium text-blue-700 hover:underline">{{ $row->project->project_number }}</a>
                                    <span class="text-gray-500 text-xs ml-1">{{ \Illuminate\Support\Str::limit($row->project->name, 40) }}</span>
                                </td>
                                <td class="text-right text-gray-700">
                                    {{ number_format($row->reg_hours, 1) }}
                                    @if($row->ot_hours > 0) <span class="text-amber-700">/ {{ number_format($row->ot_hours, 1) }}</span>@endif
                                    @if($row->dt_hours > 0) <span class="text-rose-700">/ {{ number_format($row->dt_hours, 1) }}</span>@endif
                                </td>
                                <td class="text-right font-semibold text-gray-900">${{ number_format($row->total_cost, 0) }}</td>
                                <td class="text-right text-gray-500">{{ $row->budget > 0 ? '$' . number_format($row->budget, 0) : '—' }}</td>
                                <td class="text-right">
                                    @if($row->week_burn_pct !== null)
                                        <span class="{{ $burnText[$row->burn_state] }} font-semibold">{{ number_format($row->week_burn_pct, 1) }}%</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="text-center pr-3">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $burnDot[$row->burn_state] }}"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ───── Anomalies + Equipment overdue (two columns) ───── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Anomalies --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-3">Anomalies <span class="text-xs font-medium text-gray-400">(needs attention)</span></h2>
            @if(count($anomalies) === 0)
                <p class="text-sm text-emerald-700">Nothing odd — every project that booked labor also has a daily log.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($anomalies as $a)
                        <li class="flex items-start gap-2 p-2 bg-rose-50 border border-rose-200 rounded">
                            <span class="inline-block w-1.5 h-1.5 mt-1.5 rounded-full bg-rose-500"></span>
                            <div>
                                <strong class="text-rose-900">{{ $a->project?->project_number ?? '—' }}</strong>
                                <span class="text-gray-600"> · {{ $a->date->format('M j') }} · {{ number_format($a->hours, 1) }} hrs · ${{ number_format($a->cost, 0) }}</span>
                                <p class="text-xs text-rose-700 mt-0.5">{{ $a->description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Equipment overdue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-3">Equipment Overdue <span class="text-xs font-medium text-gray-400">(3+ weeks past expected return)</span></h2>
            @if(count($overdueEq) === 0)
                <p class="text-sm text-emerald-700">Nothing stuck — all equipment returned on schedule.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($overdueEq as $e)
                        <li class="flex items-start gap-2 p-2 bg-amber-50 border border-amber-200 rounded">
                            <span class="inline-block w-1.5 h-1.5 mt-1.5 rounded-full bg-amber-500"></span>
                            <div>
                                <strong class="text-amber-900">{{ $e->assignment->equipment->name ?? '—' }}</strong>
                                <span class="text-gray-600"> on {{ $e->assignment->project->project_number ?? '—' }}</span>
                                <p class="text-xs text-amber-700 mt-0.5">
                                    {{ $e->weeks_late }} week(s) late · expected back {{ optional($e->assignment->expected_return_date)->format('M j') }}
                                    @if($e->extra_cost > 0) · ~${{ number_format($e->extra_cost, 0) }} in extra rental @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ───── Open RFIs + pending COs (two columns) ───── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-3">Open RFIs <span class="text-xs font-medium text-gray-400">({{ $openRfis->count() }})</span></h2>
            @if($openRfis->isEmpty())
                <p class="text-sm text-emerald-700">No open RFIs.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($openRfis as $rfi)
                        <li class="flex items-start gap-2">
                            <span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ $rfi->rfi_number }}</span>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('projects.rfis.show', [$rfi->project, $rfi]) }}" class="text-blue-700 hover:underline">{{ \Illuminate\Support\Str::limit($rfi->subject, 60) }}</a>
                                <div class="text-xs text-gray-500">{{ $rfi->project?->project_number ?? '—' }} · {{ ucfirst($rfi->priority) }} @if($rfi->needed_by) · needed by {{ \Carbon\Carbon::parse($rfi->needed_by)->format('M j') }} @endif</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-3">Pending Change Orders <span class="text-xs font-medium text-gray-400">({{ $pendingCOs->count() }})</span></h2>
            @if($pendingCOs->isEmpty())
                <p class="text-sm text-emerald-700">No pending COs.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($pendingCOs as $co)
                        <li class="flex items-start gap-2">
                            <span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">CO {{ $co->co_number }}</span>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('projects.change-orders.show', [$co->project, $co]) }}" class="text-blue-700 hover:underline">{{ \Illuminate\Support\Str::limit($co->title ?: '(no title)', 50) }}</a>
                                <div class="text-xs text-gray-500">{{ $co->project?->project_number ?? '—' }} · ${{ number_format($co->amount, 0) }} @if($co->date) · {{ \Carbon\Carbon::parse($co->date)->format('M j') }} @endif</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
function approveLastWeek() {
    const btn = document.getElementById('approveLastWeekBtn');
    Swal.fire({
        title: 'Approve all from last week?',
        html: 'Range: <strong>{{ $lastWeek['start']->format('M j') }} – {{ $lastWeek['end']->format('M j, Y') }}</strong><br><span class="text-xs text-gray-500">Only Submitted timesheets will be flipped to Approved.</span>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Approve all',
    }).then(r => {
        if (!r.isConfirmed) return;
        btn.disabled = true; btn.textContent = 'Approving…';
        $.ajax({
            url: '{{ route("timesheets.bulk-approve-range") }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                date_from: '{{ $lastWeek['start']->toDateString() }}',
                date_to:   '{{ $lastWeek['end']->toDateString() }}',
            }),
            success: function(res){
                Toast.fire({ icon: 'success', title: res.message });
                setTimeout(() => location.reload(), 800);
            },
            error: function(xhr){
                Toast.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Approval failed' });
                btn.disabled = false; btn.textContent = 'Approve All from Last Week';
            },
        });
    });
}
</script>
@endpush
@endsection
