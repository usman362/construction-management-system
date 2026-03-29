@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-12">
        <!-- Invoice Header -->
        <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-gray-300">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">INVOICE</h1>
                <p class="text-lg text-gray-600 mt-2">Invoice #: {{ $billingInvoice->invoice_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold">Date:</span>
                    {{ ($billingInvoice->invoice_date ?? $billingInvoice->billing_period_start)?->format('m/d/Y') ?? 'N/A' }}
                </p>
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">Status:</span>
                    @php
                        $statusClasses = match ($billingInvoice->status) {
                            'sent' => 'bg-blue-100 text-blue-800',
                            'paid' => 'bg-green-100 text-green-800',
                            'overdue' => 'bg-amber-100 text-amber-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClasses }}">
                        {{ ucfirst($billingInvoice->status ?? 'draft') }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Project and Client Info -->
        <div class="grid grid-cols-2 gap-8 mb-10 pb-6 border-b border-gray-300">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Bill From</h3>
                <p class="font-semibold text-gray-800">{{ config('app.company_name', 'Company Name') }}</p>
                <p class="text-sm text-gray-600">{{ config('app.company_address', 'Address') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Bill To</h3>
                <p class="font-semibold text-gray-800">{{ $billingInvoice->project?->client?->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600">Project: {{ $billingInvoice->project?->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600">Project #: {{ $billingInvoice->project?->project_number ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Billing Period -->
        <div class="grid grid-cols-2 gap-8 mb-10 pb-6 border-b border-gray-300">
            <div>
                <p class="text-xs font-semibold text-gray-600 uppercase">Billing Period</p>
                <p class="text-lg font-semibold text-gray-800">
                    {{ $billingInvoice->billing_period_start?->format('m/d/Y') ?? 'N/A' }} - {{ $billingInvoice->billing_period_end?->format('m/d/Y') ?? 'N/A' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-600 uppercase">Invoice Due Date</p>
                <p class="text-lg font-semibold text-gray-800">
                    {{ $billingInvoice->due_date?->format('m/d/Y') ?? 'Upon Receipt' }}
                </p>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="mb-10">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Line Items</h3>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-400">
                        <th class="px-4 py-3 text-left font-semibold text-gray-800">Description</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-800">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasItems = false;
                        $subtotal = 0;
                    @endphp
                    @if($billingInvoice->labor_amount > 0)
                        @php
                            $hasItems = true;
                            $subtotal += $billingInvoice->labor_amount;
                        @endphp
                        <tr class="border-b border-gray-300">
                            <td class="px-4 py-3 text-gray-800">Labor</td>
                            <td class="px-4 py-3 text-right text-gray-800">${{ number_format($billingInvoice->labor_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($billingInvoice->material_amount > 0)
                        @php
                            $hasItems = true;
                            $subtotal += $billingInvoice->material_amount;
                        @endphp
                        <tr class="border-b border-gray-300">
                            <td class="px-4 py-3 text-gray-800">Material</td>
                            <td class="px-4 py-3 text-right text-gray-800">${{ number_format($billingInvoice->material_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($billingInvoice->equipment_amount > 0)
                        @php
                            $hasItems = true;
                            $subtotal += $billingInvoice->equipment_amount;
                        @endphp
                        <tr class="border-b border-gray-300">
                            <td class="px-4 py-3 text-gray-800">Equipment</td>
                            <td class="px-4 py-3 text-right text-gray-800">${{ number_format($billingInvoice->equipment_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($billingInvoice->subcontractor_amount > 0)
                        @php
                            $hasItems = true;
                            $subtotal += $billingInvoice->subcontractor_amount;
                        @endphp
                        <tr class="border-b border-gray-300">
                            <td class="px-4 py-3 text-gray-800">Subcontractor</td>
                            <td class="px-4 py-3 text-right text-gray-800">${{ number_format($billingInvoice->subcontractor_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($billingInvoice->other_amount > 0)
                        @php
                            $hasItems = true;
                            $subtotal += $billingInvoice->other_amount;
                        @endphp
                        <tr class="border-b border-gray-300">
                            <td class="px-4 py-3 text-gray-800">Other</td>
                            <td class="px-4 py-3 text-right text-gray-800">${{ number_format($billingInvoice->other_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if(!$hasItems)
                        <tr class="border-b border-gray-300">
                            <td colspan="2" class="px-4 py-3 text-center text-gray-500">No line items</td>
                        </tr>
                    @endif
                    <tr class="border-b-2 border-gray-400">
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">Subtotal</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">${{ number_format($subtotal, 2) }}</td>
                    </tr>
                    <tr class="border-b-2 border-gray-400">
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">Tax</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">${{ number_format($billingInvoice->tax_amount ?? 0, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-100 border-b-2 border-gray-400">
                        <td class="px-4 py-3 text-right font-bold text-gray-800 text-lg">TOTAL</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800 text-lg">${{ number_format($billingInvoice->total_amount ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Notes Section -->
        @if($billingInvoice->notes)
        <div class="mb-10 pb-6 border-b border-gray-300">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Notes</h3>
            <p class="text-gray-700">{{ $billingInvoice->notes }}</p>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-center pt-6">
            @if($billingInvoice->status === 'draft')
                <form method="POST" action="{{ route('billing.send', $billingInvoice->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Send Invoice
                    </button>
                </form>
            @endif

            @if($billingInvoice->status !== 'paid')
                <form method="POST" action="{{ route('billing.mark-paid', $billingInvoice->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                        Mark as Paid
                    </button>
                </form>
            @endif

            <a href="{{ route('billing.pdf', $billingInvoice) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded inline-block">
                Download PDF
            </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                Print
            </button>

            <a href="{{ route('billing.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                Back to Billing
            </a>
        </div>
    </div>
</div>
@endsection
