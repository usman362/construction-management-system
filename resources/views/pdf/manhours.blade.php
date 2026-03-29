@extends('pdf.layout')

@section('title', 'Manhour Report')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('content')
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Regular Hours</div>
            <div class="value">{{ number_format(collect($manhourData)->sum('regular_hours'), 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total OT Hours</div>
            <div class="value">{{ number_format(collect($manhourData)->sum('ot_hours'), 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total DT Hours</div>
            <div class="value">{{ number_format(collect($manhourData)->sum('dt_hours'), 1) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Labor Cost</div>
            <div class="value">${{ number_format(collect($manhourData)->sum('labor_cost'), 2) }}</div>
        </div>
    </div>

    <div class="section-title">Employee Manhour Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Craft</th>
                <th class="text-right">Regular Hrs</th>
                <th class="text-right">OT Hrs</th>
                <th class="text-right">DT Hrs</th>
                <th class="text-right">Total Hrs</th>
                <th class="text-right">Labor Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($manhourData as $data)
            <tr>
                <td><strong>{{ $data['employee_name'] }}</strong></td>
                <td>{{ $data['craft'] }}</td>
                <td class="text-right">{{ number_format($data['regular_hours'], 1) }}</td>
                <td class="text-right">{{ number_format($data['ot_hours'], 1) }}</td>
                <td class="text-right">{{ number_format($data['dt_hours'], 1) }}</td>
                <td class="text-right"><strong>{{ number_format($data['total_hours'], 1) }}</strong></td>
                <td class="text-right">${{ number_format($data['labor_cost'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="text-right">{{ number_format(collect($manhourData)->sum('regular_hours'), 1) }}</td>
                <td class="text-right">{{ number_format(collect($manhourData)->sum('ot_hours'), 1) }}</td>
                <td class="text-right">{{ number_format(collect($manhourData)->sum('dt_hours'), 1) }}</td>
                <td class="text-right">{{ number_format(collect($manhourData)->sum('total_hours'), 1) }}</td>
                <td class="text-right">${{ number_format(collect($manhourData)->sum('labor_cost'), 2) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
