@extends('layouts.app')
@section('title', 'WIP Report')
@section('content')

<div class="max-w-full mx-auto px-4 py-6 space-y-6">

    {{-- Page header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Work-in-Progress (WIP) Report</h1>
            <p class="text-sm text-gray-500 mt-1">
                Per-project contract value, cost-to-date, earned revenue, billed amount, and over/under billing.
                Generated {{ $generatedAt->format('M j, Y g:i A') }}.
            </p>
        </div>
        <a href="{{ route('reports.wip', ['export' => 1]) }}" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-emerald-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-emerald-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export Excel
        </a>
    </div>

    {{-- Totals strip --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-500 font-bold">Contract Value</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($totals['contract_value'], 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-500 font-bold">Cost to Date</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($totals['cost_to_date'], 0) }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-3">
            <p class="text-[10px] uppercase tracking-wide text-blue-700 font-bold">Earned Revenue</p>
            <p class="text-lg font-bold text-blue-900">${{ number_format($totals['earned_revenue'], 0) }}</p>
        </div>
        <div class="bg-emerald-50 rounded-lg shadow-sm border border-emerald-200 p-3">
            <p class="text-[10px] uppercase tracking-wide text-emerald-700 font-bold">Billed to Date</p>
            <p class="text-lg font-bold text-emerald-900">${{ number_format($totals['billed_to_date'], 0) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg shadow-sm border border-amber-200 p-3" title="Billed faster than earned — cash advance">
            <p class="text-[10px] uppercase tracking-wide text-amber-700 font-bold">Over Billed</p>
            <p class="text-lg font-bold text-amber-900">${{ number_format($totals['over_billed'], 0) }}</p>
        </div>
        <div class="bg-rose-50 rounded-lg shadow-sm border border-rose-200 p-3" title="Earned but not yet billed — revenue at risk">
            <p class="text-[10px] uppercase tracking-wide text-rose-700 font-bold">Under Billed</p>
            <p class="text-lg font-bold text-rose-900">${{ number_format($totals['under_billed'], 0) }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Project #</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Client</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Status</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Contract</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Est. Cost</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Cost to Date</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">% Complete</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Earned</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Billed</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Over/Under</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Proj. Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $r)
                        <tr class="hover:bg-blue-50/30">
                            <td class="px-3 py-2 font-mono text-blue-700 font-semibold">
                                <a href="{{ route('projects.show', $r->project_id) }}" class="hover:underline">{{ $r->project_number }}</a>
                            </td>
                            <td class="px-3 py-2 text-gray-900">{{ $r->name }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $r->client }}</td>
                            <td class="px-3 py-2">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    @switch($r->status)
                                        @case('active')    bg-green-100 text-green-800   @break
                                        @case('completed') bg-blue-100  text-blue-800    @break
                                        @case('awarded')   bg-indigo-100 text-indigo-800 @break
                                        @case('bidding')   bg-amber-100 text-amber-800   @break
                                        @case('on_hold')   bg-orange-100 text-orange-800 @break
                                        @default          bg-gray-100  text-gray-700
                                    @endswitch">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">${{ number_format($r->contract_value, 2) }}</td>
                            <td class="px-3 py-2 text-right">${{ number_format($r->estimated_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right">${{ number_format($r->cost_to_date, 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $r->percent_complete }}%"></div>
                                    </div>
                                    <span class="text-xs">{{ $r->percent_complete }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right text-blue-700 font-semibold">${{ number_format($r->earned_revenue, 2) }}</td>
                            <td class="px-3 py-2 text-right text-emerald-700 font-semibold">${{ number_format($r->billed_to_date, 2) }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $r->over_under > 0 ? 'text-amber-700' : ($r->over_under < 0 ? 'text-rose-700' : 'text-gray-500') }}">
                                {{ $r->over_under >= 0 ? '+' : '' }}${{ number_format($r->over_under, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right font-semibold {{ $r->projected_profit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ $r->projected_profit >= 0 ? '+' : '' }}${{ number_format($r->projected_profit, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-4 py-8 text-center text-gray-400">No projects to report.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr class="font-bold">
                            <td colspan="4" class="px-3 py-3 text-right uppercase text-xs text-gray-700">Totals</td>
                            <td class="px-3 py-3 text-right">${{ number_format($totals['contract_value'], 2) }}</td>
                            <td class="px-3 py-3 text-right">${{ number_format($rows->sum('estimated_cost'), 2) }}</td>
                            <td class="px-3 py-3 text-right">${{ number_format($totals['cost_to_date'], 2) }}</td>
                            <td></td>
                            <td class="px-3 py-3 text-right text-blue-700">${{ number_format($totals['earned_revenue'], 2) }}</td>
                            <td class="px-3 py-3 text-right text-emerald-700">${{ number_format($totals['billed_to_date'], 2) }}</td>
                            <td class="px-3 py-3 text-right">${{ number_format($rows->sum('over_under'), 2) }}</td>
                            <td class="px-3 py-3 text-right">${{ number_format($rows->sum('projected_profit'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 italic">
        Cost-to-date includes vendor commitments, vendor invoices, and non-rejected timesheet labor.
        Earned revenue is calculated as cost-percent-complete × contract value (cost-to-cost method).
    </p>
</div>

@endsection
