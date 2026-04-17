@extends('pdf.layout')

@section('title', 'PURCHASE ORDER')

@section('extra-styles')
    .po-header-row { display: table; width: 100%; margin-bottom: 20px; padding: 0; }
    .po-header-left { display: table-cell; width: 50%; vertical-align: top; }
    .po-header-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }

    .po-number-box { background: #1e3a5f; color: white; padding: 12px 16px; border-radius: 4px; display: inline-block; min-width: 200px; }
    .po-number-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #bae6fd; }
    .po-number { font-size: 20px; font-weight: bold; color: white; }

    .po-info-row { display: table; width: 100%; margin-bottom: 15px; }
    .po-info-cell { display: table-cell; vertical-align: top; padding-right: 30px; }

    .info-block { margin-bottom: 12px; }
    .info-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 2px; }
    .info-value { font-size: 11px; color: #1a1a1a; line-height: 1.4; }

    .parties-section { display: table; width: 100%; margin-bottom: 25px; }
    .party-block { display: table-cell; width: 50%; vertical-align: top; padding-right: 30px; }
    .party-block:last-child { padding-right: 0; }

    .party-title { font-size: 12px; font-weight: bold; color: #1e3a5f; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #2563eb; }

    .detail-section { margin-bottom: 20px; }
    .detail-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px 16px; }
    .detail-row { display: table; width: 100%; margin-bottom: 8px; }
    .detail-row:last-child { margin-bottom: 0; }
    .detail-label { display: table-cell; width: 20%; vertical-align: top; font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; }
    .detail-value { display: table-cell; width: 80%; vertical-align: top; font-size: 11px; color: #1a1a1a; }

    .items-table th { background-color: #1e3a5f; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px; text-align: left; }
    .items-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    .items-table tr:nth-child(even) td { background-color: #f8fafc; }
    .items-table .text-right { text-align: right; }
    .items-table .text-center { text-align: center; }

    .line-number { width: 5%; text-align: center; }
    .description { width: 25%; }
    .material { width: 15%; }
    .quantity { width: 10%; text-align: center; }
    .uom { width: 10%; text-align: center; }
    .unit-cost { width: 15%; text-align: right; }
    .line-total { width: 20%; text-align: right; }

    .summary-section { margin-top: 20px; display: table; width: 100%; }
    .summary-spacer { display: table-cell; width: 60%; }
    .summary-totals { display: table-cell; width: 40%; vertical-align: top; }

    .total-row { display: table; width: 100%; margin-bottom: 8px; }
    .total-label { display: table-cell; width: 60%; text-align: right; font-size: 10px; padding-right: 10px; }
    .total-value { display: table-cell; width: 40%; text-align: right; font-size: 10px; font-weight: bold; }

    .grand-total { display: table; width: 100%; background: #1e3a5f; color: white; padding: 12px 10px; border-radius: 4px; margin-top: 8px; }
    .grand-total .total-label { color: white; }
    .grand-total .total-value { color: white; font-size: 14px; }

    .notes-section { margin-top: 20px; padding: 12px; background: #f0f9ff; border-left: 4px solid #2563eb; border-radius: 2px; }
    .notes-section .label { font-size: 9px; text-transform: uppercase; color: #1e3a5f; font-weight: bold; margin-bottom: 4px; }
    .notes-section .content { font-size: 10px; color: #1a1a1a; line-height: 1.5; }

    .authorization-section { margin-top: 30px; display: table; width: 100%; }
    .auth-block { display: table-cell; width: 50%; text-align: center; padding-right: 20px; }
    .auth-block:last-child { padding-right: 0; }
    .auth-line { border-top: 1px solid #1a1a1a; margin-top: 40px; margin-bottom: 4px; }
    .auth-name { font-size: 9px; font-weight: bold; color: #1a1a1a; }

    .status-badge { margin-top: 15px; }
@endsection

@section('header-right')
    <div class="po-number-box">
        <div class="po-number-label">PO #</div>
        <div class="po-number">{{ $purchaseOrder->po_number }}</div>
    </div>
@endsection

@section('content')
    <!-- PO Information Row -->
    <div class="po-info-row">
        <div class="po-info-cell">
            <div class="info-block">
                <div class="info-label">Issue Date</div>
                <div class="info-value">{{ $purchaseOrder->issued_at->format('F j, Y') }}</div>
            </div>
        </div>
        <div class="po-info-cell">
            <div class="info-block">
                <div class="info-label">Delivery Date</div>
                <div class="info-value">{{ $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format('F j, Y') : 'TBD' }}</div>
            </div>
        </div>
    </div>

    <!-- Party Information -->
    <div class="parties-section">
        <!-- Ship To (Project) -->
        <div class="party-block">
            <div class="party-title">Ship To</div>
            <div class="info-block">
                <div class="info-label">Project</div>
                <div class="info-value">{{ $purchaseOrder->project->name }}</div>
            </div>
            <div class="info-block">
                <div class="info-label">Address</div>
                <div class="info-value">
                    {{ $purchaseOrder->project->address }}<br>
                    {{ $purchaseOrder->project->city }}, {{ $purchaseOrder->project->state }} {{ $purchaseOrder->project->zip_code }}
                </div>
            </div>
            @if($purchaseOrder->project->phone)
                <div class="info-block">
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $purchaseOrder->project->phone }}</div>
                </div>
            @endif
        </div>

        <!-- Vendor Information -->
        <div class="party-block">
            <div class="party-title">Vendor</div>
            <div class="info-block">
                <div class="info-label">Vendor Name</div>
                <div class="info-value">{{ $purchaseOrder->vendor->name }}</div>
            </div>
            <div class="info-block">
                <div class="info-label">Contact</div>
                <div class="info-value">
                    @if($purchaseOrder->vendor->contact_person)
                        {{ $purchaseOrder->vendor->contact_person }}<br>
                    @endif
                    @if($purchaseOrder->vendor->phone)
                        {{ $purchaseOrder->vendor->phone }}<br>
                    @endif
                    @if($purchaseOrder->vendor->email)
                        {{ $purchaseOrder->vendor->email }}
                    @endif
                </div>
            </div>
            <div class="info-block">
                <div class="info-label">Address</div>
                <div class="info-value">
                    {{ $purchaseOrder->vendor->address }}<br>
                    {{ $purchaseOrder->vendor->city }}, {{ $purchaseOrder->vendor->state }} {{ $purchaseOrder->vendor->zip_code }}
                </div>
            </div>
        </div>
    </div>

    <!-- PO Details -->
    <div class="detail-section">
        <div class="detail-box">
            @if($purchaseOrder->costCode)
                <div class="detail-row">
                    <div class="detail-label">Cost Code</div>
                    <div class="detail-value">{{ $purchaseOrder->costCode->code }} - {{ $purchaseOrder->costCode->name }}</div>
                </div>
            @endif

            @if($purchaseOrder->description)
                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">{{ $purchaseOrder->description }}</div>
                </div>
            @endif

            @if($purchaseOrder->notes)
                <div class="detail-row">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">{{ $purchaseOrder->notes }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="section-title">Line Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="line-number">#</th>
                <th class="description">Description</th>
                <th class="material">Material</th>
                <th class="quantity">Qty</th>
                <th class="uom">UOM</th>
                <th class="unit-cost">Unit Cost</th>
                <th class="line-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $index => $item)
                <tr>
                    <td class="line-number">{{ $index + 1 }}</td>
                    <td class="description">{{ $item->description }}</td>
                    <td class="material">
                        @if($item->material)
                            {{ $item->material->name }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="quantity">{{ number_format($item->quantity, 2) }}</td>
                    <td class="uom">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="unit-cost">${{ number_format($item->unit_cost, 2) }}</td>
                    <td class="line-total">${{ number_format($item->total_cost, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No line items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-spacer"></div>
        <div class="summary-totals">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">${{ number_format($purchaseOrder->subtotal, 2) }}</div>
            </div>

            @if($purchaseOrder->tax_amount > 0)
                <div class="total-row">
                    <div class="total-label">Tax ({{ $purchaseOrder->tax_rate }}%):</div>
                    <div class="total-value">${{ number_format($purchaseOrder->tax_amount, 2) }}</div>
                </div>
            @endif

            @if($purchaseOrder->shipping_cost > 0)
                <div class="total-row">
                    <div class="total-label">Shipping:</div>
                    <div class="total-value">${{ number_format($purchaseOrder->shipping_cost, 2) }}</div>
                </div>
            @endif

            <div class="grand-total">
                <div class="total-label">TOTAL:</div>
                <div class="total-value">${{ number_format($purchaseOrder->total_amount, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    @if($purchaseOrder->internal_notes)
        <div class="notes-section">
            <div class="label">Internal Notes</div>
            <div class="content">{{ $purchaseOrder->internal_notes }}</div>
        </div>
    @endif

    <!-- Status and Authorization -->
    <div style="margin-top: 30px; padding: 15px; background: #f8fafc; border-radius: 4px;">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; vertical-align: middle;">
                @if($purchaseOrder->status)
                    <div style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px;">Status</div>
                    <div>
                        @if($purchaseOrder->status === 'draft')
                            <span class="badge badge-gray">Draft</span>
                        @elseif($purchaseOrder->status === 'pending')
                            <span class="badge badge-yellow">Pending</span>
                        @elseif($purchaseOrder->status === 'approved')
                            <span class="badge badge-blue">Approved</span>
                        @elseif($purchaseOrder->status === 'ordered')
                            <span class="badge badge-blue">Ordered</span>
                        @elseif($purchaseOrder->status === 'received')
                            <span class="badge badge-green">Received</span>
                        @elseif($purchaseOrder->status === 'cancelled')
                            <span class="badge badge-red">Cancelled</span>
                        @else
                            <span class="badge badge-gray">{{ ucfirst($purchaseOrder->status) }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div style="display: table-cell; vertical-align: middle; text-align: right;">
                @if($purchaseOrder->issuedBy)
                    <div style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px;">Issued By</div>
                    <div style="font-size: 11px; color: #1a1a1a;">{{ $purchaseOrder->issuedBy->name }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Authorization Signatures -->
    <div class="authorization-section">
        <div class="auth-block">
            <div style="font-size: 10px; color: #1a1a1a; margin-bottom: 4px;">Authorized By</div>
            <div class="auth-line"></div>
            <div class="auth-name">_________________________</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Signature</div>
        </div>
        <div class="auth-block">
            <div style="font-size: 10px; color: #1a1a1a; margin-bottom: 4px;">Received By</div>
            <div class="auth-line"></div>
            <div class="auth-name">_________________________</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Signature</div>
        </div>
    </div>
@endsection
