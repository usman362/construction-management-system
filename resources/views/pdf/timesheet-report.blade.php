@extends('pdf.layout')

@section('title', 'Timesheet Report')
@section('subtitle', 'Grouped by ' . ucfirst($groupBy))

@section('content')
    @php
        $grandTotalHours = $groupedData->sum('total_hours');
        $grandTotalCost = $groupedData->sum('total_cost');
    @endphp

    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Groups</div>
            <div class="value">{{ $groupedData->count() }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Hours</div>
            <div class="value">{{ number_format($grandTotalHours, 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Cost</div>
            <div class="value">${{ number_format($grandTotalCost, 2) }}</div>
        </div>
    </div>

    @foreach($groupedData as $group)
    <div class="section-title">{{ $group['name'] }} ({{ $group['type'] }})</div>
    <div style="margin-bottom: 5px; font-size: 10px; color: #64748b;">
        Total: {{ number_format($group['total_hours'], 1) }} hours | ${{ number_format($group['total_cost'], 2) }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>{{ $groupBy === 'employee' ? 'Project' : 'Employee' }}</th>
                <th class="text-right">Hours</th>
                <th class="text-right">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group['entries'] as $entry)
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('M j, Y') }}</td>
                <td>{{ $entry[$groupBy === 'employee' ? 'project' : 'employee'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($entry['hours'], 1) }}</td>
                <td class="text-right">${{ number_format($entry['cost'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>Subtotal</strong></td>
                <td class="text-right">{{ number_format($group['total_hours'], 1) }}</td>
                <td class="text-right">${{ number_format($group['total_cost'], 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endforeach

    <div style="margin-top: 15px; padding: 10px; background: #1e3a5f; color: #fff; text-align: right; font-size: 12px;">
        <strong>GRAND TOTAL: {{ number_format($grandTotalHours, 1) }} hours | ${{ number_format($grandTotalCost, 2) }}</strong>
    </div>
@endsection
