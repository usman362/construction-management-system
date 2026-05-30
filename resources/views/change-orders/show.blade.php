@extends('layouts.app')

@section('title', 'Change Order Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('projects.change-orders.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Change Orders</a>
        <div class="space-x-2">
            <a href="{{ route('projects.change-orders.index', $project) }}?edit={{ $changeOrder->id }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</a>
            <form id="delete-change-order-form" method="POST" action="{{ route('projects.change-orders.destroy', [$project, $changeOrder]) }}" style="display:inline;">
                @csrf
                @method('DELETE')
            </form>
            <button type="button" onclick="confirmDelete('delete-change-order-form')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <!-- Project Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PROJECT</h3>
                <p class="text-lg font-bold text-gray-900">{{ $project->name }}</p>
                <p class="text-sm text-gray-600">{{ $project->address ?? 'N/A' }}</p>
            </div>

            <!-- Purchase Order Info -->
            {{-- 2026-05-23 (Brenda): "Purchase order needs to pull the
                 purchase order we typed in during set up or pull the
                 original contract po." Fall through:
                 project.po_number → CO's client_po → auto fallback. --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PURCHASE ORDER</h3>
                <p class="text-lg font-bold text-gray-900">
                    {{ $project->po_number ?: ($changeOrder->client_po ?: 'PO-' . $project->id) }}
                </p>
                <p class="text-sm text-gray-600">Contract Value: ${{ number_format($project->contract_value ?? 0, 2) }}</p>
            </div>

            <!-- Change Order Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">CHANGE ORDER</h3>
                <p class="text-lg font-bold text-gray-900">{{ $changeOrder->co_number }}</p>
                <p class="text-sm text-gray-600">{{ $changeOrder->date->format('M d, Y') }}</p>
                @php $pricingLabel = ($changeOrder->pricing_type ?? 'lump_sum') === 't_and_m' ? 'T & M' : 'Lump Sum'; @endphp
                <p class="text-xs mt-1">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ ($changeOrder->pricing_type ?? 'lump_sum') === 't_and_m' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800' }}">
                        {{ $pricingLabel }}
                    </span>
                </p>
                @if($changeOrder->client_po)
                    <p class="text-xs text-gray-500 mt-1">Client PO: <span class="font-semibold text-gray-700">{{ $changeOrder->client_po }}</span></p>
                @endif
            </div>
        </div>
    </div>

    <!-- Description of Work Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-4">Description of Work</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">DESCRIPTION</h3>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $changeOrder->description }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">SCOPE OF WORK</h3>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $changeOrder->scope_of_work }}</p>
            </div>
        </div>
    </div>

    <!-- Financial Summary Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Financial Summary</h2>

        <div class="space-y-2">
            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.1 Original Purchase Order Sum</span>
                <span class="font-bold text-gray-900">${{ number_format($project->contract_value ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.2 Net change by previously authorized COs</span>
                <span class="font-bold text-gray-900">${{ number_format($previousCOsTotal ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 bg-gray-50 p-2">
                <span class="font-semibold text-gray-700">0.3 Purchase Order Sum prior to this CO</span>
                <span class="font-bold text-gray-900">${{ number_format(($project->contract_value ?? 0) + ($previousCOsTotal ?? 0), 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 text-blue-900 bg-blue-50 p-2">
                <span class="font-semibold">0.4 This CO amount {{ $changeOrder->amount >= 0 ? '(INCREASE)' : '(DECREASE)' }}</span>
                <span class="font-bold">${{ number_format($changeOrder->amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 bg-green-50 p-2">
                <span class="font-semibold text-green-900">0.5 New Purchase Order Sum</span>
                <span class="font-bold text-green-900">${{ number_format(($project->contract_value ?? 0) + ($previousCOsTotal ?? 0) + $changeOrder->amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.6 Contract Time change (days)</span>
                <span class="font-bold text-gray-900">{{ $changeOrder->contract_time_change_days ?? 0 }} days</span>
            </div>

            <div class="flex justify-between items-center pb-2">
                <span class="font-semibold text-gray-700">0.7 New completion date</span>
                <span class="font-bold text-gray-900">{{ $changeOrder->new_completion_date?->format('M d, Y') ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Previously Authorized COs Table -->
    @if ($previousCOs && $previousCOs->count())
        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <h2 class="text-2xl font-bold mb-4">Previously Authorized Change Orders</h2>

            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CO #</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previousCOs as $priorCO)
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $priorCO->co_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $priorCO->date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">${{ number_format($priorCO->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ───── Service Job Breakdown ─────
         2026-05-23 (Brenda bug — "this area of the change order is not
         populating"): three of the four sections (Equipment, Material,
         Other) were static placeholder text. Now all four sections pull
         from the CO's linked estimate's line items, grouped by
         line_type. Labor falls back to legacy laborDetails (legacy COs
         created before the estimate flow). If nothing exists for a
         section we show a one-line "—" placeholder instead of
         pretending data is there. --}}
    @php
        $coEst         = \App\Models\Estimate::where('change_order_id', $changeOrder->id)->first();
        $coEstLines    = $coEst ? $coEst->lines : collect();
        $laborLines    = $coEstLines->filter(fn($l) => $l->line_type === 'labor');
        $equipLines    = $coEstLines->filter(fn($l) => $l->line_type === 'equipment');
        $materialLines = $coEstLines->filter(fn($l) => in_array($l->line_type, ['material', 'consumable']));
        $otherLines    = $coEstLines->filter(fn($l) => ! in_array($l->line_type, ['labor', 'equipment', 'material', 'consumable']));

        $laborCost = $laborLines->sum('cost_amount');
        $equipCost = $equipLines->sum('cost_amount');
        $matCost   = $materialLines->sum('cost_amount');
        $othCost   = $otherLines->sum('cost_amount');
        $sjbTotal  = $laborCost + $equipCost + $matCost + $othCost;

        // Fall back to legacy laborDetails if no estimate lines exist
        $hasLegacyLabor = $laborLines->isEmpty() && $changeOrder->laborDetails && $changeOrder->laborDetails->count() > 0;
    @endphp
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
            <h2 class="text-2xl font-bold">Service Job Breakdown</h2>
            {{-- 2026-05-30 (KH red tab): Phase Code editable on ALL line
                 types lives on the linked estimate's inline WBS table.
                 One-click jump so the user doesn't have to hunt for it. --}}
            @if($coEst)
                <a href="{{ route('estimates.show', $coEst) }}"
                   class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    Edit lines on linked estimate
                </a>
            @endif
        </div>

        <!-- LABOR -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 flex justify-between">
                <span>LABOR</span>
                <span class="text-sm font-normal text-gray-500">{{ $hasLegacyLabor ? $changeOrder->laborDetails->count() : $laborLines->count() }} line(s) · ${{ number_format($hasLegacyLabor ? $changeOrder->laborDetails->sum('cost') : $laborCost, 2) }}</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr class="text-xs uppercase text-gray-700">
                            <th class="px-3 py-2 text-left">Phase Code</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">ST Hrs</th>
                            <th class="px-3 py-2 text-right">ST Rate</th>
                            <th class="px-3 py-2 text-right">OT Hrs</th>
                            <th class="px-3 py-2 text-right">OT Rate</th>
                            <th class="px-3 py-2 text-right">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($hasLegacyLabor)
                            @foreach($changeOrder->laborDetails as $labor)
                                <tr class="border-b">
                                    <td class="px-3 py-2 text-sm font-mono text-gray-700">—</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $labor->skill }}</td>
                                    <td class="px-3 py-2 text-sm text-right">{{ $labor->hours_per_day }} × {{ $labor->duration }}d × {{ $labor->amount_needed }}</td>
                                    <td class="px-3 py-2 text-sm text-right">${{ number_format($labor->rate, 2) }}</td>
                                    <td class="px-3 py-2 text-sm text-right text-gray-400">—</td>
                                    <td class="px-3 py-2 text-sm text-right text-gray-400">—</td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold">${{ number_format($labor->cost, 2) }}</td>
                                </tr>
                            @endforeach
                        @elseif($laborLines->isNotEmpty())
                            @foreach($laborLines as $l)
                                <tr class="border-b">
                                    <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $l->costCode?->code ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $l->description }}</td>
                                    <td class="px-3 py-2 text-sm text-right">{{ number_format((float) $l->hours, 2) }}</td>
                                    <td class="px-3 py-2 text-sm text-right">${{ number_format((float) $l->hourly_cost_rate, 2) }}</td>
                                    <td class="px-3 py-2 text-sm text-right">{{ $l->ot_hours ? number_format((float) $l->ot_hours, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right">{{ $l->ot_hourly_cost_rate ? '$' . number_format((float) $l->ot_hourly_cost_rate, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold">${{ number_format((float) $l->cost_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400 text-sm">No labor lines on the CO estimate yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- EQUIPMENT -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 flex justify-between">
                <span>EQUIPMENT</span>
                <span class="text-sm font-normal text-gray-500">{{ $equipLines->count() }} line(s) · ${{ number_format($equipCost, 2) }}</span>
            </h3>
            @if($equipLines->isEmpty())
                <p class="text-gray-400 text-sm">No equipment lines on the CO estimate yet.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr class="text-xs uppercase text-gray-700">
                            <th class="px-3 py-2 text-left">Phase Code</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-left">Unit</th>
                            <th class="px-3 py-2 text-right">Unit Cost</th>
                            <th class="px-3 py-2 text-right">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipLines as $l)
                            <tr class="border-b">
                                <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $l->costCode?->code ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $l->description }}</td>
                                <td class="px-3 py-2 text-sm text-right">{{ rtrim(rtrim(number_format((float) $l->quantity, 2), '0'), '.') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600">{{ $l->unit ?: '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right">${{ number_format((float) $l->unit_cost, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">${{ number_format((float) $l->cost_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- MATERIAL & CONSUMABLES -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 flex justify-between">
                <span>MATERIAL &amp; CONSUMABLES</span>
                <span class="text-sm font-normal text-gray-500">{{ $materialLines->count() }} line(s) · ${{ number_format($matCost, 2) }}</span>
            </h3>
            @if($materialLines->isEmpty())
                <p class="text-gray-400 text-sm">No material / consumable lines on the CO estimate yet.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr class="text-xs uppercase text-gray-700">
                            <th class="px-3 py-2 text-left">Phase Code</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Quote</th>
                            <th class="px-3 py-2 text-right">Freight</th>
                            <th class="px-3 py-2 text-right">Tax</th>
                            <th class="px-3 py-2 text-right">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materialLines as $l)
                            <tr class="border-b">
                                <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $l->costCode?->code ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $l->description }}</td>
                                <td class="px-3 py-2 text-sm text-right">{{ $l->quote_amount   !== null ? '$' . number_format((float) $l->quote_amount, 2)   : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right">{{ $l->freight_amount !== null ? '$' . number_format((float) $l->freight_amount, 2) : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right">{{ $l->tax_amount     !== null ? '$' . number_format((float) $l->tax_amount, 2)     : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">${{ number_format((float) $l->cost_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- OTHER COSTS -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 flex justify-between">
                <span>OTHER COSTS</span>
                <span class="text-sm font-normal text-gray-500">{{ $otherLines->count() }} line(s) · ${{ number_format($othCost, 2) }}</span>
            </h3>
            @if($otherLines->isEmpty())
                <p class="text-gray-400 text-sm">No other-cost lines on the CO estimate yet.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr class="text-xs uppercase text-gray-700">
                            <th class="px-3 py-2 text-left">Phase Code</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($otherLines as $l)
                            <tr class="border-b">
                                <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $l->costCode?->code ?? '—' }}</td>
                                <td class="px-3 py-2 text-xs uppercase text-gray-600">{{ str_replace('_', ' ', $l->line_type ?? 'other') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $l->description }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">${{ number_format((float) $l->cost_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- GRAND TOTAL -->
        <div class="bg-gray-900 text-white p-4 rounded mt-6 flex justify-between items-center">
            <span class="text-lg font-bold">GRAND TOTAL</span>
            <span class="text-2xl font-bold">${{ number_format($changeOrder->amount, 2) }}</span>
        </div>
        @if($coEst && abs($changeOrder->amount - $sjbTotal) > 0.01)
            <p class="text-[11px] text-gray-500 mt-2 text-right">
                Breakdown sum: ${{ number_format($sjbTotal, 2) }} · CO amount: ${{ number_format($changeOrder->amount, 2) }}. The CO amount is the price quoted to the owner (includes markup); the breakdown shows cost only.
            </p>
        @endif
    </div>

    {{-- ── CO Estimate (the "smaller estimating module") ──
         Brenda 04.25.2026: each CO can have its own line-itemized estimate
         (labor / material / equipment with markups) so the price quoted to
         the owner is fully traceable to costs. --}}
    @php
        $coEstimate = \App\Models\Estimate::where('change_order_id', $changeOrder->id)->first();
    @endphp
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">CO Pricing Estimate</h3>
                <p class="text-xs text-gray-500 mt-0.5">Itemize labor, materials, equipment, and markups for this change order so the price you charge the owner is fully traceable.</p>
            </div>
            @if($coEstimate)
                <a href="{{ route('projects.estimates.show', [$project, $coEstimate]) }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Open Estimate &rarr;
                </a>
            @else
                <button type="button" onclick="buildCoEstimate()"
                        class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Build Estimate
                </button>
            @endif
        </div>
        @if($coEstimate)
            <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Cost</p>
                    <p class="font-bold text-gray-900">${{ number_format((float) $coEstimate->total_cost, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Price</p>
                    <p class="font-bold text-blue-700">${{ number_format((float) $coEstimate->total_price, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Margin</p>
                    <p class="font-bold text-emerald-700">{{ number_format(((float) $coEstimate->margin_percent) * 100, 1) }}%</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    @if (in_array($changeOrder->status, ['pending', 'draft', 'submitted']))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            <div class="flex gap-4">
                <form method="POST" action="{{ route('projects.change-orders.approve', [$project, $changeOrder]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                        Approve Change Order
                    </button>
                </form>
                <a href="{{ route('projects.change-orders.index', $project) }}?edit={{ $changeOrder->id }}" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-6 rounded">
                    Edit
                </a>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
    function buildCoEstimate() {
        Swal.fire({
            title: 'Build estimate for this CO?',
            text: 'Creates a draft estimate scoped to this change order. You can add labor, material, equipment, sub, and other lines, with per-line markups.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            confirmButtonText: 'Create',
        }).then(r => {
            if (!r.isConfirmed) return;
            fetch('{{ route("projects.change-orders.build-estimate", [$project, $changeOrder]) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            })
            .then(r => r.json())
            .then(b => {
                if (!b.success) { Swal.fire({ icon: 'error', title: b.message }); return; }
                location.href = b.url;
            });
        });
    }
    </script>
    @endpush

    {{-- Phase 7F — E-signature capture. The partial swaps between a draw-and-save
         canvas (unsigned) and a static "Signed by X on Y" panel (signed). --}}
    <div class="mt-6">
        @include('partials.signature-pad', [
            'document'  => $changeOrder,
            'submitUrl' => route('projects.change-orders.sign', [$project, $changeOrder]),
            'docLabel'  => 'Change Order #' . ($changeOrder->co_number ?? $changeOrder->id),
        ])
    </div>
</div>
@endsection
