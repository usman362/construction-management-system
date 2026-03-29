@extends('pdf.layout')

@section('title', 'Cost Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('header-right')
    <div class="meta-label">Report Date</div>
    <div class="meta-value">{{ now()->format('M j, Y') }}</div>
@endsection

@section('content')
    {{-- Summary Grid --}}
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Budget</div>
            <div class="value">${{ number_format(collect($costCodeData)->sum('budget'), 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Committed</div>
            <div class="value">${{ number_format(collect($costCodeData)->sum('committed'), 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Invoiced</div>
            <div class="value">${{ number_format(collect($costCodeData)->sum('invoiced'), 2) }}</div>
        </div>
        <div class="summary-item {{ collect($costCodeData)->sum('balance') >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Remaining Balance</div>
            <div class="value">${{ number_format(collect($costCodeData)->sum('balance'), 2) }}</div>
        </div>
    </div>

    {{-- Cost Breakdown Table --}}
    <div class="section-title">Cost Breakdown by Code</div>
    <table>
        <thead>
            <tr>
                <th>Cost Code</th>
                <th>Description</th>
                <th class="text-right">Budget</th>
                <th class="text-right">Committed</th>
                <th class="text-right">Invoiced</th>
                <th class="text-right">Balance</th>
                <th class="text-center">% Complete</th>
            </tr>
        </thead>
        <tbody>
            @foreach($costCodeData as $data)
            <tr>
                <td><strong>{{ $data['code'] }}</strong></td>
                <td>{{ $data['name'] }}</td>
                <td class="text-right">${{ number_format($data['budget'], 2) }}</td>
                <td class="text-right">${{ number_format($data['committed'], 2) }}</td>
                <td class="text-right">${{ number_format($data['invoiced'], 2) }}</td>
                <td class="text-right" style="color: {{ $data['balance'] >= 0 ? '#16a34a' : '#dc2626' }}">${{ number_format($data['balance'], 2) }}</td>
                <td class="text-center">{{ $data['percentage_complete'] }}%</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('budget'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('committed'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('invoiced'), 2) }}</td>
                <td class="text-right">${{ number_format(collect($costCodeData)->sum('balance'), 2) }}</td>
                <td class="text-center">—</td>
            </tr>
        </tbody>
    </table>

    {{-- Approved Change Orders --}}
    @if(count($changeOrders) > 0)
    <div class="section-title">Approved Change Orders</div>
    <table>
        <thead>
            <tr>
                <th>CO #</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
                <th class="text-center">Status</th>
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
                <td class="text-right">${{ number_format($changeOrders->sum('amount'), 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Manhour Summary --}}
    <div class="section-title">Manhour Summary</div>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Budget Hours</div>
            <div class="value">{{ number_format($manhourData['budget_hours'], 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Hours</div>
            <div class="value">{{ number_format($manhourData['total_hours'], 1) }}</div>
        </div>
        <div class="summary-item {{ $manhourData['hours_variance'] >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Variance</div>
            <div class="value">{{ number_format($manhourData['hours_variance'], 1) }} hrs</div>
        </div>
    </div>
@endsection
