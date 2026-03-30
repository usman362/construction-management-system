@extends('layouts.app')

@section('title', ($commitment->commitment_number ?? 'Commitment') . ' — ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('projects.commitments.index', $project) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Back to commitments</a>
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ $project->name }}</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-2">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $commitment->commitment_number ?? 'Commitment' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $commitment->description }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">{{ ucfirst($commitment->status) }}</span>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Vendor</p>
                <p class="text-gray-900 mt-1">{{ $commitment->vendor->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Amount</p>
                <p class="text-gray-900 mt-1 font-semibold">${{ number_format((float) $commitment->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Cost code</p>
                <p class="text-gray-900 mt-1">
                    @if($commitment->costCode)
                        {{ $commitment->costCode->code }} — {{ $commitment->costCode->name }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Committed date</p>
                <p class="text-gray-900 mt-1">{{ $commitment->committed_date?->format('M j, Y') ?? '—' }}</p>
            </div>
            @if($commitment->po_number)
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">PO number</p>
                    <p class="text-gray-900 mt-1">{{ $commitment->po_number }}</p>
                </div>
            @endif
        </div>

        @if($commitment->invoices->isNotEmpty())
            <div class="px-6 pb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Related invoices</h2>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                            <tr>
                                <th class="px-4 py-2">Number</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($commitment->invoices as $inv)
                                <tr>
                                    <td class="px-4 py-2">{{ $inv->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ $inv->invoice_date?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">${{ number_format((float) $inv->amount, 2) }}</td>
                                    <td class="px-4 py-2">{{ $inv->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
