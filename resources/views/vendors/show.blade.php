@extends('layouts.app')

@section('title', $vendor->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('vendors.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Vendors</a>
        <div class="space-x-2">
            <button type="button" onclick="editVendor({{ $vendor->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('vendors.destroy', $vendor) }}', null, '{{ route('vendors.index') }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $vendor->name }}</h1>
                <p class="text-gray-600 mt-2">
                    @php
                        $typeClass = match ($vendor->type) {
                            'subcontractor' => 'bg-orange-100 text-orange-800',
                            'supplier' => 'bg-blue-100 text-blue-800',
                            'rental' => 'bg-green-100 text-green-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $typeClass }}">
                        {{ ucfirst($vendor->type) }}
                    </span>
                    @if($vendor->specialty)
                        <span class="ml-2 text-sm text-gray-600">{{ $vendor->specialty }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-10">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Contact Information</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Contact Name</p>
                        <p class="text-gray-800">{{ $vendor->contact_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Email</p>
                        <p class="text-gray-800">
                            @if($vendor->email)
                                <a href="mailto:{{ $vendor->email }}" class="text-blue-600 hover:underline">{{ $vendor->email }}</a>
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Phone</p>
                        <p class="text-gray-800">{{ $vendor->phone ?? 'N/A' }}</p>
                    </div>
                    <div class="flex gap-4 pt-2">
                        <span class="text-xs text-gray-500">Preferred:</span>
                        <span class="text-sm font-medium">{{ $vendor->is_preferred ? 'Yes' : 'No' }}</span>
                        <span class="text-xs text-gray-500">Active:</span>
                        <span class="text-sm font-medium">{{ $vendor->is_active ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Address</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Street</p>
                        <p class="text-gray-800">{{ $vendor->address ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">City, State ZIP</p>
                        <p class="text-gray-800">
                            {{ $vendor->city ?? 'N/A' }}{{ $vendor->city ? ', ' : '' }}{{ $vendor->state ?? 'N/A' }}{{ $vendor->zip ? ' '.$vendor->zip : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commitments Section -->
        <div class="mb-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Commitments</h2>
            @if($vendor->commitments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-blue-100 border border-gray-300">
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Project</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Description</th>
                                <th class="border border-gray-300 px-4 py-2 text-right font-bold">Amount</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rowClass = 0;
                                $totalCommitted = 0;
                            @endphp
                            @foreach($vendor->commitments as $commitment)
                                @php
                                    $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                    $rowClass++;
                                    $totalCommitted += $commitment->amount ?? 0;
                                @endphp
                                <tr class="{{ $bgClass }} border border-gray-300">
                                    <td class="border border-gray-300 px-4 py-2">{{ $commitment->project->name ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $commitment->description ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($commitment->amount ?? 0, 2) }}</td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                            {{ ucfirst($commitment->status ?? 'pending') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-blue-100 border border-gray-300 font-bold">
                                <td colspan="2" class="border border-gray-300 px-4 py-2">TOTAL COMMITTED</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCommitted, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 text-center">
                    <p class="text-gray-500">No commitments found for this vendor.</p>
                </div>
            @endif
        </div>

        <!-- Invoices Section -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Recent Invoices</h2>
            @if($vendor->invoices->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-blue-100 border border-gray-300">
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Invoice #</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Project</th>
                                <th class="border border-gray-300 px-4 py-2 text-right font-bold">Amount</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Date</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rowClass = 0;
                                $totalInvoiced = 0;
                            @endphp
                            @foreach($vendor->invoices as $invoice)
                                @php
                                    $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                    $rowClass++;
                                    $totalInvoiced += $invoice->amount ?? 0;
                                @endphp
                                <tr class="{{ $bgClass }} border border-gray-300">
                                    <td class="border border-gray-300 px-4 py-2 font-semibold">{{ $invoice->invoice_number ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $invoice->project->name ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($invoice->amount ?? 0, 2) }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $invoice->invoice_date?->format('m/d/Y') ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                            {{ ucfirst($invoice->status ?? 'pending') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-blue-100 border border-gray-300 font-bold">
                                <td colspan="2" class="border border-gray-300 px-4 py-2">TOTAL INVOICED</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalInvoiced, 2) }}</td>
                                <td colspan="2" class="border border-gray-300 px-4 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 text-center">
                    <p class="text-gray-500">No invoices found for this vendor.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@include('vendors.partials.vendor-edit-modal')

@push('scripts')
<script>
var table = null;
function editVendor(id){
    $.get('{{ url('/vendors') }}/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="contact_name"]').value=d.contact_name||'';
        f.querySelector('[name="email"]').value=d.email||'';
        f.querySelector('[name="phone"]').value=d.phone||'';
        f.querySelector('[name="address"]').value=d.address||'';
        f.querySelector('[name="specialty"]').value=d.specialty||'';
        f.querySelector('#edit_is_preferred').checked=!!d.is_preferred;
        f.querySelector('#edit_is_active').checked=!!d.is_active;
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/vendors') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush
@endsection
