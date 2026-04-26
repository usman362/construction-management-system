@extends('layouts.app')

@section('title', 'Estimate Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('projects.estimates.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Estimates</a>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Phase 3: PDF + Send to Client + Accept/Reject --}}
            <a href="{{ route('projects.estimates.pdf', [$project, $estimate]) }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-3 py-2 rounded-lg border border-gray-200" title="Download PDF">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>

            @if($estimate->status === 'draft' || $estimate->status === 'submitted')
                <button type="button" onclick="markEstimateSent()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-3 py-2 rounded-lg">
                    Mark Sent to Client
                </button>
            @endif

            @if(in_array($estimate->status, ['sent_to_client', 'submitted', 'draft']))
                <button type="button" onclick="recordEstimateResponse('accepted')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-3 py-2 rounded-lg">
                    Mark Accepted
                </button>
                <button type="button" onclick="recordEstimateResponse('rejected')" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-3 py-2 rounded-lg">
                    Mark Rejected
                </button>
            @endif

            {{-- Phase 2: Convert to Project — visible once accepted (or on any non-converted estimate) --}}
            @if($estimate->status !== 'converted_to_project' && $estimate->status !== 'rejected')
                <button type="button" onclick="confirmConvertEstimate()" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    Accept &amp; Convert to Project
                </button>
            @endif

            @if($estimate->converted_to_project_id)
                <a href="{{ route('projects.show', $estimate->converted_to_project_id) }}" class="inline-flex items-center gap-2 bg-green-100 text-green-800 text-sm font-semibold px-3 py-2 rounded-lg border border-green-200" title="View the project this estimate became">
                    ✓ Converted — Open Project
                </a>
            @endif

            <button type="button" onclick="editEstimateShow()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded text-sm">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('projects.estimates.destroy', [$project, $estimate]) }}', null, '{{ route('projects.estimates.index', $project) }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded text-sm">Delete</button>
        </div>
    </div>

    <!-- Estimate Header -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PROJECT</h3>
                <p class="text-lg font-bold text-gray-900">{{ $project->name }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">ESTIMATE NUMBER</h3>
                <p class="text-lg font-bold text-gray-900">{{ $estimate->estimate_number }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">NAME</h3>
                <p class="text-lg font-bold text-gray-900">{{ $estimate->name }}</p>
            </div>
        </div>

        @if ($estimate->description)
            <div class="pt-8 border-t">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">DESCRIPTION</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $estimate->description }}</p>
            </div>
        @endif
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-4 bg-white border border-gray-200 shadow-sm rounded-lg p-4">
            <p class="font-semibold text-gray-900">Import complete</p>
            <p class="text-sm text-gray-600 mt-1">Created: <span class="font-semibold text-green-700">{{ $result['created'] ?? 0 }}</span>, Skipped: <span class="font-semibold text-amber-700">{{ $result['skipped'] ?? 0 }}</span></p>
            @if(!empty($result['errors']))
                <details class="mt-2"><summary class="text-xs text-red-700 cursor-pointer">Errors ({{ count($result['errors']) }})</summary>
                    <ul class="mt-1 text-xs text-red-600 max-h-40 overflow-auto">
                        @foreach($result['errors'] as $err)
                            <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         ESTIMATING PHASE 1 — Sections + Typed Lines Builder
         Lives above the legacy Line Items table so the existing CSV import
         flow keeps working unchanged. New estimates should use this section.
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6" id="phase1Builder"
         x-data="estimateBuilder({{ $estimate->id }})">

        {{-- ── Totals strip ── --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Total Cost</p>
                <p class="text-2xl font-bold text-gray-900 mt-1" x-text="'$' + fmt(totals.total_cost)"></p>
                <p class="text-[11px] text-gray-400 mt-1">What we pay</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-xs uppercase tracking-wide text-blue-700 font-semibold">Total Price</p>
                <p class="text-2xl font-bold text-blue-900 mt-1" x-text="'$' + fmt(totals.total_price)"></p>
                <p class="text-[11px] text-blue-600 mt-1">What we charge the client</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Margin</p>
                <p class="text-2xl font-bold text-emerald-900 mt-1"
                   x-text="(totals.margin_percent * 100).toFixed(1) + '%'"></p>
                <p class="text-[11px] text-emerald-600 mt-1"
                   x-text="'$' + fmt(totals.total_price - totals.total_cost) + ' profit'"></p>
            </div>
        </div>

        {{-- ── Add Section bar ── --}}
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Sections & Lines</h2>
            <button type="button" @click="newSectionName = ''; openSectionModal = true"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Section
            </button>
        </div>

        {{-- ── Sections list ── --}}
        @forelse($estimate->sections as $section)
            <details class="border border-gray-200 rounded-lg mb-3" open>
                <summary class="flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 cursor-pointer rounded-t-lg">
                    <div>
                        <span class="font-semibold text-gray-900">{{ $section->name }}</span>
                        <span class="ml-2 text-xs text-gray-500">{{ $section->lines->count() }} line(s)</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-600">Cost: <strong>${{ number_format((float) $section->cost_amount, 2) }}</strong></span>
                        <span class="text-blue-700">Price: <strong>${{ number_format((float) $section->price_amount, 2) }}</strong></span>
                        <button type="button"
                                onclick="event.preventDefault(); event.stopPropagation(); confirmDeleteSection({{ $section->id }})"
                                class="text-red-600 hover:text-red-800 text-xs">Remove section</button>
                    </div>
                </summary>

                {{-- Lines table --}}
                @if($section->lines->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-gray-400">No lines in this section yet.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-white border-b border-gray-100">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">Qty / Hrs</th>
                                <th class="px-3 py-2 text-right">Unit / Rate</th>
                                <th class="px-3 py-2 text-right">Markup %</th>
                                <th class="px-3 py-2 text-right">Cost</th>
                                <th class="px-3 py-2 text-right">Price</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($section->lines as $line)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2"><span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded">{{ ucfirst($line->line_type ?? 'other') }}</span></td>
                                    <td class="px-3 py-2 text-gray-900">{{ $line->description }}</td>
                                    <td class="px-3 py-2 text-right">{{ $line->line_type === 'labor' ? number_format((float) $line->hours, 2) . ' hrs' : number_format((float) $line->quantity, 2) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format((float) ($line->line_type === 'labor' ? $line->hourly_cost_rate : $line->unit_cost), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(((float) $line->markup_percent) * 100, 1) }}%</td>
                                    <td class="px-3 py-2 text-right">${{ number_format((float) $line->cost_amount, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-blue-700">${{ number_format((float) $line->price_amount, 2) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" onclick="confirmDeleteLine({{ $line->id }})" class="text-red-600 hover:text-red-800 text-xs">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Add Line form for this section --}}
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 font-semibold uppercase mr-1">Add:</span>
                        @foreach($lineTypes as $key => $label)
                            <button type="button"
                                    @click="openLineModal({{ $section->id }}, '{{ $key }}')"
                                    class="text-xs font-semibold px-3 py-1 bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 rounded transition">
                                + {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </details>
        @empty
            <div class="text-center py-12 border-2 border-dashed border-gray-200 rounded-lg">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                <p class="text-sm text-gray-500">No sections yet.</p>
                <p class="text-xs text-gray-400 mt-1">Click "Add Section" to start grouping your bid (e.g. Sitework, Foundation, Framing).</p>
            </div>
        @endforelse

        {{-- ───── Add Section modal ───── --}}
        <div x-show="openSectionModal" x-transition.opacity
             @click.self="openSectionModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" style="display:none;">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">New Section</h3>
                <input type="text" x-model="newSectionName" placeholder="e.g. Sitework, Concrete, Framing"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none mb-3" autofocus>
                <textarea x-model="newSectionDescription" placeholder="Optional notes" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none mb-4"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openSectionModal = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                    <button type="button" @click="saveSection()" :disabled="!newSectionName" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-lg">
                        Add Section
                    </button>
                </div>
            </div>
        </div>

        {{-- ───── Add Line modal — generic form, fields shown by type ───── --}}
        <div x-show="openLineModalFlag" x-transition.opacity
             @click.self="openLineModalFlag = false"
             class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" style="display:none;">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    Add <span x-text="lineDraft.line_type" class="capitalize"></span> line
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description *</label>
                        <input type="text" x-model="lineDraft.description"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    {{-- Labor-only fields --}}
                    <template x-if="lineDraft.line_type === 'labor'">
                        <div class="md:col-span-2 grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Craft</label>
                                <select x-model="lineDraft.craft_id" @change="onCraftChange()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">— pick —</option>
                                    @foreach($crafts as $c)
                                        <option value="{{ $c->id }}" data-rate="{{ $c->base_hourly_rate }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Hours</label>
                                <input type="number" step="0.25" min="0" x-model="lineDraft.hours"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost Rate / hr</label>
                                <input type="number" step="0.01" min="0" x-model="lineDraft.hourly_cost_rate"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                    </template>

                    {{-- Material / Equipment / Sub / Other — qty + unit cost --}}
                    <template x-if="lineDraft.line_type !== 'labor'">
                        <div class="md:col-span-2 grid grid-cols-4 gap-3">
                            <template x-if="lineDraft.line_type === 'material'">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Material</label>
                                    <select x-model="lineDraft.material_id" @change="onMaterialChange()"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option value="">— optional —</option>
                                        @foreach($materials as $m)
                                            <option value="{{ $m->id }}" data-unit="{{ $m->unit_of_measure }}" data-cost="{{ $m->unit_cost }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>
                            <template x-if="lineDraft.line_type === 'equipment'">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Equipment</label>
                                    <select x-model="lineDraft.equipment_id" @change="onEquipmentChange()"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option value="">— optional —</option>
                                        @foreach($equipment as $e)
                                            <option value="{{ $e->id }}" data-rate="{{ $e->daily_rate }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Quantity</label>
                                <input type="number" step="0.01" min="0" x-model="lineDraft.quantity"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Unit Cost</label>
                                <input type="number" step="0.01" min="0" x-model="lineDraft.unit_cost"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Unit</label>
                                <input type="text" x-model="lineDraft.unit" placeholder="ea, sf, lf, day…"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                    </template>

                    {{-- Cost code + cost type + markup % (all line types) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost Code</label>
                        <select x-model="lineDraft.cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— optional —</option>
                            @foreach($costCodes as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost Type</label>
                        <select x-model="lineDraft.cost_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— optional —</option>
                            @foreach($costTypes as $ct)
                                <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Markup % (decimal)</label>
                        <input type="number" step="0.01" min="0" x-model="lineDraft.markup_percent" placeholder="e.g. 0.15 for 15%"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-[11px] text-gray-400 mt-1">Leave blank to use the client's default markup for this type.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Notes</label>
                        <textarea x-model="lineDraft.notes" rows="2"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" @click="openLineModalFlag = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                    <button type="button" @click="saveLine()" :disabled="!lineDraft.description"
                            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-lg">
                        Save Line
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Line Items</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.estimates.lines.import.template', [$project, $estimate]) }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-3 py-2 rounded-lg shadow-sm border border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Template
                </a>
                <button type="button" onclick="openModal('importEstLinesModal')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-3 py-2 rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/></svg>
                    Import CSV
                </button>
            </div>
        </div>

        <!-- Import Modal -->
        <div id="importEstLinesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('importEstLinesModal')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Import Estimate Lines</h3>
                    <button type="button" onclick="closeModal('importEstLinesModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('projects.estimates.lines.import', [$project, $estimate]) }}" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-900">
                        <p class="font-semibold mb-1">CSV format</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Columns: cost_code, description, quantity, unit, unit_cost, labor_hours</li>
                            <li>cost_code must match an existing Phase Code.</li>
                            <li>Amount is calculated as quantity × unit_cost.</li>
                        </ul>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CSV File *</label>
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" onclick="closeModal('importEstLinesModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Phase Code</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Cost Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Qty</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Unit</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Unit Cost</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Labor Hrs</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($estimate->lines as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $item->costCode?->code ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->costType?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->unit }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 font-semibold">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $item->labor_hours ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-center space-x-2">
                                <button type="button" onclick="confirmDelete('{{ route('projects.estimates.remove-line', [$project, $item]) }}', null, window.location.href)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">No line items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">TOTAL:</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">${{ number_format($estimate->lines->sum('amount') ?? 0, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Add Line Item Button -->
    <div class="mb-6">
        <button onclick="openAddLineModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Line Item
        </button>
    </div>

    <!-- Status Badge -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Status</h3>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'sent' => 'bg-blue-100 text-blue-800',
                        'accepted' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                    ];
                    $statusClass = $statusColors[$estimate->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                    {{ ucfirst($estimate->status) }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Estimate Modal -->
<div id="editEstimateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Edit Estimate</h2>
            <button type="button" onclick="closeModal('editEstimateModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editEstimateForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('editEstimateModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitEditEstimate()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Line Item Modal -->
<div id="addLineModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Add Line Item</h2>
        <form id="addLineForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Phase Code *</label>
                <select name="cost_code_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select Phase Code</option>
                    @foreach ($costCodes ?? [] as $code)
                        <option value="{{ $code->id }}">{{ $code->code }} - {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cost Type</label>
                <select name="cost_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">— None —</option>
                    @foreach ($costTypes ?? [] as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0.00">
                <p class="text-[11px] text-gray-500 mt-1">Enter the estimated dollar amount for this line.</p>
            </div>
            <details class="mb-4">
                <summary class="text-sm font-medium text-blue-700 cursor-pointer">Advanced: break down by Qty × Unit Cost</summary>
                <div class="grid grid-cols-3 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Qty</label>
                        <input type="number" name="quantity" step="0.01" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Unit</label>
                        <input type="text" name="unit" placeholder="ea, lf, sf…" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Unit Cost ($)</label>
                        <input type="number" name="unit_cost" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <p class="text-[11px] text-gray-500 mt-2">If Qty and Unit Cost are both filled, they override the Amount above.</p>
            </details>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Labor Hours (Manhours)</label>
                <input type="number" name="labor_hours" step="0.5" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="submitAddLine()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">Add Item</button>
                <button type="button" onclick="closeModal('addLineModal')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function editEstimateShow() {
    $.get('{{ route("projects.estimates.edit", [$project, $estimate]) }}', function(d) {
        var f = document.getElementById('editEstimateForm');
        f.querySelector('[name="name"]').value = d.name || '';
        f.querySelector('[name="description"]').value = d.description || '';
        f.querySelector('[name="status"]').value = d.status || 'draft';
        openModal('editEstimateModal');
    });
}

function submitEditEstimate() {
    submitForm('editEstimateForm', '{{ route("projects.estimates.update", [$project, $estimate]) }}', 'PUT', null, 'editEstimateModal');
}

function openAddLineModal() {
    document.getElementById('addLineForm').reset();
    openModal('addLineModal');
}

function submitAddLine() {
    var form = document.getElementById('addLineForm');
    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { data[k] = v; });

    $.ajax({
        url: '{{ route("projects.estimates.add-line", [$project, $estimate]) }}',
        type: 'POST',
        data: data,
        success: function(res) {
            closeModal('addLineModal');
            window.location.reload();
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                Swal.fire({icon: 'error', title: 'Validation Error', html: msg});
            } else {
                Swal.fire({icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error adding line item'});
            }
        }
    });
}

/* ─── Estimating Phase 1 — sections + typed lines builder ───────────── */
const EST_BASE = window.BASE_URL + '/projects/{{ $project->id }}/estimates/{{ $estimate->id }}';

function estimateBuilder(estimateId) {
    return {
        // ── State ──
        openSectionModal:  false,
        openLineModalFlag: false,
        newSectionName:    '',
        newSectionDescription: '',
        lineDraft: this.blankLine(),
        totals: {
            total_cost:     {{ (float) $estimate->total_cost }},
            total_price:    {{ (float) $estimate->total_price }},
            margin_percent: {{ (float) $estimate->margin_percent }},
        },

        // ── Helpers ──
        fmt(n) { return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        blankLine() {
            return {
                line_type: 'other', section_id: null, description: '',
                cost_code_id: '', cost_type_id: '',
                craft_id: '', hours: '', hourly_cost_rate: '',
                material_id: '', equipment_id: '',
                quantity: '', unit: '', unit_cost: '',
                markup_percent: '', notes: '',
            };
        },

        openLineModal(sectionId, type) {
            this.lineDraft = this.blankLine();
            this.lineDraft.section_id = sectionId;
            this.lineDraft.line_type = type;
            this.openLineModalFlag = true;
        },

        // Pre-fill cost rate from craft selection (and similar for material/equipment).
        onCraftChange() {
            const opt = document.querySelector('select[x-model="lineDraft.craft_id"] option[value="' + this.lineDraft.craft_id + '"]');
            if (opt && opt.dataset.rate) this.lineDraft.hourly_cost_rate = opt.dataset.rate;
        },
        onMaterialChange() {
            const opt = document.querySelector('select[x-model="lineDraft.material_id"] option[value="' + this.lineDraft.material_id + '"]');
            if (opt) {
                if (opt.dataset.cost) this.lineDraft.unit_cost = opt.dataset.cost;
                if (opt.dataset.unit) this.lineDraft.unit = opt.dataset.unit;
            }
        },
        onEquipmentChange() {
            const opt = document.querySelector('select[x-model="lineDraft.equipment_id"] option[value="' + this.lineDraft.equipment_id + '"]');
            if (opt && opt.dataset.rate) this.lineDraft.unit_cost = opt.dataset.rate;
        },

        // ── API calls ──
        async saveSection() {
            if (!this.newSectionName.trim()) return;
            const r = await fetch(EST_BASE + '/sections', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ name: this.newSectionName.trim(), description: this.newSectionDescription }),
            });
            if (!r.ok) { Toast.fire({icon:'error', title:'Save failed'}); return; }
            this.openSectionModal = false;
            location.reload();
        },

        async saveLine() {
            if (!this.lineDraft.description.trim()) return;
            const payload = JSON.parse(JSON.stringify(this.lineDraft));
            // strip empty strings → backend expects null
            Object.keys(payload).forEach(k => { if (payload[k] === '' || payload[k] === null) delete payload[k]; });
            const r = await fetch(EST_BASE + '/lines', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(payload),
            });
            const body = await r.json();
            if (!r.ok) { Toast.fire({icon:'error', title: body.message || 'Save failed'}); return; }
            Toast.fire({icon:'success', title:'Line added'});
            this.openLineModalFlag = false;
            location.reload();
        },
    };
}

function confirmDeleteSection(sectionId) {
    Swal.fire({
        title: 'Remove this section?',
        text: 'Lines under it will move to the Unsectioned bucket — they\'re not deleted.',
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Remove section', confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(EST_BASE + '/sections/' + sectionId, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        }).then(() => location.reload());
    });
}

/* ─── Phase 2 + 3 controls ─────────────────────────────────────────── */

function markEstimateSent() {
    fetch(EST_BASE + '/mark-sent', {
        method: 'POST',
        headers: { 'Accept': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    }).then(r => r.json()).then(b => {
        Toast.fire({ icon: b.success ? 'success' : 'error', title: b.message || 'Done' });
        if (b.success) setTimeout(() => location.reload(), 600);
    });
}

function recordEstimateResponse(response) {
    Swal.fire({
        title: 'Mark estimate as ' + response + '?',
        icon: response === 'accepted' ? 'success' : 'warning',
        showCancelButton: true,
        confirmButtonColor: response === 'accepted' ? '#059669' : '#d97706',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(EST_BASE + '/response', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ response }),
        }).then(r => r.json()).then(b => {
            Toast.fire({ icon: b.success ? 'success' : 'error', title: b.message || 'Done' });
            if (b.success) setTimeout(() => location.reload(), 600);
        });
    });
}

function confirmConvertEstimate() {
    Swal.fire({
        title: 'Accept & convert to project?',
        html: 'This will:<br>• Mark the estimate as <b>converted</b><br>• Create budget lines from the estimated work<br>• Copy client billable rates onto the project<br><br>You can re-convert if you edit the estimate later.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        confirmButtonText: 'Convert',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(EST_BASE + '/convert', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({}),
        }).then(r => r.json()).then(b => {
            if (!b.success) {
                Swal.fire({ icon: 'error', title: 'Conversion failed', text: b.message });
                return;
            }
            Swal.fire({
                icon: 'success',
                title: 'Converted',
                html: b.message + '<br><a href="' + b.project_url + '" class="text-blue-600 underline">Open project</a>',
                confirmButtonText: 'Open project',
            }).then(() => location.href = b.project_url);
        });
    });
}

function confirmDeleteLine(lineId) {
    Swal.fire({
        title: 'Delete this line?', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(window.BASE_URL + '/projects/{{ $project->id }}/estimates/lines/' + lineId, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        }).then(() => location.reload());
    });
}
</script>
@endpush
@endsection
