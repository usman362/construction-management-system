@extends('pdf.layout')

@section('title', 'INVOICE')
@section('subtitle', '')

@section('header-right')
    <div style="font-size: 18px; font-weight: bold; color: #1e3a5f;">#{{ $billingInvoice->invoice_number }}</div>
    <div class="meta-label">Invoice Date</div>
    <div class="meta-value">{{ ($billingInvoice->invoice_date ?? $billingInvoice->billing_period_start)?->format('M j, Y') ?? 'N/A' }}</div>
    <div style="margin-top: 4px;">
        <span class="badge {{ $billingInvoice->status === 'paid' ? 'badge-green' : ($billingInvoice->status === 'sent' ? 'badge-blue' : 'badge-gray') }}">
            {{ strtoupper($billingInvoice->status ?? 'DRAFT') }}
        </span>
    </div>
@endsection

@section('extra-styles')
    .invoice-parties { display: table; width: 100%; margin-bottom: 20px; }
    .invoice-party { display: table-cell; width: 50%; vertical-align: top; padding: 12px; }
    .invoice-party h3 { font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: bold; letter-spacing: 1px; margin-bottom: 6px; }
    .invoice-party .name { font-size: 13px; font-weight: bold; color: #1e293b; }
    .invoice-party .detail { font-size: 10px; color: #64748b; line-height: 1.6; }

    .invoice-total-box { background: #1e3a5f; color: #fff; padding: 15px 20px; text-align: right; margin-top: 10px; }
    .invoice-total-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
    .invoice-total-box .amount { font-size: 22px; font-weight: bold; }
@endsection

@section('content')
    {{-- Parties --}}
    <div class="invoice-parties">
        <div class="invoice-party" style="background: #f8fafc; border-left: 3px solid #2563eb;">
            <h3>Bill From</h3>
            <div class="name">{{ config('app.company_name', 'Company Name') }}</div>
            <div class="detail">{{ config('app.company_address', 'Address') }}</div>
        </div>
        <div class="invoice-party" style="background: #f8fafc; border-left: 3px solid #64748b;">
            <h3>Bill To</h3>
            <div class="name">{{ $billingInvoice->project?->client?->name ?? 'N/A' }}</div>
            <div class="detail">
                Project: {{ $billingInvoice->project?->name ?? 'N/A' }}<br>
                Project #: {{ $billingInvoice->project?->project_number ?? 'N/A' }}
            </div>
        </div>
    </div>

    {{-- Billing Period & Due Date --}}
    <div class="meta-row">
        <div class="meta-cell">
            <div class="meta-label">Billing Period</div>
            <div class="meta-value">
                {{ $billingInvoice->billing_period_start?->format('M j, Y') ?? 'N/A' }} —
                {{ $billingInvoice->billing_period_end?->format('M j, Y') ?? 'N/A' }}
            </div>
        </div>
        <div class="meta-cell" style="text-align: right;">
            <div class="meta-label">Due Date</div>
            <div class="meta-value">{{ $billingInvoice->due_date?->format('M j, Y') ?? 'Upon Receipt' }}</div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="section-title">Line Items</div>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotal = 0; $hasItems = false; @endphp

            @if(($billingInvoice->labor_amount ?? 0) > 0)
                @php $hasItems = true; $subtotal += $billingInvoice->labor_amount; @endphp
                <tr>
                    <td>Labor</td>
                    <td class="text-right">${{ number_format($billingInvoice->labor_amount, 2) }}</td>
                </tr>
            @endif

            @if(($billingInvoice->material_amount ?? 0) > 0)
                @php $hasItems = true; $subtotal += $billingInvoice->material_amount; @endphp
                <tr>
                    <td>Materials</td>
                    <td class="text-right">${{ number_format($billingInvoice->material_amount, 2) }}</td>
                </tr>
            @endif

            @if(($billingInvoice->equipment_amount ?? 0) > 0)
                @php $hasItems = true; $subtotal += $billingInvoice->equipment_amount; @endphp
                <tr>
                    <td>Equipment</td>
                    <td class="text-right">${{ number_format($billingInvoice->equipment_amount, 2) }}</td>
                </tr>
            @endif

            @if(($billingInvoice->subcontractor_amount ?? 0) > 0)
                @php $hasItems = true; $subtotal += $billingInvoice->subcontractor_amount; @endphp
                <tr>
                    <td>Subcontractor</td>
                    <td class="text-right">${{ number_format($billingInvoice->subcontractor_amount, 2) }}</td>
                </tr>
            @endif

            @if(($billingInvoice->other_amount ?? 0) > 0)
                @php $hasItems = true; $subtotal += $billingInvoice->other_amount; @endphp
                <tr>
                    <td>Other</td>
                    <td class="text-right">${{ number_format($billingInvoice->other_amount, 2) }}</td>
                </tr>
            @endif

            @if(!$hasItems)
                <tr>
                    <td colspan="2" class="text-center" style="color: #94a3b8;">No line items</td>
                </tr>
            @endif

            <tr style="border-top: 2px solid #e2e8f0;">
                <td class="text-right"><strong>Subtotal</strong></td>
                <td class="text-right"><strong>${{ number_format($subtotal, 2) }}</strong></td>
            </tr>
            <tr>
                <td class="text-right">Tax</td>
                <td class="text-right">${{ number_format($billingInvoice->tax_amount ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="invoice-total-box">
        <div class="label">Total Amount Due</div>
        <div class="amount">${{ number_format($billingInvoice->total_amount ?? 0, 2) }}</div>
    </div>

    @if($billingInvoice->notes)
    <div style="margin-top: 20px; padding: 10px; background: #f8fafc; border-left: 3px solid #94a3b8;">
        <div style="font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: bold; margin-bottom: 4px;">Notes</div>
        <div style="font-size: 10px; color: #475569;">{{ $billingInvoice->notes }}</div>
    </div>
    @endif
@endsection
