{{--
    T&M Estimate PDF — Brenda 2026-06-19 redesign.
    Mirrors the on-screen T&M template: section-per-category, clean layout,
    Cost Summary at the bottom. Empty rows are filtered out controller-side.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate {{ $estimate->estimate_number ?? '#'.$estimate->id }}</title>
<style>
    @page { margin: 0.5in 0.5in 0.7in 0.5in; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; color: #1f2937; }
    h1, h2, h3 { margin: 0; color: #111827; }

    .header { display: table; width: 100%; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 3px double #1e3a8a; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .company-name { font-size: 18pt; font-weight: bold; color: #1e3a8a; letter-spacing: 0.02em; }
    .company-tag  { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }
    .doc-title    { font-size: 20pt; font-weight: bold; color: #111827; letter-spacing: 0.05em; }
    .doc-meta     { font-size: 8pt; color: #4b5563; margin-top: 2px; }

    .meta-grid { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
    .meta-grid td { vertical-align: top; padding: 5px 8px; border: 1px solid #d1d5db; font-size: 9pt; }
    .meta-grid .label { background: #f3f4f6; color: #374151; font-weight: bold; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.05em; width: 17%; }

    .section { margin-bottom: 12px; page-break-inside: avoid; }
    .section-header { background: #1e3a8a; color: #ffffff; padding: 5px 9px; font-weight: bold; font-size: 9.5pt; letter-spacing: 0.03em; }
    .section-header .count { float: right; font-weight: normal; font-size: 8pt; opacity: 0.85; }
    .lines { width: 100%; border-collapse: collapse; }
    .lines th, .lines td { padding: 4px 7px; font-size: 8.5pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    .lines th { background: #f9fafb; color: #4b5563; text-align: left; font-weight: bold; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
    .lines td.right, .lines th.right { text-align: right; }
    .lines td.center, .lines th.center { text-align: center; }
    .lines .code { font-family: 'Courier New', monospace; font-size: 8pt; color: #6b7280; }
    .section-sub { background: #eef2ff; font-weight: bold; }
    .section-sub td { border-top: 1.5px solid #1e3a8a; border-bottom: 1.5px solid #1e3a8a; padding: 5px 7px; }

    .summary { width: 60%; margin-left: 40%; margin-top: 18px; border-collapse: collapse; }
    .summary td { padding: 5px 9px; border: 1px solid #d1d5db; font-size: 9.5pt; }
    .summary .label { background: #f9fafb; color: #4b5563; }
    .summary .total-row td { background: #1e3a8a; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 8px 9px; border-color: #1e3a8a; }

    .empty-state { padding: 20px; text-align: center; color: #9ca3af; font-style: italic; font-size: 9pt; }

    .terms { margin-top: 16px; padding: 10px 12px; background: #f9fafb; border-left: 3px solid #1e3a8a; font-size: 8.5pt; color: #374151; }
    .terms-title { font-weight: bold; color: #111827; margin-bottom: 4px; font-size: 9pt; }

    .signature { margin-top: 24px; }
    .sig-block { display: inline-block; width: 45%; margin-right: 4%; vertical-align: top; }
    .sig-line { border-bottom: 1px solid #374151; height: 28px; margin-bottom: 3px; }
    .sig-label { font-size: 7.5pt; color: #6b7280; }
</style>
</head>
<body>

{{-- ── Header ── --}}
<div class="header">
    <div class="header-left">
        <div class="company-name">{{ $company }}</div>
        <div class="company-tag">Time &amp; Materials Estimate</div>
    </div>
    <div class="header-right">
        <div class="doc-title">ESTIMATE</div>
        <div class="doc-meta"><strong>{{ $estimate->estimate_number ?? 'EST-' . $estimate->id }}</strong></div>
        <div class="doc-meta">Date: {{ optional($estimate->created_at)->format('M j, Y') }}</div>
        @if($estimate->valid_until)
            <div class="doc-meta">Valid until: {{ $estimate->valid_until->format('M j, Y') }}</div>
        @endif
    </div>
</div>

{{-- ── Meta grid ── --}}
<table class="meta-grid">
    <tr>
        <td class="label">Client</td>
        <td>{{ $estimate->client?->name ?? $project->client?->name ?? '—' }}</td>
        <td class="label">Project</td>
        <td>{{ $project->name }}@if($project->project_number) ({{ $project->project_number }})@endif</td>
    </tr>
    <tr>
        <td class="label">Location</td>
        <td>{{ $estimate->location ?? trim(($project->city ?? '') . ', ' . ($project->state ?? ''), ', ') ?: '—' }}</td>
        <td class="label">Job Number</td>
        <td>{{ $estimate->job_number ?? $project->project_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Duration</td>
        <td>
            @if($estimate->project_duration_weeks)
                {{ $estimate->project_duration_weeks }} weeks
            @elseif($estimate->start_date && $estimate->end_date)
                {{ $estimate->start_date->format('M j, Y') }} → {{ $estimate->end_date->format('M j, Y') }}
            @else
                —
            @endif
        </td>
        <td class="label">Work Schedule</td>
        <td>{{ $estimate->work_schedule ?? '—' }}</td>
    </tr>
    @if($estimate->description)
        <tr>
            <td class="label">Scope</td>
            <td colspan="3">{{ $estimate->description }}</td>
        </tr>
    @endif
</table>

{{-- ── Sections ── --}}
@php
    $disciplineLabel = function ($l) {
        // Pretty description fallback for labor: craft + role
        if ($l->line_type === 'labor') {
            $parts = array_filter([$l->craft?->name, $l->role]);
            $desc  = (trim($l->description) && $l->description !== 'New labor line' && !str_starts_with($l->description, 'New '))
                   ? $l->description : null;
            return $desc ?: ($parts ? implode(' — ', $parts) : 'Labor');
        }
        if (trim($l->description) && !str_starts_with($l->description, 'New ')) return $l->description;
        if ($l->line_type === 'material') return $l->material?->name ?? 'Material';
        if ($l->line_type === 'equipment') return $l->equipment?->name ?? 'Equipment';
        if ($l->line_type === 'subcontractor') return $l->subcontractor_name ?? $l->discipline ?? 'Subcontractor';
        return ucfirst($l->line_type ?? 'Other');
    };
    $anyLines = collect($groups)->sum(fn($g) => $g['lines']->count());
@endphp

@if(!$anyLines)
    <div class="empty-state">No line items entered on this estimate yet.</div>
@endif

@foreach($groups as $key => $g)
    @if($g['lines']->isEmpty()) @continue @endif

    <div class="section">
        <div class="section-header">
            {{ $g['title'] }}
            <span class="count">{{ $g['lines']->count() }} {{ Str::plural('line', $g['lines']->count()) }} · ${{ number_format($g['total'], 2) }}</span>
        </div>
        <table class="lines">
            @if(in_array($key, ['direct_labor','indirect_field_labor','field_staff']))
                {{-- LABOR section: Cost Code, Craft/Role, Crew, Weeks, ST Hrs, OT Hrs, Rate, Total --}}
                <thead>
                    <tr>
                        <th style="width: 60px;">Cost Code</th>
                        <th>Craft / Role</th>
                        <th class="center" style="width: 40px;">Crew</th>
                        <th class="center" style="width: 40px;">Weeks</th>
                        <th class="right" style="width: 50px;">ST Hrs</th>
                        <th class="right" style="width: 50px;">OT Hrs</th>
                        <th class="right" style="width: 60px;">ST Rate</th>
                        <th class="right" style="width: 70px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['lines'] as $line)
                        <tr>
                            <td class="code">{{ $line->costCode->code ?? '—' }}</td>
                            <td>{{ $disciplineLabel($line) }}</td>
                            <td class="center">{{ $line->crew_size ?: '—' }}</td>
                            <td class="center">{{ $line->weeks ? rtrim(rtrim(number_format((float)$line->weeks, 1), '0'), '.') : '—' }}</td>
                            <td class="right">{{ number_format((float) $line->hours, 1) }}</td>
                            <td class="right">{{ number_format((float) $line->ot_hours, 1) }}</td>
                            <td class="right">${{ number_format((float) $line->hourly_billable_rate, 2) }}</td>
                            <td class="right"><strong>${{ number_format((float) $line->price_amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="section-sub">
                        <td colspan="7" class="right">Subtotal — {{ $g['title'] }}</td>
                        <td class="right">${{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            @elseif(in_array($key, ['equip_3p','equip_coe']))
                {{-- EQUIPMENT section: Cost Code, Description, Qty, Duration, UOM, Unit, Total --}}
                <thead>
                    <tr>
                        <th style="width: 60px;">Cost Code</th>
                        <th>Description</th>
                        <th class="center" style="width: 35px;">Qty</th>
                        <th class="center" style="width: 50px;">Duration</th>
                        <th class="center" style="width: 50px;">UOM</th>
                        <th class="right" style="width: 70px;">Unit Rate</th>
                        <th class="right" style="width: 70px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['lines'] as $line)
                        <tr>
                            <td class="code">{{ $line->costCode->code ?? '—' }}</td>
                            <td>{{ $disciplineLabel($line) }}</td>
                            <td class="center">{{ $line->quantity ? rtrim(rtrim(number_format((float)$line->quantity, 1),'0'),'.') : '1' }}</td>
                            <td class="center">{{ $line->equipment_duration ? rtrim(rtrim(number_format((float)$line->equipment_duration, 1),'0'),'.') : '—' }}</td>
                            <td class="center">{{ ucfirst($line->duration_uom ?? '—') }}</td>
                            <td class="right">${{ number_format((float) $line->unit_cost, 2) }}</td>
                            <td class="right"><strong>${{ number_format((float) $line->price_amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="section-sub">
                        <td colspan="6" class="right">Subtotal — {{ $g['title'] }}</td>
                        <td class="right">${{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            @elseif($key === 'material')
                {{-- MATERIAL section: Cost Code, Description, Vendor, Cost, Total --}}
                <thead>
                    <tr>
                        <th style="width: 60px;">Cost Code</th>
                        <th>Description</th>
                        <th style="width: 110px;">Vendor</th>
                        <th class="right" style="width: 80px;">Cost</th>
                        <th class="right" style="width: 80px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['lines'] as $line)
                        <tr>
                            <td class="code">{{ $line->costCode->code ?? '—' }}</td>
                            <td>{{ $disciplineLabel($line) }}</td>
                            <td>{{ $line->vendor_name ?? '—' }}</td>
                            <td class="right">${{ number_format((float)($line->quote_amount ?: $line->cost_amount), 2) }}</td>
                            <td class="right"><strong>${{ number_format((float) $line->price_amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="section-sub">
                        <td colspan="4" class="right">Subtotal — {{ $g['title'] }}</td>
                        <td class="right">${{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            @elseif($key === 'subcontractor')
                {{-- SUBCONTRACTOR section --}}
                <thead>
                    <tr>
                        <th style="width: 60px;">Cost Code</th>
                        <th style="width: 100px;">Discipline</th>
                        <th>Subcontractor / Notes</th>
                        <th class="right" style="width: 80px;">Cost</th>
                        <th class="right" style="width: 80px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['lines'] as $line)
                        <tr>
                            <td class="code">{{ $line->costCode->code ?? '—' }}</td>
                            <td>{{ $line->discipline ?? '—' }}</td>
                            <td>{{ $line->subcontractor_name ?? '' }}@if($line->subcontractor_name && trim($line->description) && !str_starts_with($line->description, 'New ')) — {{ $line->description }}@elseif(trim($line->description) && !str_starts_with($line->description, 'New ')){{ $line->description }}@endif</td>
                            <td class="right">${{ number_format((float) $line->quote_amount, 2) }}</td>
                            <td class="right"><strong>${{ number_format((float) $line->price_amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="section-sub">
                        <td colspan="4" class="right">Subtotal — {{ $g['title'] }}</td>
                        <td class="right">${{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            @else
                {{-- OTHER section --}}
                <thead>
                    <tr>
                        <th style="width: 60px;">Cost Code</th>
                        <th>Description</th>
                        <th class="right" style="width: 60px;">Qty</th>
                        <th class="right" style="width: 80px;">Unit Cost</th>
                        <th class="right" style="width: 80px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['lines'] as $line)
                        <tr>
                            <td class="code">{{ $line->costCode->code ?? '—' }}</td>
                            <td>{{ $disciplineLabel($line) }}</td>
                            <td class="right">{{ number_format((float) $line->quantity, 2) }}</td>
                            <td class="right">${{ number_format((float) $line->unit_cost, 2) }}</td>
                            <td class="right"><strong>${{ number_format((float) $line->price_amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="section-sub">
                        <td colspan="4" class="right">Subtotal — {{ $g['title'] }}</td>
                        <td class="right">${{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            @endif
        </table>
    </div>
@endforeach

{{-- ── Cost Summary ── --}}
@php
    $laborTotal = $groups['direct_labor']['total'] + $groups['indirect_field_labor']['total'] + $groups['field_staff']['total'];
@endphp

<table class="summary">
    @if($laborTotal > 0)
        <tr><td class="label">Total Labor</td><td class="right">${{ number_format($laborTotal, 2) }}</td></tr>
    @endif
    @foreach(['material' => 'Total Materials', 'equip_3p' => 'Total 3rd Party Equipment', 'equip_coe' => 'Total Company Owned Equipment', 'subcontractor' => 'Total Subcontractors', 'other' => 'Other'] as $k => $lbl)
        @if($groups[$k]['total'] > 0)
            <tr><td class="label">{{ $lbl }}</td><td class="right">${{ number_format($groups[$k]['total'], 2) }}</td></tr>
        @endif
    @endforeach
    <tr><td class="label">Total Cost</td><td class="right">${{ number_format((float) $estimate->total_cost, 2) }}</td></tr>
    <tr><td class="label">Margin ({{ number_format(((float) $estimate->margin_percent) * 100, 1) }}%)</td><td class="right">${{ number_format((float) ($estimate->total_price - $estimate->total_cost), 2) }}</td></tr>
    <tr class="total-row"><td>TOTAL PRICE</td><td class="right">${{ number_format((float) $estimate->total_price, 2) }}</td></tr>
</table>

{{-- ── Terms ── --}}
@if($estimate->terms_and_conditions)
    <div class="terms">
        <div class="terms-title">Terms &amp; Conditions</div>
        {!! nl2br(e($estimate->terms_and_conditions)) !!}
    </div>
@endif
@if($estimate->assumed_exclusions)
    <div class="terms">
        <div class="terms-title">Assumptions &amp; Exclusions</div>
        {!! nl2br(e($estimate->assumed_exclusions)) !!}
    </div>
@endif

{{-- ── Signature ── --}}
<div class="signature">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Client Signature &amp; Date</div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Authorized Representative — {{ $company }}</div>
    </div>
</div>

</body>
</html>
