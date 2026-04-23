@extends('pdf.layout')

@section('title', 'Budget Forecast Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('header-right')
    <div class="meta-label">Report Date</div>
    <div class="meta-value">{{ now()->format('M j, Y') }}</div>
@endsection

@section('extra-styles')
<style>
    .forecast-variance-positive { color: #16a34a; font-weight: bold; }
    .forecast-variance-negative { color: #dc2626; font-weight: bold; }
    .grand-total td {
        background: #1e3a5f;
        color: #fff;
        font-weight: bold;
        font-size: 11px;
        padding: 8px;
    }
    .cost-type-header td {
        background: #e8edf3;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1e3a5f;
        padding: 6px 8px;
        border-bottom: 2px solid #1e3a5f;
    }
    .cost-type-subtotal td {
        background: #f0f4f8;
        font-weight: bold;
        border-top: 1px solid #ccc;
    }
</style>
@endsection

@section('content')
    {{-- Detail-only rows power totals (header + subtotal rows are decoration). --}}
    @php
        $detailRows = collect($costCodeData)
            ->filter(fn ($r) => !($r['is_header'] ?? false) && !($r['is_group_total'] ?? false))
            ->values();
        $totalOriginal   = $detailRows->sum('original_budget');
        $totalForecast   = $detailRows->sum('forecast_budget');
        $totalCommitted  = $detailRows->sum('committed');
        $totalInvoiced   = $detailRows->sum('invoiced');
        $totalBalance    = $detailRows->sum('balance');
    @endphp

    {{-- Budget Summary --}}
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Original Budget</div>
            <div class="value">${{ number_format($originalBudgetTotal, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Approved Changes</div>
            <div class="value">${{ number_format($forecastBudgetTotal - $originalBudgetTotal, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Forecast Budget</div>
            <div class="value">${{ number_format($forecastBudgetTotal, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Committed</div>
            <div class="value">${{ number_format($totalCommitted, 2) }}</div>
        </div>
        <div class="summary-item {{ ($forecastBudgetTotal - $totalCommitted) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Projected Balance</div>
            <div class="value">${{ number_format($forecastBudgetTotal - $totalCommitted, 2) }}</div>
        </div>
    </div>

    {{-- Forecast by Cost Type & Phase Code --}}
    <div class="section-title">Forecast by Cost Type &amp; Phase Code</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Phase Code</th>
                <th style="width: 18%">Description</th>
                <th class="text-right" style="width: 12%">Original Budget</th>
                <th class="text-right" style="width: 12%">Forecast Budget</th>
                <th class="text-right" style="width: 12%">Committed</th>
                <th class="text-right" style="width: 12%">Invoiced</th>
                <th class="text-right" style="width: 12%">Balance</th>
                <th class="text-center" style="width: 12%">Variance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($costCodeData as $data)
                @if($data['is_header'] ?? false)
                    <tr class="cost-type-header">
                        <td colspan="8">{{ $data['name'] ?? $data['cost_type'] ?? '' }}</td>
                    </tr>
                @elseif($data['is_group_total'] ?? false)
                    @php $subVariance = ($data['forecast_budget'] ?? $data['budget'] ?? 0) - ($data['committed'] ?? 0); @endphp
                    <tr class="cost-type-subtotal">
                        <td colspan="2"><em>{{ $data['name'] ?? 'Subtotal' }}</em></td>
                        <td class="text-right">${{ number_format($data['original_budget'] ?? $data['budget'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['forecast_budget'] ?? $data['budget'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['committed'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['invoiced'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['balance'] ?? 0, 2) }}</td>
                        <td class="text-center {{ $subVariance >= 0 ? 'forecast-variance-positive' : 'forecast-variance-negative' }}">${{ number_format($subVariance, 2) }}</td>
                    </tr>
                @else
                    @php $variance = ($data['forecast_budget'] ?? 0) - ($data['committed'] ?? 0); @endphp
                    <tr>
                        <td><strong>{{ $data['code'] }}</strong></td>
                        <td>{{ $data['name'] }}</td>
                        <td class="text-right">${{ number_format($data['original_budget'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['forecast_budget'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['committed'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($data['invoiced'] ?? 0, 2) }}</td>
                        <td class="text-right" style="color: {{ ($data['balance'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }}">${{ number_format($data['balance'] ?? 0, 2) }}</td>
                        <td class="text-center {{ $variance >= 0 ? 'forecast-variance-positive' : 'forecast-variance-negative' }}">${{ number_format($variance, 2) }}</td>
                    </tr>
                @endif
            @endforeach

            <tr class="grand-total">
                <td colspan="2">GRAND TOTAL</td>
                <td class="text-right">${{ number_format($totalOriginal, 2) }}</td>
                <td class="text-right">${{ number_format($totalForecast, 2) }}</td>
                <td class="text-right">${{ number_format($totalCommitted, 2) }}</td>
                <td class="text-right">${{ number_format($totalInvoiced, 2) }}</td>
                <td class="text-right">${{ number_format($totalBalance, 2) }}</td>
                <td class="text-center">${{ number_format($totalForecast - $totalCommitted, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Manhour Forecast Summary --}}
    @php
        $mhRows = is_array($manhourData) ? array_values($manhourData) : [];
        $mhTotalBudget   = collect($mhRows)->sum('budget_hours');
        $mhTotalActual   = collect($mhRows)->sum('actual_hours');
        $mhTotalForecast = collect($mhRows)->sum('forecast_hours');
        $mhTotalCost     = collect($mhRows)->sum('labor_cost');
        $mhVariance      = $mhTotalBudget - $mhTotalActual;
        $mhProductivity  = $mhTotalActual > 0 ? round(($mhTotalBudget / $mhTotalActual) * 100, 1) : 0;
    @endphp
    <div class="section-title">Manhour Forecast</div>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Budgeted Hours</div>
            <div class="value">{{ number_format($mhTotalBudget, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Actual Hours</div>
            <div class="value">{{ number_format($mhTotalActual, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Forecast Hours</div>
            <div class="value">{{ number_format($mhTotalForecast, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Productivity</div>
            <div class="value">{{ $mhProductivity }}%</div>
        </div>
        <div class="summary-item {{ $mhVariance >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Hours Variance</div>
            <div class="value">{{ number_format($mhVariance, 1) }} hrs</div>
        </div>
    </div>
@endsection
