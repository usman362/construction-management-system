{{--
    Estimate PDF — Phase 3 deliverable.
    Renders the bid in a clean, client-presentable layout with sections,
    line-level pricing, and a margin-aware totals table at the bottom.

    Dompdf-friendly: pure tables, inline styles, no external CSS.
--}}
{{-- Self-contained — doesn't extend the shared pdf.layout because the
     estimate has its own header/footer treatment and we don't want the
     generic report chrome. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate {{ $estimate->estimate_number ?? '#'.$estimate->id }}</title>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #1f2937; }
    h1, h2, h3, h4 { margin: 0 0 6px; color: #111827; }
    .header { display: table; width: 100%; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid #2563eb; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .company-name { font-size: 22pt; font-weight: bold; color: #2563eb; }
    .company-tag  { font-size: 9pt; color: #6b7280; }
    .doc-title    { font-size: 18pt; font-weight: bold; color: #111827; margin-top: 4px; }
    .doc-meta     { font-size: 9pt; color: #6b7280; margin-top: 4px; }

    .meta-grid { width: 100%; margin-bottom: 18px; }
    .meta-grid td { vertical-align: top; padding: 4px 8px; border: 1px solid #e5e7eb; }
    .meta-grid .label { background: #f9fafb; color: #6b7280; font-weight: bold; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.05em; width: 25%; }

    .section-block { margin-bottom: 14px; page-break-inside: avoid; }
    .section-header { background: #1e40af; color: #ffffff; padding: 6px 10px; font-weight: bold; font-size: 11pt; }
    .section-header .price { float: right; font-weight: normal; font-size: 10pt; }
    .lines { width: 100%; border-collapse: collapse; }
    .lines th, .lines td { padding: 5px 8px; font-size: 9pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    .lines th { background: #f3f4f6; color: #374151; text-align: left; font-weight: bold; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.03em; }
    .lines td.right, .lines th.right { text-align: right; }
    .lines td.center, .lines th.center { text-align: center; }
    .type-pill { display: inline-block; padding: 1px 6px; background: #e5e7eb; color: #374151; border-radius: 8px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .section-subtotal { background: #eff6ff; font-weight: bold; }

    .grand-totals { width: 60%; margin-left: 40%; margin-top: 18px; border-collapse: collapse; }
    .grand-totals td { padding: 6px 10px; border: 1px solid #e5e7eb; font-size: 10pt; }
    .grand-totals .label { background: #f9fafb; color: #6b7280; }
    .grand-totals .price-row { background: #1e40af; color: #ffffff; font-weight: bold; font-size: 12pt; }

    .terms { margin-top: 24px; padding: 12px; background: #f9fafb; border-left: 3px solid #2563eb; font-size: 9pt; color: #374151; }
    .terms-title { font-weight: bold; color: #111827; margin-bottom: 6px; }

    .signature { margin-top: 32px; }
    .sig-block { display: inline-block; width: 45%; margin-right: 4%; vertical-align: top; }
    .sig-line { border-bottom: 1px solid #1f2937; height: 30px; margin-bottom: 4px; }
    .sig-label { font-size: 8pt; color: #6b7280; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-left">
        <div class="company-name">{{ $company }}</div>
        <div class="company-tag">Construction Estimate</div>
    </div>
    <div class="header-right">
        <div class="doc-title">ESTIMATE</div>
        <div class="doc-meta">{{ $estimate->estimate_number ?? 'EST-' . $estimate->id }}</div>
        <div class="doc-meta">Date: {{ optional($estimate->created_at)->format('M j, Y') }}</div>
        @if($estimate->valid_until)
            <div class="doc-meta">Valid until: {{ $estimate->valid_until->format('M j, Y') }}</div>
        @endif
    </div>
</div>

{{-- Meta block --}}
<table class="meta-grid">
    <tr>
        <td class="label">Client</td>
        <td>{{ $estimate->client?->name ?? $project->client?->name ?? '—' }}</td>
        <td class="label">Project</td>
        <td>{{ $project->name }} ({{ $project->project_number }})</td>
    </tr>
    <tr>
        <td class="label">Project Address</td>
        <td>{{ trim($project->address . ', ' . $project->city . ', ' . $project->state . ' ' . $project->zip, ', ') ?: '—' }}</td>
        <td class="label">Duration</td>
        <td>
            @if($estimate->start_date && $estimate->end_date)
                {{ $estimate->start_date->format('M j, Y') }} → {{ $estimate->end_date->format('M j, Y') }}
                ({{ $estimate->duration_days ?? '—' }} days)
            @else
                —
            @endif
        </td>
    </tr>
    @if($estimate->description)
        <tr>
            <td class="label">Scope</td>
            <td colspan="3">{{ $estimate->description }}</td>
        </tr>
    @endif
</table>

{{-- Sections + lines --}}
@php
    // Build a flat "All groups including unsectioned bucket" so the PDF has a
    // single rendering loop. Unsectioned lines come last under "General" header.
    $unsectionedLines = $estimate->lines->whereNull('section_id');
    $groups = $estimate->sections->map(fn ($s) => ['name' => $s->name, 'lines' => $s->lines, 'cost' => $s->cost_amount, 'price' => $s->price_amount]);
    if ($unsectionedLines->isNotEmpty()) {
        $groups->push([
            'name'  => 'General',
            'lines' => $unsectionedLines,
            'cost'  => $unsectionedLines->sum('cost_amount'),
            'price' => $unsectionedLines->sum('price_amount'),
        ]);
    }
@endphp

@foreach($groups as $g)
    <div class="section-block">
        <div class="section-header">
            {{ $g['name'] }}
            <span class="price">${{ number_format((float) $g['price'], 2) }}</span>
        </div>
        <table class="lines">
            <thead>
                <tr>
                    <th style="width: 70px;">Type</th>
                    <th>Description</th>
                    <th class="right" style="width: 60px;">Qty</th>
                    <th class="right" style="width: 70px;">Unit Cost</th>
                    <th class="right" style="width: 80px;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($g['lines'] as $line)
                    <tr>
                        <td><span class="type-pill">{{ ucfirst($line->line_type ?? 'other') }}</span></td>
                        <td>
                            {{ $line->description }}
                            @if($line->line_type === 'labor' && $line->craft)
                                <br><small style="color:#9ca3af;">{{ $line->craft->name }}</small>
                            @endif
                        </td>
                        <td class="right">
                            @if($line->line_type === 'labor')
                                {{ number_format((float) $line->hours, 2) }} hr
                            @else
                                {{ number_format((float) $line->quantity, 2) }} {{ $line->unit ?? '' }}
                            @endif
                        </td>
                        <td class="right">
                            ${{ number_format((float) ($line->line_type === 'labor' ? $line->hourly_cost_rate : $line->unit_cost), 2) }}
                        </td>
                        <td class="right">${{ number_format((float) $line->price_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="section-subtotal">
                    <td colspan="4" class="right">Subtotal — {{ $g['name'] }}</td>
                    <td class="right">${{ number_format((float) $g['price'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endforeach

{{-- Grand totals --}}
<table class="grand-totals">
    <tr>
        <td class="label" style="width: 40%;">Total Cost</td>
        <td class="right">${{ number_format((float) $estimate->total_cost, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Margin ({{ number_format(((float) $estimate->margin_percent) * 100, 2) }}%)</td>
        <td class="right">${{ number_format((float) ($estimate->total_price - $estimate->total_cost), 2) }}</td>
    </tr>
    <tr class="price-row">
        <td>TOTAL PRICE</td>
        <td class="right">${{ number_format((float) $estimate->total_price, 2) }}</td>
    </tr>
</table>

{{-- Terms & Exclusions --}}
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

{{-- Signature --}}
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
