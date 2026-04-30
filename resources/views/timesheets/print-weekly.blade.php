{{--
    Weekly Timesheet Summary print — one printable LANDSCAPE page per
    (employee, Mon–Sun week) pair. Brenda asked for this on 2026-04-28
    so the office can hand each worker (or attach to billing) a single
    sheet showing their full week instead of one page per daily entry.

    Mon=0 … Sun=6 indexing matches the controller's
    `groupTimesheetsByEmployeeWeek()` helper. Each day cell lists the
    project + ST/OT/PR for that day; the right-most column totals the
    week. Footer holds the signature block (manager + employee).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $heading }} — {{ $companyName }}</title>
    <style>
        @page { size: Letter landscape; margin: 0.4in; }
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .sheet {
            padding: 18px 24px;
            max-width: 10.2in;
            margin: 0 auto;
        }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        /* Header */
        .hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .hdr-left { display: flex; align-items: center; gap: 12px; }
        .hdr-logo {
            width: 52px; height: 52px;
            border-radius: 6px;
            background: {{ $primaryColor }};
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 18px;
        }
        .hdr-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 6px; }
        .hdr-company { font-size: 16px; font-weight: 700; }
        .hdr-tag { font-size: 10px; color: #666; }
        .hdr-right { text-align: right; font-size: 10px; color: #555; }
        .hdr-right .doc-title { font-size: 14px; font-weight: 700; color: #111; }

        /* Employee + week strip */
        .emp-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f3f4f6;
            border-left: 4px solid {{ $primaryColor }};
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .emp-name { font-size: 14px; font-weight: 700; }
        .emp-meta { font-size: 10px; color: #555; }
        .week-range { font-size: 12px; font-weight: 600; color: #111; }

        /* Week grid */
        table.week {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.week th, table.week td {
            border: 1px solid #bbb;
            padding: 4px 5px;
            font-size: 10px;
            vertical-align: top;
        }
        table.week th {
            background: {{ $primaryColor }};
            color: #fff;
            font-weight: 700;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.week th.row-label, table.week td.row-label {
            background: #f9fafb;
            text-align: left;
            font-weight: 600;
            color: #111;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.4px;
            width: 90px;
        }
        table.week td.day {
            min-height: 60px;
            height: 60px;
        }
        table.week .day-date {
            display: block;
            font-size: 9px;
            color: #777;
            margin-bottom: 2px;
        }
        table.week .day-entries {
            font-size: 9px;
            line-height: 1.3;
        }
        table.week .day-entries .entry {
            border-bottom: 1px dotted #ddd;
            padding: 2px 0;
        }
        table.week .day-entries .entry:last-child { border-bottom: none; }
        table.week .day-entries .proj { font-weight: 600; }
        table.week .day-entries .hrs { color: #444; }
        table.week .day-empty {
            color: #bbb;
            font-style: italic;
            text-align: center;
            font-size: 9px;
        }
        table.week td.hrs-num {
            text-align: center;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        table.week td.col-total {
            background: #eff6ff;
            font-weight: 700;
            color: {{ $primaryColor }};
            text-align: center;
        }
        table.week tr.totals-row td {
            background: #f3f4f6;
            font-weight: 700;
            font-size: 11px;
        }

        /* Project breakdown (under the week grid) */
        h3.section {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.4px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin: 12px 0 6px;
            color: {{ $primaryColor }};
        }
        table.proj-totals {
            width: 100%;
            border-collapse: collapse;
        }
        table.proj-totals th, table.proj-totals td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            font-size: 10px;
        }
        table.proj-totals th {
            background: #f9fafb;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.proj-totals td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.proj-totals tfoot td { background: #eff6ff; font-weight: 700; color: {{ $primaryColor }}; }

        /* Signature block */
        .sig-wrap {
            display: flex;
            gap: 16px;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-box {
            flex: 1;
            border: 1px solid #999;
            border-radius: 4px;
            padding: 8px;
            min-height: 70px;
        }
        .sig-box .sig-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 4px;
        }
        .sig-box .sig-line {
            border-bottom: 1px solid #333;
            height: 36px;
            margin-bottom: 3px;
        }
        .sig-box .sig-hint {
            font-size: 9px;
            color: #999;
        }

        .footer-meta {
            margin-top: 8px;
            font-size: 9px;
            color: #888;
            text-align: right;
            border-top: 1px solid #eee;
            padding-top: 4px;
        }

        /* Print toolbar — hidden when actually printing */
        .toolbar {
            position: sticky;
            top: 0;
            background: #1f2937;
            color: #fff;
            padding: 10px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
        .toolbar button, .toolbar a {
            background: {{ $primaryColor }};
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            margin-left: 8px;
        }
        @media print {
            .toolbar { display: none; }
            .sheet { padding: 0; }
        }
    </style>
</head>
<body>

@if ($printMode === 'html')
    <div class="toolbar">
        <div>
            <strong>{{ $heading }}</strong>
            <span style="opacity:0.7; font-size: 11px; margin-left: 12px;">
                {{ count($weeks) }} page(s)
            </span>
        </div>
        <div>
            <button type="button" onclick="window.print()">🖨️ Print</button>
            <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->all(), ['mode' => 'pdf'])) }}">⬇️ Download PDF</a>
            <a href="{{ route('timesheets.index') }}" style="background:#374151;">← Back</a>
        </div>
    </div>
@endif

@php
    $dayLabels = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $dayShort  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
@endphp

@foreach ($weeks as $bucket)
    @php
        $emp        = $bucket['employee'];
        $weekStart  = $bucket['week_start'];
        $weekEnd    = $bucket['week_end'];
        $days       = $bucket['days'];
        $totals     = $bucket['totals'];

        // Project-level breakdown for the week (sum hours + per diem by project)
        $projTotals = [];
        for ($i = 0; $i < 7; $i++) {
            foreach ($days[$i] as $ts) {
                $pid = $ts->project_id;
                if (! isset($projTotals[$pid])) {
                    $projTotals[$pid] = [
                        'project'      => $ts->project,
                        'regular'      => 0,
                        'overtime'     => 0,
                        'double_time'  => 0,
                        'total'        => 0,
                        'cost'         => 0,
                        'billable'     => 0,
                        // 2026-04-30 (Brenda): per diem column requested for the
                        // weekly billing print. Pulled from cost-allocation rows.
                        'per_diem'     => 0,
                    ];
                }
                $projTotals[$pid]['regular']     += (float) $ts->regular_hours;
                $projTotals[$pid]['overtime']    += (float) $ts->overtime_hours;
                $projTotals[$pid]['double_time'] += (float) $ts->double_time_hours;
                $projTotals[$pid]['total']       += (float) $ts->total_hours;
                $projTotals[$pid]['cost']        += (float) $ts->total_cost;
                $projTotals[$pid]['billable']    += (float) $ts->billable_amount;
                $projTotals[$pid]['per_diem']    += (float) $ts->costAllocations->sum('per_diem_amount');
            }
        }
    @endphp

    <div class="page sheet">
        <div class="hdr">
            <div class="hdr-left">
                {{-- 2026-04-30 (Brenda, 2nd round): logo was STILL not
                     rendering on production. Replaced URL resolution with
                     server-side base64 inlining via App\Support\BrandLogo.
                     Now $companyLogo arrives as a fully-formed data: URI
                     (or null), so the blade just emits it directly. No
                     remote fetch, no symlink dependency, no APP_URL gotchas.
                     Falls back to typographic initials when no logo set. --}}
                <div class="hdr-logo">
                    @if (! empty($companyLogo))
                        <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                    @else
                        {{ strtoupper(substr($companyName, 0, 2)) }}
                    @endif
                </div>
                <div>
                    <div class="hdr-company">{{ $companyName }}</div>
                    <div class="hdr-tag">Weekly Timesheet Summary</div>
                </div>
            </div>
            <div class="hdr-right">
                <div class="doc-title">Week Ending {{ $weekEnd->format('M j, Y') }}</div>
                <div>Generated {{ $generatedAt->format('M j, Y g:i A') }}</div>
            </div>
        </div>

        <div class="emp-strip">
            <div>
                <div class="emp-name">
                    {{ $emp ? ($emp->last_name . ', ' . $emp->first_name) : '— Unknown Employee —' }}
                </div>
                <div class="emp-meta">
                    Employee #: <strong>{{ $emp->employee_number ?? '—' }}</strong>
                    @if ($emp && $emp->craft)
                        &nbsp;·&nbsp; Craft: <strong>{{ $emp->craft->name }}</strong>
                    @endif
                </div>
            </div>
            <div class="week-range">
                {{ $weekStart->format('M j') }} – {{ $weekEnd->format('M j, Y') }}
            </div>
        </div>

        {{-- 2026-04-29 (Brenda): "Just the daily hours on each day and one
             weekly total." Simplified from the prior 5-row layout (Detail +
             ST/OT/PR + Day Total) down to one detail row + one bold totals
             row. ST/OT/PR breakdown is preserved in the per-entry detail cell
             text but no longer gets its own row. Project breakdown table at
             the bottom still gives the office the per-project ST/OT/PR split
             they need for billing. --}}
        <table class="week">
            <thead>
                <tr>
                    <th class="row-label">&nbsp;</th>
                    @foreach ($dayLabels as $i => $lbl)
                        <th>
                            {{ $dayShort[$i] }}<br>
                            <span style="font-size:9px; opacity:0.85;">{{ $weekStart->addDays($i)->format('M j') }}</span>
                        </th>
                    @endforeach
                    <th style="background:#1f2937;">Week<br>Total</th>
                </tr>
            </thead>
            <tbody>
                {{-- Per-day project + hours detail (one line per timesheet entry).
                     ST/OT/PR shown inline so the breakdown is still visible
                     when needed but doesn't take its own row. --}}
                <tr>
                    <td class="row-label">Detail</td>
                    @for ($i = 0; $i < 7; $i++)
                        <td class="day">
                            <span class="day-date">{{ $weekStart->addDays($i)->format('m/d') }}</span>
                            @if ($days[$i]->isEmpty())
                                <div class="day-empty">—</div>
                            @else
                                <div class="day-entries">
                                    @foreach ($days[$i] as $ts)
                                        <div class="entry">
                                            <div class="proj">
                                                {{ optional($ts->project)->project_number }}
                                                @if (optional($ts->project)->name)
                                                    — {{ \Illuminate\Support\Str::limit($ts->project->name, 22) }}
                                                @endif
                                            </div>
                                            <div class="hrs">
                                                {{ number_format($ts->total_hours, 2) }} hrs
                                                @if ($ts->overtime_hours > 0) <span style="color:#b45309;">(OT {{ number_format($ts->overtime_hours, 2) }})</span> @endif
                                                @if ($ts->double_time_hours > 0) <span style="color:#b91c1c;">(PR {{ number_format($ts->double_time_hours, 2) }})</span> @endif
                                                @if (($ts->earnings_category ?? 'HE') !== 'HE')
                                                    <strong>· {{ $ts->earnings_category }}</strong>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    @endfor
                    <td class="col-total"></td>
                </tr>

                {{-- Daily hours total per day + the single weekly total.
                     This is the headline row Brenda asked for — one number
                     per day, one weekly grand total in the right column. --}}
                <tr class="totals-row">
                    <td class="row-label">Hours</td>
                    @for ($i = 0; $i < 7; $i++)
                        @php $dt = $days[$i]->sum(fn($t) => (float) $t->total_hours); @endphp
                        <td class="hrs-num" style="font-size:14px;">{{ $dt > 0 ? number_format($dt, 2) : '—' }}</td>
                    @endfor
                    <td class="col-total" style="background:#1f2937; color:#fff; font-size:14px;">
                        {{ number_format($totals['total'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        @if (count($projTotals) > 0)
            {{-- 2026-04-30 (Brenda's manager, evening update):
                 "remove the billable cost from the weekly printed timesheets"
                 — the printout now only shows hours + per diem. Internal cost
                 and revenue stay in the system but never appear on the sheet
                 the employee or client sees. --}}
            <h3 class="section">Project Breakdown</h3>
            <table class="proj-totals">
                <thead>
                    <tr>
                        <th>Project #</th>
                        <th>Project Name</th>
                        <th class="num">ST</th>
                        <th class="num">OT</th>
                        <th class="num">PR</th>
                        <th class="num">Total Hours</th>
                        <th class="num">Per Diem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projTotals as $row)
                        <tr>
                            <td>{{ optional($row['project'])->project_number ?? '—' }}</td>
                            <td>{{ optional($row['project'])->name ?? '—' }}</td>
                            <td class="num">{{ number_format($row['regular'], 2) }}</td>
                            <td class="num">{{ number_format($row['overtime'], 2) }}</td>
                            <td class="num">{{ number_format($row['double_time'], 2) }}</td>
                            <td class="num">{{ number_format($row['total'], 2) }}</td>
                            <td class="num">{{ $row['per_diem'] > 0 ? '$' . number_format($row['per_diem'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Week Totals</td>
                        <td class="num">{{ number_format($totals['regular'], 2) }}</td>
                        <td class="num">{{ number_format($totals['overtime'], 2) }}</td>
                        <td class="num">{{ number_format($totals['double_time'], 2) }}</td>
                        <td class="num">{{ number_format($totals['total'], 2) }}</td>
                        <td class="num">{{ $totals['per_diem'] > 0 ? '$' . number_format($totals['per_diem'], 2) : '—' }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <div class="sig-wrap">
            <div class="sig-box">
                <div class="sig-label">Employee Signature</div>
                <div class="sig-line"></div>
                <div class="sig-hint">{{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : '' }} — sign &amp; date</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Foreman / Site Manager</div>
                <div class="sig-line"></div>
                <div class="sig-hint">Print name, sign &amp; date</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Office Approval</div>
                <div class="sig-line"></div>
                <div class="sig-hint">Print name, sign &amp; date</div>
            </div>
        </div>

        <div class="footer-meta">
            {{ $companyName }} · Weekly Timesheet Summary · Page generated {{ $generatedAt->format('M j, Y g:i A') }}
        </div>
    </div>
@endforeach

@if ($printMode === 'html')
<script>
    // Auto-pop the print dialog so this opens "ready to print" the way
    // the daily print does. Wrapped in setTimeout so the toolbar paints first.
    window.addEventListener('load', () => setTimeout(() => window.print(), 350));
</script>
@endif

</body>
</html>
