{{-- 2026-04-29 (Brenda): Friendly "no rows match" page for the
     Print for Billing flow. Replaces the bare 404 the clerk used to
     hit when she'd pick a date range / project that had no timesheets
     yet. Tells her exactly what she filtered for and gives a one-click
     way back to retry. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>No timesheets match — {{ $companyName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            margin: 0;
            background: #f3f4f6;
            color: #111;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            max-width: 560px;
            width: 100%;
            padding: 32px;
            text-align: center;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 22px;
            margin: 0 0 8px;
            color: #111;
        }
        p {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 16px;
        }
        .filters {
            text-align: left;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 13px;
        }
        .filters .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #e5e7eb;
        }
        .filters .row:last-child { border-bottom: none; }
        .filters .lbl {
            color: #6b7280;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.4px;
        }
        .filters .val {
            color: #111;
            font-weight: 600;
            font-size: 13px;
        }
        .filters .val.empty {
            color: #9ca3af;
            font-weight: 400;
            font-style: italic;
        }
        .btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        a.btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.15s;
        }
        a.btn-primary {
            background: {{ $primaryColor }};
            color: #fff;
        }
        a.btn-primary:hover { opacity: 0.9; }
        a.btn-secondary {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        a.btn-secondary:hover { background: #f9fafb; }
        .hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📄</div>
        <h1>No timesheets match those filters</h1>
        <p>The filters you picked didn't return any timesheet records to print. Try widening the date range or removing a filter.</p>

        <div class="filters">
            <div class="row">
                <span class="lbl">Date from</span>
                <span class="val {{ empty($filters['date_from']) ? 'empty' : '' }}">
                    {{ $filters['date_from'] ?? 'any' }}
                </span>
            </div>
            <div class="row">
                <span class="lbl">Date to</span>
                <span class="val {{ empty($filters['date_to']) ? 'empty' : '' }}">
                    {{ $filters['date_to'] ?? 'any' }}
                </span>
            </div>
            @if (!empty($projectName))
                <div class="row">
                    <span class="lbl">Project</span>
                    <span class="val">{{ $projectName }}</span>
                </div>
            @endif
            @if (!empty($crewName))
                <div class="row">
                    <span class="lbl">Crew</span>
                    <span class="val">{{ $crewName }}</span>
                </div>
            @endif
            @if (!empty($employeeName))
                <div class="row">
                    <span class="lbl">Employee</span>
                    <span class="val">{{ $employeeName }}</span>
                </div>
            @endif
            @if (!empty($filters['status']))
                <div class="row">
                    <span class="lbl">Status</span>
                    <span class="val">{{ $filters['status'] }}</span>
                </div>
            @endif
            @if (!empty($filters['layout']))
                <div class="row">
                    <span class="lbl">Layout</span>
                    <span class="val">{{ $filters['layout'] === 'weekly' ? 'Weekly Summary' : 'Per Timesheet (Daily)' }}</span>
                </div>
            @endif
        </div>

        <div class="btns">
            <a class="btn btn-primary" href="{{ route('timesheets.index') }}">← Back to Timesheets</a>
            <a class="btn btn-secondary" href="javascript:history.back()">Adjust filters</a>
        </div>

        <p class="hint">Tip: status defaults to "any" — if you set it to "Approved", entries still in Submitted or Draft will be skipped.</p>
    </div>
</body>
</html>
