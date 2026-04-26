@extends('layouts.app')
@section('title', 'Estimates')
@section('content')

@php
    $statusBadge = [
        'draft'                => 'bg-gray-100 text-gray-700 border-gray-200',
        'submitted'            => 'bg-blue-100 text-blue-800 border-blue-200',
        'sent_to_client'       => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'accepted'             => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'rejected'             => 'bg-rose-100 text-rose-800 border-rose-200',
        'converted_to_project' => 'bg-purple-100 text-purple-800 border-purple-200',
        'approved'             => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'revised'              => 'bg-amber-100 text-amber-800 border-amber-200',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Estimates</h1>
            <p class="text-sm text-gray-500 mt-1">Portfolio-wide register of bids across all clients and projects.</p>
        </div>
        <button type="button" onclick="openModal('newEstimateModal')"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Estimate
        </button>
    </div>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 font-bold">Total Estimates</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_count'] }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-4">
            <p class="text-xs uppercase tracking-wide text-blue-700 font-bold">Pipeline</p>
            <p class="text-2xl font-bold text-blue-900 mt-1">${{ number_format($summary['pipeline'], 0) }}</p>
            <p class="text-[10px] text-blue-700 mt-0.5">Draft + Submitted + Sent</p>
        </div>
        <div class="bg-emerald-50 rounded-lg shadow-sm border border-emerald-200 p-4">
            <p class="text-xs uppercase tracking-wide text-emerald-700 font-bold">Won</p>
            <p class="text-2xl font-bold text-emerald-900 mt-1">${{ number_format($summary['won'], 0) }}</p>
            <p class="text-[10px] text-emerald-700 mt-0.5">Accepted + Converted</p>
        </div>
        <div class="bg-rose-50 rounded-lg shadow-sm border border-rose-200 p-4">
            <p class="text-xs uppercase tracking-wide text-rose-700 font-bold">Lost</p>
            <p class="text-2xl font-bold text-rose-900 mt-1">${{ number_format($summary['lost'], 0) }}</p>
            <p class="text-[10px] text-rose-700 mt-0.5">Rejected</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Estimate # / name / desc"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Client</label>
                <select name="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected((int)($filters['client_id'] ?? 0) === $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
            <a href="{{ route('estimates.portfolio') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($estimates->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">
                No estimates yet. Click "New Estimate" to create your first bid.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Estimate #</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Name</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Client</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Project</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Total Price</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Margin %</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Created</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($estimates as $est)
                        <tr class="hover:bg-blue-50/30">
                            <td class="px-3 py-2 font-mono text-blue-700 font-semibold">{{ $est->estimate_number ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-900">{{ $est->name }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $est->client?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600">
                                @if($est->project)
                                    <a href="{{ route('projects.show', $est->project) }}" class="text-blue-600 hover:underline">{{ $est->project->project_number }}</a>
                                @else
                                    <span class="text-gray-400 italic">— standalone bid —</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">${{ number_format((float) $est->total_price, 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format(((float) $est->margin_percent) * 100, 1) }}%</td>
                            <td class="px-3 py-2">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $statusBadge[$est->status] ?? 'bg-gray-100' }}">
                                    {{ $statusLabels[$est->status] ?? ucfirst($est->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">{{ optional($est->created_at)->format('M j, Y') }}</td>
                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('estimates.portfolio.show', $est->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Open &rarr;</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $estimates->links() }}
            </div>
        @endif
    </div>
</div>

{{-- New Estimate modal — picks client first, project optional --}}
<div id="newEstimateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('newEstimateModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">New Estimate</h3>
            <button onclick="closeModal('newEstimateModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="newEstimateForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                <select name="client_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Pick a client —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-500 mt-1">Estimate's billable rates and markups will pre-fill from this client's defaults.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estimate Name *</label>
                <input type="text" name="name" required placeholder="e.g. Site work — Smith warehouse expansion"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estimate Number</label>
                <input type="text" name="estimate_number" placeholder="Auto: EST-{{ now()->year }}-####"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                <input type="date" name="valid_until" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-[11px] text-gray-500 mt-1">When the bid expires if the client hasn't responded.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Scope / Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Brief summary of the work."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('newEstimateModal')" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="saveNewEstimate()" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                    Create &amp; Open
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function saveNewEstimate() {
    const form = document.getElementById('newEstimateForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    const payload = {};
    fd.forEach((v, k) => { if (v !== '') payload[k] = v; });

    fetch('{{ route("estimates.portfolio.store") }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(b => {
        if (!b.success) {
            Swal.fire({ icon: 'error', title: 'Could not create', text: b.message || 'Unknown error' });
            return;
        }
        location.href = b.url;
    })
    .catch(e => Swal.fire({ icon: 'error', title: 'Save failed', text: e.message }));
}
</script>
@endpush

@endsection
