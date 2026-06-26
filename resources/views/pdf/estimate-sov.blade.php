{{-- Internal SOV summary — matches Brenda's LAMELA Excel layout.
     Cost Type rows × EST / COST / GP / GPM / Markup columns. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SOV — {{ $estimate->estimate_number ?? '#'.$estimate->id }}</title>
<style>
    @page { margin: 0.5in; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #1f2937; }
    h1, h2 { margin: 0; }

    .header { padding: 12px 14px; background: #cfe2f3; border-bottom: 2px solid #1e3a8a; display: table; width: 100%; box-sizing: border-box; }
    .header .left  { display: table-cell; vertical-align: middle; }
    .header .right { display: table-cell; vertical-align: middle; text-align: right; }
    .project-name { font-size: 16pt; font-weight: bold; color: #111827; letter-spacing: 0.05em; }
    .sov-title    { font-size: 14pt; font-style: italic; color: #374151; }

    .meta { margin-top: 10px; font-size: 9pt; color: #4b5563; }
    .meta strong { color: #111827; }

    .sov-table { width: 100%; margin-top: 14px; border-collapse: collapse; }
    .sov-table th, .sov-table td { padding: 6px 8px; border: 1px solid #94a3b8; font-size: 9.5pt; vertical-align: middle; }
    .sov-table th { background: #cfe2f3; color: #1e3a8a; font-weight: bold; text-align: center; }
    .sov-table th.italic { font-style: italic; }
    .sov-table .row-label { background: #d9d9d9; color: #111827; font-weight: bold; font-size: 9pt; }
    .sov-table .right  { text-align: right; }
    .sov-table .center { text-align: center; }
    .sov-table .muted  { color: #9ca3af; }
    .sov-table tr.total-row td { background: #d9d9d9; font-weight: bold; font-size: 10.5pt; padding: 9px 8px; }
    .sov-table tr.margin-row td  { background: #ffffff; font-weight: bold; font-size: 11pt; padding: 7px 8px; }
    .sov-table tr.margin-row td.label { text-align: right; color: #111827; }

    .footer-note { margin-top: 12px; font-size: 8pt; color: #6b7280; font-style: italic; }
    .warn-box { margin-top: 10px; padding: 10px 12px; background: #fef3c7; border-left: 4px solid #d97706; font-size: 9pt; color: #78350f; }
    .warn-box strong { color: #78350f; }
</style>
</head>
<body>

<div class="header">
    <div class="left">
        <div class="project-name">{{ strtoupper($project->name) }}</div>
        <div class="meta">
            <strong>{{ $estimate->estimate_number ?? 'EST-' . $estimate->id }}</strong>
            · {{ $estimate->client?->name ?? $project->client?->name ?? '—' }}
            · {{ optional($estimate->created_at)->format('M j, Y') }}
        </div>
    </div>
    <div class="right">
        <div class="sov-title">ESTIMATE - SOV</div>
        <div class="meta" style="margin-top:2px;">{{ $company }}</div>
    </div>
</div>

<table class="sov-table">
    <thead>
        <tr>
            <th style="width: 25%;">COST TYPE</th>
            <th style="width: 14%;">EST.</th>
            <th style="width: 14%;">COST</th>
            <th style="width: 14%;">&nbsp;</th>
            <th style="width: 11%;" class="italic">GPM</th>
            <th style="width: 11%;" class="italic">Markup</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sov as $label => $row)
            <tr>
                <td class="row-label">{{ $label }}</td>
                <td class="right">{{ $row['est'] > 0 ? number_format($row['est'], 2) : '' }}</td>
                <td class="right">{{ $row['cost'] > 0 ? number_format($row['cost'], 2) : '' }}</td>
                <td class="right">{{ $row['gp'] != 0 ? number_format($row['gp'], 2) : '' }}</td>
                <td class="right">{{ $row['gpm']    !== null && $row['gpm']    != 0 ? number_format($row['gpm']    * 100, 2) . '%' : '' }}</td>
                <td class="right">{{ $row['markup'] !== null && $row['markup'] != 0 ? number_format($row['markup'] * 100, 2) . '%' : '' }}</td>
            </tr>
        @endforeach
        {{-- Blank padding rows to match the Excel spacing (visual breathing room) --}}
        <tr><td class="row-label">CHANGE ORDERS - APPROVED</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td class="row-label">&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>

        <tr class="total-row">
            <td class="center">GRAND TOTAL</td>
            <td class="right">{{ number_format($totalEst,  2) }}</td>
            <td class="right">{{ number_format($totalCost, 2) }}</td>
            <td class="right">{{ number_format($totalGp,   2) }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr class="margin-row">
            <td colspan="2"></td>
            <td class="label">Margin</td>
            <td class="right" style="font-size:13pt;">{{ $totalMargin !== null ? number_format($totalMargin * 100, 2) . '%' : '—' }}</td>
            <td colspan="2"></td>
        </tr>
        <tr class="margin-row">
            <td colspan="2"></td>
            <td class="label">Mark-Up</td>
            <td></td>
            <td class="right" style="font-size:13pt;">{{ $totalMarkup !== null ? number_format($totalMarkup * 100, 2) . '%' : '—' }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

@if(!empty($missingCostCrafts))
<div class="warn-box">
    <strong>Heads up — labor cost column is incomplete.</strong>
    These crafts have a billable rate but no cost rate yet, so the system is treating them as $0 cost
    (which is why Margin looks higher than it should):
    <strong>{{ implode(', ', $missingCostCrafts) }}</strong>.
    Open this project → <strong>Setup → Billable Rates</strong> and enter the <em>Base ST</em>
    + burden fields (Payroll Tax, Burden, Insurance) for each one, then regenerate this SOV.
</div>
@endif

<div class="footer-note">
    Internal Schedule of Values · generated from estimate {{ $estimate->estimate_number ?? '#'.$estimate->id }}.
    "01 DIRECT" includes direct labor + indirect field labor. "010 INDIRECT" is field staff (supervision / QA).
</div>

</body>
</html>
