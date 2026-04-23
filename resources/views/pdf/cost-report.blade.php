@extends('pdf.layout')

@section('title', 'Cost Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('header-right')
    <div class="meta-label">Report Date</div>
    <div class="meta-value">{{ now()->format('M j, Y') }}</div>
@endsection

@section('extra-styles')
<style>
    .cost-type-header {
        background: #e8edf3;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cost-type-header td {
        padding: 6px 8px;
        color: #1e3a5f;
        border-bottom: 2px solid #1e3a5f;
    }
    .cost-type-subtotal td {
        background: #f0f4f8;
        font-weight: bold;
        border-top: 1px solid #ccc;
    }
    .sub-line td {
        padding-left: 20px;
        font-size: 9px;
    }
    .grand-total td {
        background: #1e3a5f;
        color: #fff;
        font-weight: bold;
        font-size: 11px;
        padding: 8px;
    }
    .over-budget {
        color: #dc2626;
        font-weight: bold;
    }
    .under-budget {
        color: #16a34a;
    }
    .manhour-table {
        margin-top: 15px;
    }
    .manhour-table th {
        background: #374151;
        color: #fff;
        font-size: 9px;
        padding: 5px 8px;
    }
    .manhour-table td {
        font-size: 9px;
        padding: 4px 8px;
    }
</style>
@endsection

@section('content')
    {{-- Detail-only rows power the summary totals (headers + subtotals
         are view-only decorations and would double-count if summed). --}}
    @php
        $detailRows = collect($costCodeData)
            ->filter(fn ($r) => !($r['is_header'] ?? false) && !($r['is_group_total'] ?? false))
            ->values();
        $totalBudget    = $detailRows->sum('budget');
        $totalCommitted = $detailRows->sum('committed');
        $totalInvoiced  = $detailRows->sum('invoiced');
        $totalBalance   = $detailRows->sum('balance');
    @endphp

    {{-- Summary Grid --}}
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Original Budget</div>
            <div class="value">${{ number_format($totalBudget, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Approved COs</div>
            <div class="value">${{ number_format($changeOrders->sum('amount'), 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Revised Budget</div>
            <div class="value">${{ number_format($totalBudget + $changeOrders->sum('amount'), 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Committed</div>
            <div class="value">${{ number_format($totalCommitted, 2) }}</div>
        </div>
        <div class="summary-item {{ $totalBalance >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Remaining Balance</div>
            <div class="value">${{ number_format($totalBalance, 2) }}</div>
        </div>
    </div>

    {{-- Cost Breakdown: Budget → Cost Type → Phase Code --}}
    <div class="section-title">Cost Breakdown by Cost Type &amp; Phase Code</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Phase Code</th>
                <th style="width: 22%">Description</th>
                <th class="text-right" style="width: 12%">Original Budget</th>
                <th class="text-right" style="width: 12%">Revised Budget</th>
                <th class="text-right" style="width: 12%">Committed</th>
                <th class="text-right" style="width: 10%">Invoiced</th>
                <th class="text-right" style="width: 12%">Balance</th>
                <th class="text-center" style="width: 10%">% Complete</th>
            </tr>
        </thead>
        <tbody>
            @foreach($costCodeData as $data)
                @if($data['is_header'] ?? false)
                    <tr class="cost-type-header">
                        <td colspan="8">{{ $data['name'] ?? $data['cost_type'] ?? '' }}</td>
                    </tr>
                @elseif($data['is_group_total'] ?? false)
                    <tr class="cost-type-subtotal">
                        <td colspan="2"><em>{{ $data['name'] ?? 'Subtotal' }}</em></td>
                        <td class="text-right">${{ number_format($data['budget'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['budget'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['committed'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['invoiced'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['balance'], 2) }}</td>
                        <td class="text-center">{{ $data['percentage_complete'] }}%</td>
                    </tr>
                @else
                    <tr class="sub-line">
                        <td><strong>{{ $data['code'] }}</strong></td>
                        <td>{{ $data['name'] }}</td>
                        <td class="text-right">${{ number_format($data['budget'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['budget'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['committed'], 2) }}</td>
                        <td class="text-right">${{ number_format($data['invoiced'], 2) }}</td>
                        <td class="text-right {{ $data['balance'] >= 0 ? 'under-budget' : 'over-budget' }}">${{ number_format($data['balance'], 2) }}</td>
                        <td class="text-center">{{ $data['percentage_complete'] }}%</td>
                    </tr>
                @endif
            @endforeach

            <tr class="grand-total">
                <td colspan="2">GRAND TOTAL</td>
                <td class="text-right">${{ number_format($totalBudget, 2) }}</td>
                <td class="text-right">${{ number_format($totalBudget + $changeOrders->sum('amount'), 2) }}</td>
                <td class="text-right">${{ number_format($totalCommitted, 2) }}</td>
                <td class="text-right">${{ number_format($totalInvoiced, 2) }}</td>
                <td class="text-right">${{ number_format($totalBalance, 2) }}</td>
                <td class="text-center">{{ $totalBudget > 0 ? round(($totalCommitted / $totalBudget) * 100, 1) : 0 }}%</td>
            </tr>
        </tbody>
    </table>

    {{-- Approved Change Orders --}}
    @if(count($changeOrders) > 0)
    <div class="section-title">Approved Change Orders</div>
    <table>
        <thead>
            <tr>
                <th style="width: 12%">CO #</th>
                <th style="width: 50%">Description</th>
                <th class="text-right" style="width: 18%">Amount</th>
                <th class="text-center" style="width: 20%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($changeOrders as $co)
            <tr>
                <td>{{ $co->co_number }}</td>
                <td>{{ $co->title ?? $co->description }}</td>
                <td class="text-right">${{ number_format($co->amount, 2) }}</td>
                <td class="text-center"><span class="badge badge-green">Approved</span></td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>Total Change Orders</strong></td>
                <td class="text-right"><strong>${{ number_format($changeOrders->sum('amount'), 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Manhour Summary (per phase code rows; totals below) --}}
    @php
        $mhRows = is_array($manhourData) ? array_values($manhourData) : [];
        $mhTotalBudget = collect($mhRows)->sum('budget_hours');
        $mhTotalActual = collect($mhRows)->sum('actual_hours');
        $mhTotalCost = collect($mhRows)->sum('labor_cost');
        $mhVariance = $mhTotalBudget - $mhTotalActual;
    @endphp
    <div class="section-title">Manhour Summary</div>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Budget Hours</div>
            <div class="value">{{ number_format($mhTotalBudget, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Actual Hours</div>
            <div class="value">{{ number_format($mhTotalActual, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Labor Cost</div>
            <div class="value">${{ number_format($mhTotalCost, 2) }}</div>
        </div>
        <div class="summary-item {{ $mhVariance >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Hours Variance</div>
            <div class="value">{{ number_format($mhVariance, 1) }} hrs</div>
        </div>
    </div>
@endsection
