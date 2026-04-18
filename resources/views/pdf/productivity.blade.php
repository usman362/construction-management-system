@extends('pdf.layout')

@section('title', 'Productivity Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('content')
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Earned Hours</div>
            <div class="value">{{ number_format(collect($productivityData)->sum('earned_hours'), 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Actual Hours</div>
            <div class="value">{{ number_format(collect($productivityData)->sum('actual_hours'), 1) }}</div>
        </div>
        @php
            $totalEarned = collect($productivityData)->sum('earned_hours');
            $totalActual = collect($productivityData)->sum('actual_hours');
            $overallProd = $totalActual > 0 ? round(($totalEarned / $totalActual) * 100, 1) : 0;
        @endphp
        <div class="summary-item {{ $overallProd >= 100 ? 'positive' : 'negative' }}">
            <div class="label">Overall Productivity</div>
            <div class="value">{{ $overallProd }}%</div>
        </div>
        <div class="summary-item {{ collect($productivityData)->sum('variance') >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Total Variance</div>
            <div class="value">{{ number_format(collect($productivityData)->sum('variance'), 1) }} hrs</div>
        </div>
    </div>

    <div class="section-title">Productivity by Phase Code</div>
    <table>
        <thead>
            <tr>
                <th>Phase Code</th>
                <th class="text-right">Earned Hours</th>
                <th class="text-right">Actual Hours</th>
                <th class="text-center">Productivity %</th>
                <th class="text-right">Variance (hrs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productivityData as $data)
            <tr>
                <td><strong>{{ $data['cost_code'] }}</strong></td>
                <td class="text-right">{{ number_format($data['earned_hours'], 1) }}</td>
                <td class="text-right">{{ number_format($data['actual_hours'], 1) }}</td>
                <td class="text-center">
                    <span class="badge {{ $data['productivity'] >= 100 ? 'badge-green' : ($data['productivity'] >= 80 ? 'badge-yellow' : 'badge-red') }}">
                        {{ $data['productivity'] }}%
                    </span>
                </td>
                <td class="text-right" style="color: {{ $data['variance'] >= 0 ? '#16a34a' : '#dc2626' }}">{{ number_format($data['variance'], 1) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-right">{{ number_format($totalEarned, 1) }}</td>
                <td class="text-right">{{ number_format($totalActual, 1) }}</td>
                <td class="text-center"><strong>{{ $overallProd }}%</strong></td>
                <td class="text-right">{{ number_format(collect($productivityData)->sum('variance'), 1) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
