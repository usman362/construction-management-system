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
</style>
@endsection

@section('content')
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
            <div class="value">${{ number_format(collect($costCodeData)->sum('committed'), 2) }}</div>
        </div>
        <div class="summary-item {{ ($forecastBudgetTotal - collect($costCodeData)->sum('committed')) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Projected Balance</div>
            <div class="value">${{ number_format($forecastBudgetTotal - collect($costCodeData)->sum('committed'), 2) }}</div>
        </div>
    </div>

    {{-- Forecast by Cost Code --}}
    <div class="section-title">Forecast by Cost Code</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Cost Code</th>
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
            @php
                $totalOriginal = 0;
                $totalForecast = 0;
                $totalCommitted = 0;
                $totalInvoiced = 0;
                $totalBalance = 0;
            @endphp

            @foreach($costCodeData as $data)
            @php
                $variance = $data['forecast_budget'] - $data['committed'];
                $totalOriginal += $data['original_budget'];
                $totalForecast += $data['forecast_budget'];
                $totalCommitted += $data['committed'];
                $totalInvoiced += $data['invoiced'];
                $totalBalance += $data['balance'];
            @endphp
            <tr>
                <td><strong>{{ $data['code'] }}</strong></td>
                <td>{{ $data['name'] }}</td>
                <td class="text-right">${{ number_format($data['original_budget'], 2) }}</td>
                <td class="text-right">${{ number_format($data['forecast_budget'], 2) }}</td>
                <td class="text-right">${{ number_format($data['committed'], 2) }}</td>
                <td class="text-right">${{ number_format($data['invoiced'], 2) }}</td>
                <td class="text-right" style="color: {{ $data['balance'] >= 0 ? '#16a34a' : '#dc2626' }}">${{ number_format($data['balance'], 2) }}</td>
                <td class="text-center {{ $variance >= 0 ? 'forecast-variance-positive' : 'forecast-variance-negative' }}">${{ number_format($variance, 2) }}</td>
            </tr>
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

    {{-- Manhour Forecast --}}
    <div class="section-title">Manhour Forecast</div>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Budgeted Hours</div>
            <div class="value">{{ number_format($manhourData['earned_hours'], 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Actual Hours</div>
            <div class="value">{{ number_format($manhourData['actual_hours'], 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Productivity</div>
            <div class="value">{{ $manhourData['productivity'] }}%</div>
        </div>
        <div class="summary-item {{ $manhourData['variance'] >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Hours Variance</div>
            <div class="value">{{ number_format($manhourData['variance'], 1) }} hrs</div>
        </div>
    </div>
@endsection
