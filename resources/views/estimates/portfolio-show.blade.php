@extends('layouts.app')
@section('title', 'Estimate ' . ($estimate->estimate_number ?? '#'.$estimate->id))
@section('content')

{{-- Standalone estimate detail view — used when an estimate has no project_id
     yet (a brand-new bid). Once accepted + converted, redirect logic in
     EstimatePortfolioController::show takes the user to the project-scoped
     detail page. So this template is only ever rendered for unconverted bids. --}}

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('estimates.portfolio') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to all estimates</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $estimate->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="font-mono">{{ $estimate->estimate_number ?? 'EST-#' . $estimate->id }}</span>
                · Client: <strong>{{ $estimate->client?->name ?? '—' }}</strong>
                · Status: <span class="font-semibold">{{ ucwords(str_replace('_', ' ', $estimate->status)) }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold px-3 py-2 rounded-lg" title="No project yet — accept this estimate to auto-create one.">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                Standalone bid — no project linked yet
            </span>
            <a href="#" onclick="alert('Open in project view will be available once accepted + converted.'); return false;"
               class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm cursor-not-allowed opacity-60"
               title="Convert this estimate first">
                Convert to Project
            </a>
        </div>
    </div>

    {{-- Helpful banner explaining the workflow --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            <div class="text-sm text-blue-900">
                <strong>How this works:</strong> This estimate isn't tied to a project yet. To start adding labor / material / equipment lines, click <em>Open as project draft</em> below — that creates a placeholder project so the full builder is unlocked. When the client accepts, the project becomes a real one and the budget + billable rates auto-populate.
            </div>
        </div>
    </div>

    {{-- Estimate metadata --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Estimate Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Total Cost</p>
                <p class="text-lg font-bold text-gray-900">${{ number_format((float) $estimate->total_cost, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Total Price</p>
                <p class="text-lg font-bold text-blue-700">${{ number_format((float) $estimate->total_price, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Margin</p>
                <p class="text-lg font-bold text-emerald-700">{{ number_format(((float) $estimate->margin_percent) * 100, 1) }}%</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Created</p>
                <p class="text-base text-gray-900">{{ optional($estimate->created_at)->format('M j, Y') }}</p>
            </div>

            @if($estimate->start_date || $estimate->end_date)
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Project Window</p>
                    <p class="text-sm text-gray-900">
                        {{ optional($estimate->start_date)->format('M j, Y') ?? '—' }}
                        →
                        {{ optional($estimate->end_date)->format('M j, Y') ?? '—' }}
                        @if($estimate->duration_days)
                            <span class="text-gray-500">({{ $estimate->duration_days }} days)</span>
                        @endif
                    </p>
                </div>
            @endif

            @if($estimate->valid_until)
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Valid Until</p>
                    <p class="text-sm text-gray-900">{{ $estimate->valid_until->format('M j, Y') }}</p>
                </div>
            @endif
        </div>

        @if($estimate->description)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Scope</p>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $estimate->description }}</p>
            </div>
        @endif
    </div>

    {{-- "Spawn project" prompt — primary CTA on this page so the user has an
         obvious next step. Creates a draft project and redirects to the
         project-scoped estimate detail page. --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-dashed border-blue-300 p-8 text-center">
        <svg class="w-12 h-12 text-blue-400 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Ready to build out this bid?</h3>
        <p class="text-sm text-gray-500 mb-4 max-w-md mx-auto">
            Open this estimate as a project draft to start adding sections, labor lines, material costs, and markups. The project stays in <em>Bidding</em> status until the client accepts.
        </p>
        <button type="button" onclick="spawnProjectDraft()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-sm">
            Open as Project Draft &rarr;
        </button>
    </div>

</div>

@push('scripts')
<script>
function spawnProjectDraft() {
    Swal.fire({
        title: 'Create draft project for this bid?',
        text: 'A project will be created in "Bidding" status, and you\'ll be taken to the full estimate builder. Status changes to "Active" when the client accepts.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Create draft',
    }).then(r => {
        if (!r.isConfirmed) return;

        fetch('{{ route("estimates.portfolio.spawn-project", $estimate) }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
        })
        .then(r => r.json())
        .then(b => {
            if (!b.success) {
                Swal.fire({ icon: 'error', title: 'Could not create draft', text: b.message });
                return;
            }
            location.href = b.url;
        });
    });
}
</script>
@endpush

@endsection
