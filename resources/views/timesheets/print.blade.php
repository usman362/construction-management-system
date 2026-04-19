<!DOCTYPE html>
{{-- Timesheet print view — shared by single + batch.
     Intentionally vanilla HTML/CSS (no Tailwind) because DomPDF doesn't support
     utility classes and browser print needs a self-contained stylesheet.
     Each timesheet block is wrapped so `page-break-after: always` cleanly
     splits the batch into one-per-page output. --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $heading }} — {{ $companyName }}</title>
    <style>
        @page { size: Letter; margin: 0.5in; }
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .sheet {
            padding: 24px;
            max-width: 7.5in;
            margin: 0 auto;
        }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        /* Header with branding */
        .hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid {{ $primaryColor }};
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .hdr-left { display: flex; align-items: center; gap: 12px; }
        .hdr-logo {
            width: 48px; height: 48px;
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

        /* Info grid */
        h2.section {
            font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin: 14px 0 8px;
            color: {{ $primaryColor }};
        }
        table.info {
            width: 100%; border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.info td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 11px;
        }
        table.info td.lbl {
            width: 110px;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.info td.val {
            font-weight: 600;
        }

        /* Hours table */
        table.hours {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.hours th, table.hours td {
            border: 1px solid #bbb;
            padding: 6px 8px;
            text-align: center;
            font-size: 11px;
        }
        table.hours th {
            background: #f3f4f6;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.hours td.total {
            background: #eff6ff;
            font-weight: 700;
            color: {{ $primaryColor }};
        }

        /* Cost allocation breakdown */
        table.alloc {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.alloc th, table.alloc td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            font-size: 10px;
        }
        table.alloc th {
            background: #f9fafb;
            text-align: left;
            font-weight: 700;
        }
        table.alloc td.num { text-align: right; }

        /* Signature block */
        .sig-wrap {
            display: flex;
            gap: 20px;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .sig-box {
            flex: 1;
            border: 1px solid #999;
            border-radius: 4px;
            padding: 8px;
            min-height: 90px;
        }
        .sig-box .sig-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 4px;
        }
        .sig-box .sig-img {
            max-width: 100%;
            max-height: 60px;
            display: block;
        }
        .sig-box .sig-name {
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
            border-top: 1px dashed #bbb;
            padding-top: 4px;
        }
        .sig-box .sig-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 4px;
        }
        .sig-box .sig-hint {
            font-size: 9px;
            color: #999;
        }

        /* Notes block */
        .notes {
            border: 1px dashed #bbb;
            padding: 8px;
            margin-top: 10px;
            font-size: 11px;
            min-height: 40px;
            background: #fafafa;
        }
        .notes .notes-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 3px;
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
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            margin-left: 8px;
        }
        .toolbar .back { background: #4b5563; }
        .toolbar .pdf  { background: #059669; }
        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; }
        }

        /* Footer */
        .ft {
            margin-top: 18px;
            border-top: 1px solid #ddd;
            padding-top: 6px;
            font-size: 9px;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-draft     { background: #e5e7eb; color: #374151; }
        .badge-submitted { background: #fef3c7; color: #92400e; }
        .badge-approved  { background: #d1fae5; color: #065f46; }
        .badge-rejected  { background: #fee2e2; color: #991b1b; }

        .yes { color: #059669; font-weight: 700; }
        .no  { color: #9ca3af; }
    </style>
</head>
<body>

    @if($printMode !== 'pdf')
    <div class="toolbar">
        <div style="font-size: 13px; font-weight: 600;">
            {{ $heading }}
            @if(!$single)
                <span style="font-weight: 400; font-size: 11px; opacity: 0.75; margin-left: 8px;">
                    {{ $timesheets->count() }} timesheet{{ $timesheets->count() === 1 ? '' : 's' }}
                </span>
            @endif
        </div>
        <div>
            <a href="javascript:history.back()" class="back">← Back</a>
            <button onclick="window.print()">🖨 Print</button>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'pdf']) }}" class="pdf">⬇ PDF</a>
        </div>
    </div>
    @endif

    @foreach($timesheets as $t)
    <div class="page">
        <div class="sheet">
            {{-- Header: company branding + document title --}}
            <div class="hdr">
                <div class="hdr-left">
                    <div class="hdr-logo">
                        @if($companyLogo)
                            <img src="{{ public_path(ltrim($companyLogo, '/')) }}" alt="{{ $companyName }}">
                        @else
                            {{ strtoupper(substr($companyName, 0, 2)) }}
                        @endif
                    </div>
                    <div>
                        <div class="hdr-company">{{ $companyName }}</div>
                        <div class="hdr-tag">Daily Timesheet</div>
                    </div>
                </div>
                <div class="hdr-right">
                    <div class="doc-title">Timesheet #{{ $t->id }}</div>
                    <div>Date: <strong>{{ $t->date->format('D, M j, Y') }}</strong></div>
                    <div>Status:
                        <span class="badge badge-{{ $t->status }}">{{ $t->status }}</span>
                    </div>
                </div>
            </div>

            {{-- Project + Employee details --}}
            <h2 class="section">Project &amp; Employee</h2>
            <table class="info">
                <tr>
                    <td class="lbl">Project</td>
                    <td class="val">
                        {{ $t->project->project_number ?? '' }}
                        @if($t->project) — {{ $t->project->name }} @endif
                    </td>
                    <td class="lbl">Client</td>
                    <td class="val">{{ optional($t->project?->client)->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Employee</td>
                    <td class="val">
                        {{ $t->employee->employee_number ?? '' }} —
                        {{ $t->employee->first_name ?? '' }} {{ $t->employee->last_name ?? '' }}
                    </td>
                    <td class="lbl">Phase Code</td>
                    <td class="val">
                        @if($t->costCode)
                            {{ $t->costCode->code }} — {{ $t->costCode->name }}
                        @else — @endif
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Crew</td>
                    <td class="val">{{ $t->crew->name ?? '—' }}</td>
                    <td class="lbl">Shift</td>
                    <td class="val">{{ $t->shift->name ?? '—' }}</td>
                </tr>
            </table>

            {{-- Hours breakdown --}}
            <h2 class="section">Hours Worked</h2>
            <table class="hours">
                <thead>
                    <tr>
                        <th>Regular</th>
                        <th>Overtime</th>
                        <th>Double Time</th>
                        <th>Total</th>
                        @php
                            $pd = optional($t->costAllocations->first())->per_diem_amount ?? 0;
                        @endphp
                        <th>Per Diem ($)</th>
                        <th>Billable</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format((float)$t->regular_hours, 2) }}</td>
                        <td>{{ number_format((float)$t->overtime_hours, 2) }}</td>
                        <td>{{ number_format((float)$t->double_time_hours, 2) }}</td>
                        <td class="total">{{ number_format((float)$t->total_hours, 2) }}</td>
                        <td>{{ $pd > 0 ? '$'.number_format((float)$pd, 2) : '—' }}</td>
                        <td>
                            @if($t->is_billable || (float)($t->billable_amount ?? 0) > 0)
                                <span class="yes">Yes</span>
                            @else
                                <span class="no">No</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
            @if($t->force_overtime)
                <p style="font-size: 10px; color: #92400e; margin: 4px 0 0;">
                    ⚠ Force OT applied — all hours booked to overtime regardless of weekly total.
                </p>
            @endif

            {{-- Cost allocation breakdown (only if multiple or has per-diem) --}}
            @if($t->costAllocations->count() > 1 || ($t->costAllocations->count() === 1 && $pd > 0))
            <h2 class="section">Cost Allocation</h2>
            <table class="alloc">
                <thead>
                    <tr>
                        <th>Phase Code</th>
                        <th style="text-align:right;">Hours</th>
                        <th style="text-align:right;">Labor Cost</th>
                        <th style="text-align:right;">Per Diem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($t->costAllocations as $alloc)
                    <tr>
                        <td>
                            @if($alloc->costCode)
                                {{ $alloc->costCode->code }} — {{ $alloc->costCode->name }}
                            @else — @endif
                        </td>
                        <td class="num">{{ number_format((float)$alloc->hours, 2) }}</td>
                        <td class="num">${{ number_format((float)$alloc->cost, 2) }}</td>
                        <td class="num">
                            {{ ((float)($alloc->per_diem_amount ?? 0)) > 0
                                ? '$'.number_format((float)$alloc->per_diem_amount, 2)
                                : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            {{-- Notes --}}
            @if($t->notes)
            <div class="notes">
                <div class="notes-label">Notes</div>
                <div>{{ $t->notes }}</div>
            </div>
            @endif

            {{-- Signature block — always shown.
                 If signature captured electronically, display the image + name/date.
                 Otherwise render a blank line for manual paper signing. --}}
            <h2 class="section">Client Signature</h2>
            <div class="sig-wrap">
                <div class="sig-box">
                    <div class="sig-label">Client / Foreman Signature</div>
                    @if($t->client_signature)
                        {{-- Stored as data URL or file path — both work as img src --}}
                        <img src="{{ $t->client_signature }}" class="sig-img" alt="Signature">
                        <div class="sig-name">
                            {{ $t->client_signature_name ?: 'Signed' }}
                            @if($t->signed_at)
                                <span style="font-weight: 400; color: #666; margin-left: 6px;">
                                    on {{ $t->signed_at->format('M j, Y g:i A') }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="sig-line"></div>
                        <div class="sig-hint">Sign above — printed name / date:</div>
                        <div class="sig-line" style="height: 20px; margin-top: 6px;"></div>
                    @endif
                </div>
                <div class="sig-box">
                    <div class="sig-label">Approved By (Office)</div>
                    @if($t->approver)
                        <div class="sig-name">
                            {{ $t->approver->name }}
                            @if($t->approved_at)
                                <span style="font-weight: 400; color: #666; margin-left: 6px;">
                                    on {{ $t->approved_at->format('M j, Y g:i A') }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="sig-line"></div>
                        <div class="sig-hint">Signature / date</div>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="ft">
                <div>{{ $companyName }} • Timesheet #{{ $t->id }}</div>
                <div>Generated {{ $generatedAt->format('M j, Y g:i A') }}</div>
            </div>
        </div>
    </div>
    @endforeach

    @if($printMode !== 'pdf')
    <script>
        // Auto-open the print dialog on first load for single-timesheet view only.
        // Batch view skips this — user usually wants to review the list first.
        @if($single)
            window.addEventListener('load', function() {
                setTimeout(function() { window.print(); }, 400);
            });
        @endif
    </script>
    @endif
</body>
</html>
