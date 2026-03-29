<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Report')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }

        .page-header { padding: 20px 30px; border-bottom: 3px solid #2563eb; margin-bottom: 20px; }
        .page-header h1 { font-size: 22px; color: #1e3a5f; margin-bottom: 4px; }
        .page-header .subtitle { font-size: 12px; color: #64748b; }
        .page-header .company { font-size: 14px; font-weight: bold; color: #2563eb; }

        .meta-row { display: table; width: 100%; margin-bottom: 15px; padding: 0 30px; }
        .meta-cell { display: table-cell; vertical-align: top; }
        .meta-label { font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: bold; letter-spacing: 0.5px; }
        .meta-value { font-size: 12px; font-weight: bold; color: #1e293b; }

        .content { padding: 0 30px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th { background-color: #1e3a5f; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; text-align: left; }
        table td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        table tr:nth-child(even) td { background-color: #f8fafc; }
        table .text-right { text-align: right; }
        table .text-center { text-align: center; }

        .summary-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 12px 16px; margin-bottom: 15px; }
        .summary-box .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .summary-box .value { font-size: 16px; font-weight: bold; color: #1e3a5f; }

        .summary-grid { display: table; width: 100%; margin-bottom: 15px; }
        .summary-item { display: table-cell; text-align: center; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary-item .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .summary-item .value { font-size: 14px; font-weight: bold; color: #1e3a5f; }
        .summary-item.positive .value { color: #16a34a; }
        .summary-item.negative .value { color: #dc2626; }

        .totals-row td { font-weight: bold; background-color: #f0f9ff !important; border-top: 2px solid #2563eb; }

        .section-title { font-size: 13px; font-weight: bold; color: #1e3a5f; margin: 20px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 30px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        @yield('extra-styles')
    </style>
</head>
<body>
    <div class="page-header">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; vertical-align: middle;">
                <div class="company">{{ config('app.company_name', 'Construction Management System') }}</div>
                <h1>@yield('title', 'Report')</h1>
                <div class="subtitle">@yield('subtitle', 'Generated on ' . now()->format('F j, Y'))</div>
            </div>
            <div style="display: table-cell; vertical-align: middle; text-align: right;">
                @yield('header-right')
            </div>
        </div>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        {{ config('app.company_name', 'Construction Management System') }} &bull; Generated {{ now()->format('M j, Y g:i A') }} &bull; Page <span class="page-number"></span>
    </div>
</body>
</html>
