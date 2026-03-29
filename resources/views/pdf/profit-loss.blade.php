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

    {{-- Costs by Code --}}
    @if(count($byCodeData) > 0)
    <div class="section-title">Cost Breakdown by Code</div>
    <table>
        <thead>
            <tr>
                <th>Cost Code</th>
                <th>Description</th>
                <th class="text-right">Cost</th>
                <th class="text-center">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCodeData as $data)
            <tr>
                <td><strong>{{ $data['code'] }}</strong></td>
                <td>{{ $data['name'] }}</td>
                <td class="text-right">${{ number_format($data['cost'], 2) }}</td>
                <td class="text-center">{{ $totalCosts > 0 ? round(($data['cost'] / $totalCosts) * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="text-right">${{ number_format(collect($byCodeData)->sum('cost'), 2) }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tbody>
    </table>
    @endif
@endsection
