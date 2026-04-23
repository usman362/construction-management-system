@extends('pdf.layout')

@php
    $project = $weekly['project'] ?? null;
    $client  = $project?->client;
    $weekEnding = $weekly['week_ending'];
    $days = $weekly['days'];
@endphp

@section('title', 'Weekly Timesheet')
@section('subtitle', $project ? ($project->project_number . ' — ' . $project->name) : 'Project not selected')

@section('header-right')
    <div class="meta-label">Week Ending</div>
    <div class="meta-value">{{ $weekEnding->format('m/d/Y') }}</div>
    @if($project?->po_number)
        <div class="meta-label" style="margin-top:6px;">Job / PO #</div>
        <div class="meta-value">{{ $project->po_number }}</div>
    @endif
@endsection

@section('extra-styles')
<style>
    .ws-info { margin-bottom: 12px; padding: 8px 10px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:4px; }
    .ws-info .row { display:table; width:100%; }
    .ws-info .cell { display:table-cell; font-size:10px; }
    .ws-info .cell strong { color:#1e3a5f; text-transform:uppercase; font-size:9px; letter-spacing:0.4px; }

    .shift-band td { background:#1e293b; color:#fff; font-weight:bold; text-transform:uppercase; letter-spacing:1px; padding:6px 8px; text-align:center; font-size:11px; }

    table.ws { width:100%; border-collapse:collapse; margin-bottom:10px; font-size:9px; }
    table.ws th, table.ws td { border:1px solid #cbd5e1; padding:3px 4px; }
    table.ws thead th { background:#dbeafe; color:#1e3a5f; font-size:9px; text-transform:none; letter-spacing:0; text-align:center; }
    table.ws thead .day-head { background:#e0e7ff; }
    table.ws thead .sub-head th { background:#eff6ff; font-size:8px; padding:2px 3px; }
    table.ws td.text-right { text-align:right; }
    table.ws td.text-center { text-align:center; }
    table.ws td.name { font-weight:bold; white-space:nowrap; }
    table.ws td.class { color:#475569; }
    table.ws tr.shift-totals td { background:#dbeafe; font-weight:bold; }
    table.ws td.rt { background:#f8fafc; font-weight:bold; }
    table.ws td.ot-val { color:#c2410c; }
    table.ws td.pd-val { color:#15803d; }
    table.ws td.empty { color:#cbd5e1; }

    .grand-band { background:#0f172a; color:#fff; padding:6px 10px; margin-bottom:15px; font-size:10px; font-weight:bold; letter-spacing:0.5px; }
    .grand-band .lbl { text-transform:uppercase; opacity:0.7; margin-right:4px; font-size:9px; }

    .sig-grid { display:table; width:100%; margin-top:20px; }
    .sig-cell { display:table-cell; width:50%; padding:0 15px; vertical-align:top; }
    .sig-line { border-bottom:2px solid #1e293b; height:35px; }
    .sig-label { font-size:9px; text-transform:uppercase; color:#64748b; font-weight:bold; letter-spacing:0.5px; margin-top:4px; }
    .sig-sub { display:table; width:100%; margin-top:10px; }
    .sig-sub-cell { display:table-cell; width:50%; padding:0 5px; }
    .sig-sub-line { border-bottom:1px solid #94a3b8; height:18px; }
    .sig-sub-label { font-size:8px; color:#94a3b8; text-transform:uppercase; margin-top:3px; }

    .empty-state { padding:30px; text-align:center; color:#64748b; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:6px; }
</style>
@endsection

@section('content')
    <div class="ws-info">
        <div class="row">
            <div class="cell"><strong>Client</strong><br>{{ $client->name ?? '—' }}</div>
            <div class="cell"><strong>Project</strong><br>{{ $project ? ($project->project_number . ' — ' . $project->name) : '—' }}</div>
            <div class="cell"><strong>Week</strong><br>{{ $weekly['week_start']->format('M j') }} – {{ $weekEnding->format('M j, Y') }}</div>
        </div>
    </div>

    @if(!$project || empty($weekly['shifts']))
        <div class="empty-state">
            {{ $project ? 'No timesheets recorded for this project during the selected week.' : 'No project selected. The Weekly Timesheet requires a project filter.' }}
        </div>
    @else
        @foreach($weekly['shifts'] as $shiftName => $shift)
            <table class="ws">
                <thead>
                    <tr class="shift-band">
                        <td colspan="{{ 2 + (count($days) * 3) + 3 }}">{{ strtoupper($shiftName) }}</td>
                    </tr>
                    <tr class="day-head">
                        <th rowspan="2" style="width:17%; text-align:left;">Employee Name</th>
                        <th rowspan="2" style="width:11%; text-align:left;">Classification</th>
                        @foreach($days as $d)
                            <th colspan="3">{{ $d->format('D') }}<br><span style="font-weight:normal; font-size:8px; color:#64748b;">{{ $d->format('m/d') }}</span></th>
                        @endforeach
                        <th rowspan="2" style="background:#bfdbfe;">ST<br>Total</th>
                        <th rowspan="2" style="background:#bfdbfe;">OT<br>Total</th>
                        <th rowspan="2" style="background:#bfdbfe;">Per<br>Diem</th>
                    </tr>
                    <tr class="sub-head">
                        @foreach($days as $d)
                            <th>ST</th>
                            <th>OT</th>
                            <th>P/D</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($shift['employees'] as $emp)
                        <tr>
                            <td class="name">{{ $emp['name'] }}</td>
                            <td class="class">{{ $emp['classification'] ?: '—' }}</td>
                            @foreach($days as $d)
                                @php $cell = $emp['days'][$d->format('Y-m-d')] ?? ['st'=>0,'ot'=>0,'pd'=>0]; @endphp
                                <td class="text-right {{ $cell['st'] > 0 ? '' : 'empty' }}">{{ $cell['st'] > 0 ? number_format($cell['st'], 1) : '—' }}</td>
                                <td class="text-right {{ $cell['ot'] > 0 ? 'ot-val' : 'empty' }}">{{ $cell['ot'] > 0 ? number_format($cell['ot'], 1) : '—' }}</td>
                                <td class="text-right {{ $cell['pd'] > 0 ? 'pd-val' : 'empty' }}">{{ $cell['pd'] > 0 ? '$' . number_format($cell['pd'], 0) : '—' }}</td>
                            @endforeach
                            <td class="text-right rt">{{ number_format($emp['st_total'], 1) }}</td>
                            <td class="text-right rt">{{ number_format($emp['ot_total'], 1) }}</td>
                            <td class="text-right rt">${{ number_format($emp['pd_total'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="shift-totals">
                        <td colspan="2">{{ strtoupper($shiftName) }} TOTALS</td>
                        @foreach($days as $d)
                            @php $dt = $shift['day_totals'][$d->format('Y-m-d')] ?? ['st'=>0,'ot'=>0,'pd'=>0]; @endphp
                            <td class="text-right">{{ number_format($dt['st'], 1) }}</td>
                            <td class="text-right">{{ number_format($dt['ot'], 1) }}</td>
                            <td class="text-right">{{ $dt['pd'] > 0 ? '$' . number_format($dt['pd'], 0) : '—' }}</td>
                        @endforeach
                        <td class="text-right">{{ number_format($shift['shift_st'], 1) }}</td>
                        <td class="text-right">{{ number_format($shift['shift_ot'], 1) }}</td>
                        <td class="text-right">${{ number_format($shift['shift_pd'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        @if(count($weekly['shifts']) > 1)
            <div class="grand-band">
                <span class="lbl">Grand Totals:</span>
                <span class="lbl">ST:</span> {{ number_format($weekly['grand_st'], 1) }} hrs &nbsp;&bull;&nbsp;
                <span class="lbl">OT:</span> {{ number_format($weekly['grand_ot'], 1) }} hrs &nbsp;&bull;&nbsp;
                <span class="lbl">Per Diem:</span> ${{ number_format($weekly['grand_pd'], 2) }} &nbsp;&bull;&nbsp;
                <span class="lbl">Labor Cost:</span> ${{ number_format($weekly['grand_cost'], 2) }}
            </div>
        @endif

        <div class="sig-grid">
            <div class="sig-cell">
                <div class="sig-line"></div>
                <div class="sig-label">Company Representative Signature</div>
                <div class="sig-sub">
                    <div class="sig-sub-cell">
                        <div class="sig-sub-line"></div>
                        <div class="sig-sub-label">Printed Name</div>
                    </div>
                    <div class="sig-sub-cell">
                        <div class="sig-sub-line"></div>
                        <div class="sig-sub-label">Date</div>
                    </div>
                </div>
            </div>
            <div class="sig-cell">
                <div class="sig-line"></div>
                <div class="sig-label">Client Representative Signature</div>
                <div class="sig-sub">
                    <div class="sig-sub-cell">
                        <div class="sig-sub-line"></div>
                        <div class="sig-sub-label">Printed Name</div>
                    </div>
                    <div class="sig-sub-cell">
                        <div class="sig-sub-line"></div>
                        <div class="sig-sub-label">Date</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
