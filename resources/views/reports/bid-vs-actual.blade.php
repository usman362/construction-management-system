@extends('layouts.app')
@section('title', 'Bid vs Actual Report')

@section('content')
@php
    $varClass = function($pct) {
        if ($pct === null) return 'text-gray-500';
        if ($pct >  5) return 'text-rose-700 font-semibold';
        if ($pct < -5) return 'text-emerald-700 font-semibold';
        return 'text-gray-700';
    };
    $varBg = function($pct) {
        if ($pct === null) return '';
        if ($pct >  5) return 'bg-rose-50';
        if ($pct < -5) return 'bg-emerald-50';
        return '';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bid vs Actual Report</h1>
            <p class="text-sm text-gray-500 mt-1">
                What we bid vs what we actually spent. Use these variance patterns to inform your next bids.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; Dashboard</a>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-xl p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Project status</label>
            <select name="status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="closed"    @selected($statusFilter === 'closed')>Closed only</option>
                <option value="completed" @selected($statusFilter === 'completed')>Completed only</option>
                <option value="active"    @selected($statusFilter === 'active')>Active only</option>
                <option value="all"       @selected($statusFilter === 'all')>All projects</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Sort by</label>
            <select name="sort" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="variance_dollar" @selected($sortBy === 'variance_dollar')>Variance $ (biggest first)</option>
                <option value="variance_pct"    @selected($sortBy === 'variance_pct')>Variance % (worst overrun first)</option>
                <option value="bid"             @selected($sortBy === 'bid')>Bid total</option>
                <option value="actual"          @selected($sortBy === 'actual')>Actual total</option>
                <option value="name"            @selected($sortBy === 'name')>Project number</option>
            </select>
        </div>
        <div class="ml-auto text-xs text-gray-500">
            {{ count($rows) }} project(s)
        </div>
    </form>

    {{-- Portfolio rollup --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Bid Total</div>
            <div class="text-xl font-bold text-gray-900 mt-1">${{ number_format($portfolioBid, 0) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Actual Total</div>
            <div class="text-xl font-bold text-gray-900 mt-1">${{ number_format($portfolioActual, 0) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 {{ $varBg($portfolioVarPct) }}">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Variance $</div>
            <div class="text-xl font-bold mt-1 {{ $varClass($portfolioVarPct) }}">
                {{ $portfolioVar >= 0 ? '+' : '−' }}${{ number_format(abs($portfolioVar), 0) }}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 {{ $varBg($portfolioVarPct) }}">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Variance %</div>
            <div class="text-xl font-bold mt-1 {{ $varClass($portfolioVarPct) }}">
                @if($portfolioVarPct !== null)
                    {{ $portfolioVarPct >= 0 ? '+' : '' }}{{ number_format($portfolioVarPct, 1) }}%
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Portfolio table --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Project-Level Bid vs Actual</h2>
            <p class="text-xs text-gray-500">Click a project to drill into per-cost-code variance.</p>
        </div>
        @if(count($rows) === 0)
            <p class="px-5 py-6 text-sm text-gray-500">No projects match this filter. Try switching to "All projects" above.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Project</th>
                            <th class="px-4 py-2 text-left">Client</th>
                            <th class="px-4 py-2 text-right">Bid</th>
                            <th class="px-4 py-2 text-right">Actual</th>
                            <th class="px-4 py-2 text-right">Labor</th>
                            <th class="px-4 py-2 text-right">Invoices</th>
                            <th class="px-4 py-2 text-right">Variance $</th>
                            <th class="px-4 py-2 text-right">Variance %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rows as $r)
                            <tr class="hover:bg-gray-50 {{ $varBg($r['variance_pct']) }}">
                                <td class="px-4 py-2">
                                    <a href="{{ route('reports.bid-vs-actual', ['status' => $statusFilter, 'sort' => $sortBy, 'project_id' => $r['project']->id]) }}"
                                       class="text-blue-700 hover:underline font-mono">{{ $r['project']->project_number }}</a>
                                    <span class="text-xs text-gray-500 ml-1">{{ \Illuminate\Support\Str::limit($r['project']->name, 38) }}</span>
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $r['project']->client?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">${{ number_format($r['bid_total'], 0) }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900">${{ number_format($r['actual_total'], 0) }}</td>
                                <td class="px-4 py-2 text-right text-gray-500">${{ number_format($r['labor_cost'], 0) }}</td>
                                <td class="px-4 py-2 text-right text-gray-500">${{ number_format($r['invoice_cost'], 0) }}</td>
                                <td class="px-4 py-2 text-right {{ $varClass($r['variance_pct']) }}">
                                    {{ $r['variance_dollar'] >= 0 ? '+' : '−' }}${{ number_format(abs($r['variance_dollar']), 0) }}
                                </td>
                                <td class="px-4 py-2 text-right {{ $varClass($r['variance_pct']) }}">
                                    @if($r['variance_pct'] !== null)
                                        {{ $r['variance_pct'] >= 0 ? '+' : '' }}{{ number_format($r['variance_pct'], 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Per-project drill --}}
    @if($drillProject)
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Cost-Code Breakdown — {{ $drillProject->project_number }}</h2>
                    <p class="text-xs text-gray-500">{{ $drillProject->name }} · {{ $drillProject->client?->name ?? '—' }}</p>
                </div>
                <a href="{{ route('reports.bid-vs-actual', ['status' => $statusFilter, 'sort' => $sortBy]) }}" class="text-sm text-blue-600 hover:underline">Clear drill</a>
            </div>
            @if(count($drillRows) === 0)
                <p class="px-5 py-6 text-sm text-gray-500">No cost-code-tagged data for this project. Either the estimate / budget lines or the actuals (timesheets/invoices) don't have cost codes set yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Cost Code</th>
                                <th class="px-4 py-2 text-right">Bid</th>
                                <th class="px-4 py-2 text-right">Actual</th>
                                <th class="px-4 py-2 text-right">Labor</th>
                                <th class="px-4 py-2 text-right">Invoices</th>
                                <th class="px-4 py-2 text-right">Variance $</th>
                                <th class="px-4 py-2 text-right">Variance %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($drillRows as $r)
                                <tr class="hover:bg-gray-50 {{ $varBg($r['variance_pct']) }}">
                                    <td class="px-4 py-2">
                                        <span class="font-mono text-sm">{{ $r['cost_code']->code }}</span>
                                        <span class="text-xs text-gray-500 ml-1">{{ \Illuminate\Support\Str::limit($r['cost_code']->name, 36) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700">${{ number_format($r['bid'], 0) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900">${{ number_format($r['actual'], 0) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">${{ number_format($r['labor_cost'], 0) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">${{ number_format($r['invoice_cost'], 0) }}</td>
                                    <td class="px-4 py-2 text-right {{ $varClass($r['variance_pct']) }}">
                                        {{ $r['variance_dollar'] >= 0 ? '+' : '−' }}${{ number_format(abs($r['variance_dollar']), 0) }}
                                    </td>
                                    <td class="px-4 py-2 text-right {{ $varClass($r['variance_pct']) }}">
                                        @if($r['variance_pct'] !== null)
                                            {{ $r['variance_pct'] >= 0 ? '+' : '' }}{{ number_format($r['variance_pct'], 1) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <p class="text-xs text-gray-500">
        <strong>How "Bid" is calculated:</strong> approved estimate line totals → any estimate line totals → original budget line totals → projects.estimate → contract_value (in that order, whichever is non-zero first).
        <strong>How "Actual" is calculated:</strong> approved/submitted timesheet total_cost + approved/paid vendor invoice amounts. Standalone budget overrides aren't double-counted.
    </p>
</div>
@endsection
