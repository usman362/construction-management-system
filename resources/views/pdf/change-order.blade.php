@extends('pdf.layout')

@section('title', 'Change Order')
@section('subtitle', 'Project: ' . $project->name . ' (#' . $project->project_number . ')')

@section('header-right')
    <div class="meta-label">CO Number</div>
    <div class="meta-value">{{ $changeOrder->co_number }}</div>
    <div class="meta-label" style="margin-top:4px;">Date</div>
    <div class="meta-value">{{ $changeOrder->date?->format('M j, Y') ?? now()->format('M j, Y') }}</div>
@endsection

@section('extra-styles')
<style>
    .co-info-grid { width: 100%; margin-bottom: 15px; }
    .co-info-grid td { padding: 5px 8px; font-size: 10px; vertical-align: top; }
    .co-info-label { color: #666; font-weight: bold; text-transform: uppercase; font-size: 8px; letter-spacing: 0.5px; }
    .co-info-value { color: #1e3a5f; font-size: 10px; }
    .section-title {
        background: #1e3a5f;
        color: #fff;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 6px 10px;
        margin-top: 15px;
        margin-bottom: 0;
    }
    .scope-box {
        border: 1px solid #ddd;
        padding: 10px;
        font-size: 10px;
        line-height: 1.5;
        min-height: 60px;
        background: #fafbfc;
    }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .items-table th {
        background: #e8edf3;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 6px 8px;
        border-bottom: 2px solid #1e3a5f;
        color: #1e3a5f;
    }
    .items-table td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #eee; }
    .items-table .total-row td {
        background: #1e3a5f;
        color: #fff;
        font-weight: bold;
        font-size: 11px;
        padding: 8px;
        border: none;
    }
    .summary-box {
        border: 2px solid #1e3a5f;
        padding: 12px;
        margin-top: 15px;
    }
    .summary-box table { width: 100%; }
    .summary-box td { padding: 4px 8px; font-size: 10px; }
    .summary-box .label { font-weight: bold; color: #333; }
    .summary-box .value { text-align: right; font-weight: bold; color: #1e3a5f; }
    .summary-box .total-line { border-top: 2px solid #1e3a5f; }
    .summary-box .total-line td { padding-top: 8px; font-size: 12px; }

    .signature-section { margin-top: 40px; width: 100%; }
    .signature-section td { padding: 0; vertical-align: bottom; }
    .sig-block { width: 45%; }
    .sig-line { border-bottom: 1px solid #333; height: 30px; margin-bottom: 5px; }
    .sig-label { font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; }
    .sig-date { margin-top: 15px; }
    .sig-date .sig-line { height: 20px; }

    .terms-section {
        margin-top: 25px;
        border: 1px solid #ddd;
        padding: 10px;
        background: #fafbfc;
    }
    .terms-section p { font-size: 8px; color: #555; line-height: 1.6; margin: 0 0 4px 0; }
    .terms-title { font-size: 9px; font-weight: bold; color: #333; margin-bottom: 6px; }
</style>
@endsection

@section('content')
    {{-- CO Info Grid --}}
    <table class="co-info-grid" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="co-info-label">Change Order #</div>
                <div class="co-info-value">{{ $changeOrder->co_number }}</div>
            </td>
            <td width="25%">
                <div class="co-info-label">Date</div>
                <div class="co-info-value">{{ $changeOrder->date?->format('M j, Y') ?? '—' }}</div>
            </td>
            <td width="25%">
                <div class="co-info-label">Status</div>
                <div class="co-info-value">{{ ucfirst($changeOrder->status) }}</div>
            </td>
            <td width="25%">
                <div class="co-info-label">Project</div>
                <div class="co-info-value">{{ $project->name }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="co-info-label">Title</div>
                <div class="co-info-value">{{ $changeOrder->title }}</div>
            </td>
            <td>
                <div class="co-info-label">Contract Time Change</div>
                <div class="co-info-value">{{ $changeOrder->contract_time_change_days ?? 0 }} days</div>
            </td>
            <td>
                <div class="co-info-label">New Completion Date</div>
                <div class="co-info-value">{{ $changeOrder->new_completion_date?->format('M j, Y') ?? 'No Change' }}</div>
            </td>
        </tr>
        @if($project->client)
        <tr>
            <td colspan="2">
                <div class="co-info-label">Owner / Client</div>
                <div class="co-info-value">{{ $project->client->name }}</div>
            </td>
            <td colspan="2">
                <div class="co-info-label">Contractor</div>
                <div class="co-info-value">{{ config('app.name', 'BuildTrack Construction') }}</div>
            </td>
        </tr>
        @endif
    </table>

    {{-- Description --}}
    <div class="section-title">Description</div>
    <div class="scope-box">{{ $changeOrder->description ?? 'N/A' }}</div>

    {{-- Scope of Work --}}
    @if($changeOrder->scope_of_work)
    <div class="section-title">Scope of Work</div>
    <div class="scope-box">{{ $changeOrder->scope_of_work }}</div>
    @endif

    {{-- Line Items --}}
    @if($changeOrder->items && $changeOrder->items->count() > 0)
    <div class="section-title">Cost Breakdown - Materials &amp; Equipment</div>
    <table class="items-table" cellspacing="0">
        <thead>
            <tr>
                <th style="text-align:left;">Description</th>
                <th style="text-align:right;">Qty</th>
                <th style="text-align:center;">Unit</th>
                <th style="text-align:right;">Unit Cost</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $itemsTotal = 0; @endphp
            @foreach($changeOrder->items as $item)
            @php $lineTotal = ($item->quantity ?? 0) * ($item->unit_cost ?? 0); $itemsTotal += $lineTotal; @endphp
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align:right;">{{ number_format($item->quantity ?? 0, 2) }}</td>
                <td style="text-align:center;">{{ $item->unit_of_measure ?? 'EA' }}</td>
                <td style="text-align:right;">${{ number_format($item->unit_cost ?? 0, 2) }}</td>
                <td style="text-align:right;">${{ number_format($lineTotal, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Materials/Equipment Subtotal</td>
                <td style="text-align:right;">${{ number_format($itemsTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Labor Details --}}
    @if($changeOrder->laborDetails && $changeOrder->laborDetails->count() > 0)
    <div class="section-title">Cost Breakdown - Labor</div>
    <table class="items-table" cellspacing="0">
        <thead>
            <tr>
                <th style="text-align:left;">Craft / Description</th>
                <th style="text-align:right;">Hours</th>
                <th style="text-align:right;">Rate</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $laborTotal = 0; @endphp
            @foreach($changeOrder->laborDetails as $labor)
            @php $labLine = ($labor->hours ?? 0) * ($labor->rate ?? 0); $laborTotal += $labLine; @endphp
            <tr>
                <td>{{ $labor->description ?? ($labor->craft?->name ?? 'Labor') }}</td>
                <td style="text-align:right;">{{ number_format($labor->hours ?? 0, 2) }}</td>
                <td style="text-align:right;">${{ number_format($labor->rate ?? 0, 2) }}</td>
                <td style="text-align:right;">${{ number_format($labLine, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Labor Subtotal</td>
                <td style="text-align:right;">${{ number_format($laborTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Summary Box --}}
    <div class="summary-box">
        <table cellspacing="0">
            <tr>
                <td class="label" width="70%">Original Contract Amount</td>
                <td class="value">${{ number_format($project->budget ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Previously Approved Change Orders</td>
                <td class="value">${{ number_format($previousCOTotal ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">This Change Order ({{ $changeOrder->co_number }})</td>
                <td class="value">${{ number_format($changeOrder->amount ?? 0, 2) }}</td>
            </tr>
            <tr class="total-line">
                <td class="label">New Contract Amount</td>
                <td class="value">${{ number_format(($project->budget ?? 0) + ($previousCOTotal ?? 0) + ($changeOrder->amount ?? 0), 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Terms & Conditions --}}
    <div class="terms-section">
        <div class="terms-title">Terms &amp; Conditions</div>
        <p>The above change in scope is hereby agreed upon. The original contract is modified only to the extent herein specified. All other terms and conditions of the original contract and any prior change orders remain in full force and effect.</p>
        <p>This Change Order becomes part of the contract when signed by all parties listed below. Work shall not proceed until this Change Order has been approved and signed by the Owner.</p>
    </div>

    {{-- Signature Section
         When the change order has been e-signed in BuildTrack, drop the captured
         signature image onto the Owner / Client signature line and pre-fill the
         printed name + date. Blank lines stay for hand-signing the printed copy. --}}
    <table class="signature-section" cellspacing="0">
        <tr>
            <td class="sig-block">
                @if(!empty($changeOrder->signature))
                    <img src="{{ $changeOrder->signature }}" alt="Owner Signature" style="max-height:55px; display:block; margin-bottom:4px;">
                @endif
                <div class="sig-line"></div>
                <div class="sig-label">Owner / Client Signature</div>
                <div class="sig-date">
                    <div class="sig-line">{{ $changeOrder->signature_name ?? '' }}</div>
                    <div class="sig-label">Printed Name</div>
                </div>
                <div class="sig-date">
                    <div class="sig-line">{{ optional($changeOrder->signed_at)->format('M j, Y') ?? '' }}</div>
                    <div class="sig-label">Date</div>
                </div>
            </td>
            <td width="10%">&nbsp;</td>
            <td class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Contractor Signature</div>
                <div class="sig-date">
                    <div class="sig-line"></div>
                    <div class="sig-label">Printed Name</div>
                </div>
                <div class="sig-date">
                    <div class="sig-line"></div>
                    <div class="sig-label">Date</div>
                </div>
            </td>
        </tr>
    </table>
@endsection
