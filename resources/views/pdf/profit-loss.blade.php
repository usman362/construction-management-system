@extends('pdf.layout')

@section('title', 'Profit & Loss Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('content')
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Revenue</div>
            <div class="value">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Costs</div>
            <div class="value">${{ number_format($totalCosts, 2) }}</div>
        </div>
        <div class="summary-item {{ $margin >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Net Margin</div>
            <div class="value">${{ number_format($margin, 2) }}</div>
        </div>
        <div class="summary-item {{ $marginPercentage >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Margin %</div>
            <div class="value">{{ $marginPercentage }}%</div>
        </div>
    </div>

    {{-- P&L Summary --}}
    <div class="section-title">Profit & Loss Summary</div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Revenue (Paid Invoices)</strong></td>
                <td class="text-right">${{ number_format($totalRevenue, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Costs (Commitments + Invoices)</strong></td>
                <td class="text-right" style="color: #dc2626;">(${{ number_format($totalCosts, 2) }})</td>
            </tr>
            <tr class="totals-row">
                <td><strong>NET PROFIT / (LOSS)</strong></td>
                <td class="text-right" style="color: {{ $margin >= 0 ? '#16a34a' : '#dc2626' }}; font-size: 13px;">
                    ${{ number_format($margin, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Costs grouped by Cost Type & Phase Code --}}
    @if(count($byCodeData) > 0)
    @php
        $detailRows = collect($byCodeData)
            ->filter(fn ($r) => !($r['is_header'] ?? false) && !($r['is_group_total'] ?? false))
            ->values();
        $detailCostTotal = $detailRows->sum('cost');
    @endphp
    <div class="section-title">Cost Breakdown by Cost Type &amp; Phase Code</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Cost Type</th>
                <th style="width: 12%">Phase Code</th>
                <th>Description</th>
                <th class="text-right" style="width: 15%">Cost</th>
                <th class="text-center" style="width: 12%">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCodeData as $data)
                @if($data['is_header'] ?? false)
                    <tr style="background:#e8edf3; font-weight:bold;">
                        <td colspan="5" style="padding:6px 8px; color:#1e3a5f; text-transform:uppercase; letter-spacing:0.5px;">{{ $data['name'] ?? $data['cost_type'] ?? '' }}</td>
                    </tr>
                @elseif($data['is_group_total'] ?? false)
                    <tr style="background:#f0f4f8; font-weight:bold;">
                        <td colspan="3"><em>{{ $data['name'] ?? 'Subtotal' }}</em></td>
                        <td class="text-right">${{ number_format($data['cost'] ?? 0, 2) }}</td>
                        <td class="text-center">{{ $detailCostTotal > 0 ? round((($data['cost'] ?? 0) / $detailCostTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $data['cost_type'] ?? '' }}</td>
                        <td><strong>{{ $data['code'] }}</strong></td>
                        <td>{{ $data['name'] }}</td>
                        <td class="text-right">${{ number_format($data['cost'] ?? 0, 2) }}</td>
                        <td class="text-center">{{ $detailCostTotal > 0 ? round((($data['cost'] ?? 0) / $detailCostTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                @endif
            @endforeach
            <tr class="totals-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td class="text-right">${{ number_format($detailCostTotal, 2) }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tbody>
    </table>
    @endif
@endsection
