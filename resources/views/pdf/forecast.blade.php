@extends('pdf.layout')

@section('title', 'Budget Forecast Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('content')
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Original Budget</div>
            <div class="value">${{ number_format($originalBudgetTotal, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Forecast Budget</div>
            <div class="value">${{ number_format($forecastBudgetTotal, 2) }}</div>
        </div>
        <div class="summary-item {{ ($forecastBudgetTotal - $originalBudgetTotal) >= 0 ? '' : 'negative' }}">
            <div class="label">Variance</div>
            <div class="value">${{ number_format($forecastBudgetTotal - $originalBudgetTotal, 2) }}</div>
        </div>
    </div>

    <div class="section-title">Forecast by Cost Code</div>
    <table>
        <thead>
            <tr>
                <th>Cost Code</th>
                <th>Description</th>
                <th class="text-right">Original Budget</th>
                <th class="text-right">Forecast Budget</th>
                <th class="text-right">Committed</th>
                <th class="text-right">Invoiced</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($costCodeData as $data)
            <tr>
                <td><strong>{{ $data['code'] }}</strong></td>
                <td>{{ $data['name'] }}</td>
                <td class="text-right">${{ number_format($data['original_budget'], 2) }}</td>
                <td class="text-right">${{ number_format($data['forecast_budget'], 2) }}</td>
                <td class="text-right">${{ number_format($data['committed'], 2) }}</td>
                <td class="text-right">${{ number_format($data['invoiced'], 2) }}</td>
                <td class="text-right" style="color: {{ $data['balance'] >= 0 ? '#16a34a' : '#dc2626' }}">${{ number_format($data['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('original_budget'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('forecast_budget'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('committed'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('invoiced'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('balance'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Manhour Forecast</div>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Earned Hours</div>
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
            <div class="label">Variance</div>
            <div class="value">{{ number_format($manhourData['variance'], 1) }} hrs</div>
        </div>
    </div>
@endsection
