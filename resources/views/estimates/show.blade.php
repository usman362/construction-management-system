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

        {{-- ── Totals strip ──
             2026-05-01 (KH cost controller): "When creating an estimate –
             the total needs to be visible on that screen as lines are added."
             Made the totals strip STICKY so it stays pinned to the top of
             the viewport while the user scrolls down to add sections/lines.
             Updates live after each save (the page reloads after Save Line,
             so the totals re-pull from the model — which is correct).
        --}}
        <div class="grid grid-cols-3 gap-3 mb-6 sticky top-0 z-30 bg-white py-3 -mx-2 px-2 shadow-sm">
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
            <div class="flex items-center gap-2">
                {{-- 2026-05-12 (Brenda — Phase 6): AI Estimate Builder.
                     Paste a scope of work, AI returns suggested sections +
                     line items grounded in the catalog + past pricing. --}}
                <button type="button" onclick="document.getElementById('aiSuggestModal').classList.remove('hidden')"
                        class="relative inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 hover:from-purple-700 hover:via-fuchsia-700 hover:to-pink-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow"
                        title="Paste a scope of work — AI suggests sections + line items.">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    AI Suggest from Scope
                    <span class="absolute -top-1 -right-1 bg-yellow-400 text-[9px] font-black text-purple-900 px-1.5 py-0.5 rounded-full shadow">AI</span>
                </button>
                <button type="button" @click="newSectionName = ''; openSectionModal = true"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Section
                </button>
            </div>
        </div>

        {{-- ───── AI Estimate Builder modal ─────
             3 stages: scope (paste textarea) → working (spinner) → review
             (sections + line items with checkboxes). User checks the lines
             they want, picks a target section (existing or new), commits.
        --}}
        <div id="aiSuggestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay"
             x-data="aiEstimateBuilder()" x-init="init()"
             onclick="if(event.target===this) this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 max-h-[92vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 text-white">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                        <div>
                            <h3 class="text-lg font-bold">AI Estimate Builder</h3>
                            <p class="text-xs text-purple-100" x-text="stage === 'review' ? summary : 'Paste a scope of work — AI returns sections + line items.'"></p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('aiSuggestModal').classList.add('hidden')" class="text-purple-100 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- STAGE 1: paste scope --}}
                <div x-show="stage === 'scope'" class="p-6 flex-1 overflow-y-auto">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Paste the scope of work / RFP excerpt</label>
                    <textarea x-model="scope" rows="12"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
                              placeholder="Example: Replace 200 LF of 8-inch carbon steel pipe between Tank 4 and the cooling tower. Includes demolition of existing pipe, new pipe supply &amp; install, x-ray of 12 welds, hydrostatic test. Crew of 3 fitters + 1 welder. Standard scaffold rental for 1 week. PPE included."></textarea>
                    <p class="text-xs text-gray-500 mt-1">The more specific the scope, the better the suggestions. Include quantities, materials, and crew counts where you can.</p>
                    <div class="mt-4 flex items-center justify-between gap-2">
                        <span x-show="error" class="text-sm text-rose-700" x-text="error"></span>
                        <button type="button" @click="run()" :disabled="working || !scope || scope.length < 20"
                                class="ml-auto bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            <span x-show="!working">✨ Suggest line items</span>
                            <span x-show="working">Thinking…</span>
                        </button>
                    </div>
                </div>

                {{-- STAGE 2: review + selectively commit --}}
                <div x-show="stage === 'review'" class="p-6 flex-1 overflow-y-auto">
                    <p class="text-sm text-gray-600 mb-4" x-text="'AI suggested ' + totalLines + ' line(s) across ' + sections.length + ' section(s). Check the ones you want to add, then pick a target section per group below.'"></p>

                    <template x-for="(sec, si) in sections" :key="si">
                        <div class="mb-5 border border-gray-200 rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-2 border-b border-gray-200">
                                <div>
                                    <strong class="text-sm" x-text="sec.name"></strong>
                                    <span class="text-xs text-gray-500" x-text="'(' + sec.lines.length + ' lines)'"></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <label>Add to:</label>
                                    <select x-model="sec.target_section_id" class="border border-gray-300 rounded px-2 py-1 text-xs">
                                        <option value="">— New section: <span x-text="sec.name"></span> —</option>
                                        @foreach($estimate->sections as $existing)
                                            <option value="{{ $existing->id }}">{{ $existing->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <table class="w-full text-xs">
                                <thead class="text-gray-500 border-b">
                                    <tr>
                                        <th class="px-2 py-1 w-8"><input type="checkbox" @change="toggleAllInSection(si, $event.target.checked)"></th>
                                        <th class="text-left px-2">Description</th>
                                        <th class="text-left px-2">Cost Code</th>
                                        <th class="text-right px-2">Qty</th>
                                        <th class="text-left px-2">Unit</th>
                                        <th class="text-right px-2">Unit $</th>
                                        <th class="text-right px-2">Hrs</th>
                                        <th class="text-right px-2">Total</th>
                                        <th class="text-center px-2">Conf.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(l, li) in sec.lines" :key="li">
                                        <tr class="border-b border-gray-100">
                                            <td class="px-2 py-1"><input type="checkbox" x-model="l._sel"></td>
                                            <td class="px-2"><input type="text" x-model="l.description" class="w-full border-0 bg-transparent text-xs"></td>
                                            <td class="px-2 text-gray-500" x-text="l.cost_code || '—'"></td>
                                            <td class="px-2 text-right"><input type="number" step="0.01" x-model.number="l.quantity" class="w-16 border-0 bg-transparent text-xs text-right"></td>
                                            <td class="px-2" x-text="l.unit"></td>
                                            <td class="px-2 text-right"><input type="number" step="0.01" x-model.number="l.unit_cost" class="w-20 border-0 bg-transparent text-xs text-right"></td>
                                            <td class="px-2 text-right"><input type="number" step="0.01" x-model.number="l.hours" class="w-14 border-0 bg-transparent text-xs text-right"></td>
                                            <td class="px-2 text-right font-semibold" x-text="'$' + ((l.quantity || 0) * (l.unit_cost || 0)).toFixed(2)"></td>
                                            <td class="px-2 text-center">
                                                <span class="inline-block w-2 h-2 rounded-full"
                                                      :class="l.confidence >= 0.8 ? 'bg-emerald-500' : (l.confidence >= 0.5 ? 'bg-amber-500' : 'bg-rose-500')"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                {{-- Footer actions --}}
                <div class="flex items-center justify-between gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <span class="text-xs text-gray-500" x-show="stage === 'review'" x-text="selectedCount + ' of ' + totalLines + ' lines selected · $' + selectedTotal.toFixed(0) + ' total'"></span>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" x-show="stage === 'review'" @click="stage = 'scope'" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">Back</button>
                        <button type="button" onclick="document.getElementById('aiSuggestModal').classList.add('hidden')" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">Cancel</button>
                        <button type="button" x-show="stage === 'review'" @click="commit()" :disabled="committing || selectedCount === 0"
                                class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-lg">
                            <span x-show="!committing">Add <span x-text="selectedCount"></span> line(s) to estimate</span>
                            <span x-show="committing">Adding…</span>
                        </button>
                    </div>
                </div>
            </div>
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
                        {{-- 2026-05-31 (KH red tab): "Total cost should also
                             include the difference between Burden Cost and
                             Billable rate." Show the margin $ (price - cost)
                             inline so the spread is visible at a glance. --}}
                        @php $sec_margin = (float) $section->price_amount - (float) $section->cost_amount; @endphp
                        <span class="{{ $sec_margin >= 0 ? 'text-emerald-700' : 'text-red-600' }}">Margin: <strong>${{ number_format($sec_margin, 2) }}</strong></span>
                        <button type="button"
                                onclick="event.preventDefault(); event.stopPropagation(); confirmDeleteSection({{ $section->id }})"
                                class="text-red-600 hover:text-red-800 text-xs">Remove section</button>
                    </div>
                </summary>

                {{-- 2026-05-23 (KH WBS sheet): table reshaped to match her
                     spreadsheet layout exactly — Phase Code | Cost Type |
                     Description | Quote | Freight | Tax | Cost | Billable |
                     Mark-Up % | Mark-Up $ | Billable Total. Section totals
                     strip appears BOTH at top (header) and bottom (tfoot).
                     Inline-edit / TAB-to-add still on the roadmap; for now
                     entries go through the "+ Add Line Item" modal which
                     also takes the same WBS columns. --}}
                @if($section->lines->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-gray-400">No lines in this section yet.</div>
                @else
                    @php
                        // Pre-compute section totals once for both top + bottom strips.
                        $sec_quote   = (float) $section->lines->sum('quote_amount');
                        $sec_freight = (float) $section->lines->sum('freight_amount');
                        $sec_tax     = (float) $section->lines->sum('tax_amount');
                        $sec_cost    = (float) $section->lines->sum('cost_amount');
                        $sec_markup  = (float) $section->lines->sum('markup_amount');
                        $sec_bill    = (float) $section->lines->sum('price_amount');
                    @endphp
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white border-b border-gray-100">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-2 py-2 text-left">Phase Code</th>
                                <th class="px-2 py-2 text-left">Cost Type</th>
                                <th class="px-2 py-2 text-left">Description</th>
                                <th class="px-2 py-2 text-right">Quote</th>
                                <th class="px-2 py-2 text-right">Freight</th>
                                <th class="px-2 py-2 text-right">Tax</th>
                                <th class="px-2 py-2 text-right">Cost $</th>
                                <th class="px-2 py-2 text-center" title="Pass through to client?">Bill?</th>
                                <th class="px-2 py-2 text-right">Mark-Up %</th>
                                <th class="px-2 py-2 text-right">Mark-Up $</th>
                                <th class="px-2 py-2 text-right">Billable Total</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                            {{-- Top totals strip — totals visible at a glance before scrolling --}}
                            <tr class="bg-blue-50 text-blue-900 font-semibold text-xs">
                                <td class="px-2 py-1.5" colspan="3">Section totals</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_quote, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_freight, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_tax, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_cost, 2) }}</td>
                                <td class="px-2 py-1.5"></td>
                                <td class="px-2 py-1.5"></td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_markup, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_bill, 2) }}</td>
                                <td class="px-2 py-1.5"></td>
                            </tr>
                        </thead>
                        {{-- 2026-05-23 (Brenda + KH): every cell is now an inline
                             editable input/select. TAB navigates left-to-right
                             through the row. Each input auto-saves to the
                             existing updateLine endpoint on blur (or after
                             a 600ms debounce on number typing). Spreadsheet
                             feel without a click-to-enter-edit-mode step. --}}
                        <tbody class="divide-y divide-gray-100">
                            @foreach($section->lines as $line)
                                @php
                                    $isLabor = $line->line_type === 'labor';
                                @endphp
                                <tr class="hover:bg-blue-50/40 {{ $line->is_billable === false ? 'bg-gray-50' : '' }}"
                                    x-data="wbsRow({{ $line->id }})">
                                    <td class="px-1 py-1">
                                        <select x-model="row.cost_code_id" @change="save()" class="w-full border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-xs font-mono bg-transparent">
                                            <option value="">—</option>
                                            @foreach($costCodes as $cc)
                                                <option value="{{ $cc->id }}" @selected($line->cost_code_id === $cc->id)>{{ $cc->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select x-model="row.cost_type_id" @change="save()" class="w-full border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-xs bg-transparent">
                                            <option value="">—</option>
                                            @foreach($costTypes as $ct)
                                                <option value="{{ $ct->id }}" @selected($line->cost_type_id === $ct->id)>{{ $ct->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" x-model="row.description" @blur="save()"
                                               value="{{ $line->description }}"
                                               class="w-full border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-sm bg-transparent">
                                        @if($isLabor)
                                            <div class="text-[10px] text-gray-500 mt-0.5 flex flex-wrap gap-x-3 px-2">
                                                <span>ST:
                                                    <input type="number" step="0.25" min="0" x-model="row.hours" @blur="save()" value="{{ $line->hours }}" class="w-14 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-1 text-right text-[11px] bg-transparent">
                                                    hrs @ $<input type="number" step="0.01" min="0" x-model="row.hourly_cost_rate" @blur="save()" value="{{ $line->hourly_cost_rate }}" class="w-16 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-1 text-right text-[11px] bg-transparent">/hr
                                                </span>
                                                <span>OT:
                                                    <input type="number" step="0.25" min="0" x-model="row.ot_hours" @blur="save()" value="{{ $line->ot_hours }}" class="w-14 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-1 text-right text-[11px] bg-transparent">
                                                    hrs @ $<input type="number" step="0.01" min="0" x-model="row.ot_hourly_cost_rate" @blur="save()" value="{{ $line->ot_hourly_cost_rate }}" class="w-16 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-1 text-right text-[11px] bg-transparent">/hr
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" step="0.01" min="0" x-model="row.quote_amount" @blur="save()" value="{{ $line->quote_amount }}" placeholder="0.00"
                                               class="w-24 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-sm text-right bg-transparent">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" step="0.01" min="0" x-model="row.freight_amount" @blur="save()" value="{{ $line->freight_amount }}" placeholder="0.00"
                                               class="w-20 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-sm text-right bg-transparent">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" step="0.01" min="0" x-model="row.tax_amount" @blur="save()" value="{{ $line->tax_amount }}" placeholder="0.00"
                                               class="w-20 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-sm text-right bg-transparent">
                                    </td>
                                    <td class="px-2 py-2 text-right font-semibold text-gray-900">${{ number_format((float) $line->cost_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-center">
                                        <input type="checkbox" x-model="row.is_billable" @change="save()" @if($line->is_billable !== false) checked @endif
                                               title="Billable to client">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" step="0.1" min="0" max="500" x-model="row.markup_percent_display" @blur="save()" value="{{ number_format(((float) $line->markup_percent) * 100, 1) }}" placeholder="0"
                                               class="w-16 border border-transparent hover:border-gray-300 focus:border-blue-500 rounded px-2 py-1 text-sm text-right bg-transparent">
                                        <span class="text-[10px] text-gray-400">%</span>
                                    </td>
                                    <td class="px-2 py-2 text-right text-gray-700">${{ number_format((float) $line->markup_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-right font-bold text-blue-700">${{ number_format((float) $line->price_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-right whitespace-nowrap">
                                        <span x-show="status==='saving'" class="text-[10px] text-gray-400">saving…</span>
                                        <span x-show="status==='saved'"  class="text-[10px] text-emerald-600">✓</span>
                                        <span x-show="status==='error'"  class="text-[10px] text-rose-600" :title="errorMsg">⚠</span>
                                        <button type="button" onclick="confirmDeleteLine({{ $line->id }})" class="text-red-600 hover:text-red-800 text-xs ml-1">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            {{-- Bottom totals strip — mirrors the top one --}}
                            <tr class="bg-blue-50 text-blue-900 font-semibold text-xs border-t-2 border-blue-200">
                                <td class="px-2 py-1.5" colspan="3">Section totals</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_quote, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_freight, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_tax, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_cost, 2) }}</td>
                                <td class="px-2 py-1.5"></td>
                                <td class="px-2 py-1.5"></td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_markup, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">${{ number_format($sec_bill, 2) }}</td>
                                <td class="px-2 py-1.5"></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
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
        <div id="importEstLinesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="importEstLinesModal">
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

    <!-- Add Line Item + Labor buttons -->
    <div class="mb-6 flex flex-wrap gap-2">
        <button onclick="openAddLineModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Line Item
        </button>
        {{-- 2026-05-23 (KH ESTIMATE-LABOR tab): Labor tile — auto computes
             ST/OT split from Craft × Qty × Hrs/Day × Duration. --}}
        <button onclick="openLaborModal()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Add Labor (Craft × Qty × Hrs × Days)
        </button>
    </div>

    {{-- ───── Labor builder modal (KH 2026-05-23) ─────
         Pick a craft + qty + hrs/day + duration → live preview shows
         Total Hrs, ST/OT split, ST $, OT $. Submit creates 1-2 labor
         lines on the estimate (one for ST, one for OT if any). --}}
    <div id="laborModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full mx-4 max-h-[92vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">Add Labor</h2>
            <p class="text-xs text-gray-500 mb-4">Pick a craft and the system computes total hours + ST/OT split (40 hrs/wk per person max) + cost & billable using the craft's hourly rates.</p>
            <form id="laborForm" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Craft *</label>
                        <select name="craft_id" id="lab_craft" required oninput="laborRecalc()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">— Pick a craft —</option>
                            @foreach($crafts ?? [] as $c)
                                <option value="{{ $c->id }}"
                                        data-rate="{{ (float) $c->base_hourly_rate }}"
                                        data-otmult="{{ (float) ($c->overtime_multiplier ?? 1.5) }}"
                                        data-bill="{{ (float) $c->billable_rate }}"
                                        data-otbill="{{ (float) ($c->ot_billable_rate ?? $c->billable_rate * ($c->overtime_multiplier ?? 1.5)) }}">
                                    {{ $c->code }} — {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        {{-- 2026-05-23 (Brenda): show both rates explicitly so it's
                             clear that COST = what we pay, BILLABLE = what we
                             bill the client. Both come from the project's
                             Billable Rates page (or the craft master if no
                             project-level override exists). --}}
                        <div id="lab_craft_rates" class="hidden mt-1 text-[11px] text-gray-600 leading-relaxed">
                            Cost rate (our cost): <span id="lab_craft_cost" class="font-semibold text-gray-900">—</span>/hr ST,
                            <span id="lab_craft_cost_ot" class="font-semibold text-gray-900">—</span>/hr OT
                            <br>Billable rate (to client): <span id="lab_craft_bill" class="font-semibold text-blue-700">—</span>/hr ST,
                            <span id="lab_craft_bill_ot" class="font-semibold text-blue-700">—</span>/hr OT
                            <br><span id="lab_craft_warn" class="hidden text-rose-600">⚠ Rate is $0 — set it on the project's Billable Rates page.</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Classification (optional)</label>
                        <input type="text" name="classification" placeholder="e.g. Foreman, Apprentice"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Qty (workers) *</label>
                        <input type="number" name="qty" id="lab_qty" min="1" max="500" value="1" required oninput="laborRecalc()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hrs/Day *</label>
                        {{-- 2026-05-23 (Brenda bug): step="0.5" combined with min="0.25"
                             made round numbers like 10 invalid (browser only allows
                             0.25, 0.75, 1.25, …). Switched to step="0.25" min="0"
                             so 10, 11.5, 8 all work. Same fix on Duration below. --}}
                        <input type="number" name="hrs_per_day" id="lab_hpd" step="0.25" min="0" max="24" value="10" required oninput="laborRecalc()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Duration (days) *</label>
                        <input type="number" name="duration_days" id="lab_dur" step="1" min="1" max="730" value="14" required oninput="laborRecalc()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mark-Up %</label>
                    <input type="number" name="markup_percent" id="lab_markup" step="0.5" min="0" max="500" value="10" oninput="laborRecalc()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    <p class="text-[10px] text-gray-500 mt-1">Enter 10 for 10%.</p>
                </div>

                {{-- Live preview --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs">
                    <div class="font-semibold text-amber-900 uppercase tracking-wide text-[10px] mb-2">Live calculation</div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                        <div>Total Hours:        <span id="lab_total"  class="font-bold text-gray-900">0</span></div>
                        <div>Weeks:              <span id="lab_weeks"  class="font-bold text-gray-900">0</span></div>
                        <div>Max ST (40 × wks × qty): <span id="lab_max_st" class="font-bold text-gray-900">0</span></div>
                        <div>Actual OT:          <span id="lab_ot"     class="font-bold text-amber-700">0</span></div>
                        <div>ST $:               <span id="lab_st_cost" class="font-bold text-gray-900">$0</span></div>
                        <div>OT $:               <span id="lab_ot_cost" class="font-bold text-amber-700">$0</span></div>
                        <div class="col-span-2 border-t border-amber-300 pt-1 mt-1">
                            Total Cost: <span id="lab_total_cost" class="font-bold text-gray-900">$0</span>
                            &nbsp;·&nbsp; Billable: <span id="lab_billable" class="font-bold text-blue-700">$0</span>
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_billable" value="1" checked class="rounded border-gray-300">
                    Billable to client
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="submitLaborBundle()" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded">
                        Create Labor Line(s)
                    </button>
                    <button type="button" onclick="closeModal('laborModal')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
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
            {{-- 2026-05-23 (KH WBS sheet): Cost build-up by Quote + Freight + Tax.
                 Live preview shows Cost (sum) and Billable Total (Cost × (1 + markup))
                 underneath. Each field is optional — for a labor-only line you can
                 still leave these blank and just fill Labor Hours below. --}}
            <div class="mb-4 grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Quote ($)</label>
                    <input type="number" name="quote_amount" step="0.01" min="0" placeholder="0.00"
                           oninput="addLineRecalc()" id="al_quote"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Freight ($)</label>
                    <input type="number" name="freight_amount" step="0.01" min="0" placeholder="0.00"
                           oninput="addLineRecalc()" id="al_freight"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tax ($)</label>
                    <input type="number" name="tax_amount" step="0.01" min="0" placeholder="0.00"
                           oninput="addLineRecalc()" id="al_tax"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                </div>
            </div>
            <div class="mb-4 grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mark-Up %</label>
                    <input type="number" name="markup_percent" step="0.01" min="0" max="500" placeholder="0"
                           oninput="addLineRecalc()" id="al_markup"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    <p class="text-[10px] text-gray-500 mt-1">Enter 10 for 10%</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-2">
                    <div class="text-[10px] uppercase tracking-wide text-blue-700 font-semibold">Live preview</div>
                    <div class="text-xs text-gray-700 mt-0.5">Cost: <span id="al_cost_preview" class="font-bold text-gray-900">$0.00</span></div>
                    <div class="text-xs text-gray-700">Mark-Up $: <span id="al_markup_preview" class="font-bold text-gray-900">$0.00</span></div>
                    <div class="text-xs text-blue-800 mt-1">Billable Total: <span id="al_billable_preview" class="font-bold">$0.00</span></div>
                </div>
            </div>
            <details class="mb-4">
                <summary class="text-sm font-medium text-blue-700 cursor-pointer">Alternative: simple Amount or Qty × Unit Cost</summary>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Amount ($)</label>
                    <input type="number" name="amount" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
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
                <p class="text-[11px] text-gray-500 mt-2">Use these only if you don't have a Quote/Freight/Tax breakdown.</p>
            </details>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Labor Hours (Manhours)</label>
                <input type="number" name="labor_hours" step="0.5" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            {{-- 2026-05-23 (KH WBS sheet): per-line Billable checkbox.
                 Default checked because most lines pass through to the
                 client. Uncheck for things like Misc Consumables that
                 we eat on the cost side but never bill. The hidden
                 is_billable_present field tells the server "this form
                 actually rendered the checkbox" so an unchecked box
                 isn't confused with the field being absent. --}}
            <div class="mb-6 flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg p-3">
                <input type="hidden" name="is_billable_present" value="1">
                <input type="checkbox" id="addline_is_billable" name="is_billable" value="1" checked
                       class="rounded border-gray-300">
                <label for="addline_is_billable" class="text-sm font-semibold text-gray-700">
                    Billable
                    <span class="text-xs font-normal text-gray-500">— uncheck if this line is a cost we eat but never bill the client</span>
                </label>
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

// 2026-05-23 (Brenda + KH): Excel-style inline-edit on the WBS table.
// Each row gets its own Alpine component that auto-saves on blur via
// the existing updateLine endpoint. TAB navigation comes free from
// native browser behavior — cells are real inputs, browser walks
// through them in DOM order. Saving state shows on the right.
function wbsRow(lineId) {
    return {
        row: {
            cost_code_id: null, cost_type_id: null, description: '',
            quote_amount: null, freight_amount: null, tax_amount: null,
            hours: null, hourly_cost_rate: null,
            ot_hours: null, ot_hourly_cost_rate: null,
            is_billable: true, markup_percent_display: null,
        },
        status: '',  // '' | 'saving' | 'saved' | 'error'
        errorMsg: '',
        timer: null,
        init() {
            // Read initial values from the rendered inputs so we don't
            // duplicate them as JS literals (avoids template-vs-runtime
            // drift if the row is re-rendered).
            const row = this.$el;
            this.row.cost_code_id = row.querySelector('[x-model="row.cost_code_id"]')?.value || '';
            this.row.cost_type_id = row.querySelector('[x-model="row.cost_type_id"]')?.value || '';
            this.row.description  = row.querySelector('[x-model="row.description"]')?.value || '';
            this.row.quote_amount   = row.querySelector('[x-model="row.quote_amount"]')?.value   || '';
            this.row.freight_amount = row.querySelector('[x-model="row.freight_amount"]')?.value || '';
            this.row.tax_amount     = row.querySelector('[x-model="row.tax_amount"]')?.value     || '';
            this.row.hours              = row.querySelector('[x-model="row.hours"]')?.value || '';
            this.row.hourly_cost_rate   = row.querySelector('[x-model="row.hourly_cost_rate"]')?.value || '';
            this.row.ot_hours           = row.querySelector('[x-model="row.ot_hours"]')?.value || '';
            this.row.ot_hourly_cost_rate= row.querySelector('[x-model="row.ot_hourly_cost_rate"]')?.value || '';
            this.row.is_billable        = row.querySelector('[x-model="row.is_billable"]')?.checked ?? true;
            this.row.markup_percent_display = row.querySelector('[x-model="row.markup_percent_display"]')?.value || '0';
        },
        save() {
            // Debounce — if multiple fields change in quick succession we
            // only fire one PUT.
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this._doSave(), 350);
        },
        async _doSave() {
            this.status = 'saving';
            const blankToNull = v => (v === '' || v === null || v === undefined) ? null : v;
            const payload = {
                cost_code_id: blankToNull(this.row.cost_code_id),
                cost_type_id: blankToNull(this.row.cost_type_id),
                description:  this.row.description || '(no description)',
                quote_amount:   blankToNull(this.row.quote_amount),
                freight_amount: blankToNull(this.row.freight_amount),
                tax_amount:     blankToNull(this.row.tax_amount),
                hours:                   blankToNull(this.row.hours),
                hourly_cost_rate:        blankToNull(this.row.hourly_cost_rate),
                ot_hours:                blankToNull(this.row.ot_hours),
                ot_hourly_cost_rate:     blankToNull(this.row.ot_hourly_cost_rate),
                is_billable:  this.row.is_billable ? 1 : 0,
                // User types markup as "10" → server expects 0.10
                markup_percent: this.row.markup_percent_display !== ''
                    ? (parseFloat(this.row.markup_percent_display) || 0) / 100
                    : null,
            };
            try {
                const r = await fetch(window.BASE_URL + '/projects/{{ $project->id }}/estimates/lines/' + lineId, {
                    method: 'PUT',
                    headers: { 'Accept':'application/json','Content-Type':'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(payload),
                });
                if (!r.ok) {
                    const b = await r.json().catch(() => ({}));
                    this.status = 'error';
                    this.errorMsg = b.errors
                        ? Object.values(b.errors).flat().join(' ')
                        : (b.message || ('HTTP ' + r.status));
                    return;
                }
                this.status = 'saved';
                // Refresh totals row after a beat — easiest is a full reload
                // since cost/markup/price are computed server-side via the
                // recalculate observer. Reload debounced so rapid edits
                // don't reload mid-typing.
                clearTimeout(window.__wbsReload);
                window.__wbsReload = setTimeout(() => location.reload(), 1200);
            } catch (e) {
                this.status = 'error';
                this.errorMsg = e.message;
            }
        },
    };
}

// 2026-05-23 (KH ESTIMATE-LABOR + Misc tab math): Labor builder.
// Live computes Total Hrs, ST/OT split (40 hrs/wk/person cap), ST $,
// OT $, total cost, billable. Submit POSTs to addLaborBundle which
// creates 1-2 EstimateLine rows server-side.
function openLaborModal() {
    document.getElementById('laborForm').reset();
    document.getElementById('lab_qty').value    = 1;
    document.getElementById('lab_hpd').value    = 10;
    document.getElementById('lab_dur').value    = 14;
    document.getElementById('lab_markup').value = 10;
    laborRecalc();
    openModal('laborModal');
}

function laborRecalc() {
    const num = (id) => parseFloat(document.getElementById(id)?.value) || 0;
    const fmt = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtH = (n) => Number(n).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });

    const craftSel = document.getElementById('lab_craft');
    const opt = craftSel?.selectedOptions?.[0];
    const stRate   = opt ? parseFloat(opt.dataset.rate   || 0) : 0;
    const otMult   = opt ? parseFloat(opt.dataset.otmult || 1.5) : 1.5;
    const otRate   = stRate * otMult;
    const billSt   = opt ? parseFloat(opt.dataset.bill   || 0) : 0;
    const billOt   = opt ? parseFloat(opt.dataset.otbill || (billSt * otMult)) : 0;

    // Show / hide the rate breakdown panel under the dropdown
    const ratesBox = document.getElementById('lab_craft_rates');
    if (opt && opt.value) {
        ratesBox.classList.remove('hidden');
        document.getElementById('lab_craft_cost').textContent    = fmt(stRate);
        document.getElementById('lab_craft_cost_ot').textContent = fmt(otRate);
        document.getElementById('lab_craft_bill').textContent    = fmt(billSt);
        document.getElementById('lab_craft_bill_ot').textContent = fmt(billOt);
        const warn = document.getElementById('lab_craft_warn');
        if (stRate === 0 || billSt === 0) warn.classList.remove('hidden');
        else warn.classList.add('hidden');
    } else {
        ratesBox.classList.add('hidden');
    }

    const qty = num('lab_qty');
    const hpd = num('lab_hpd');
    const dur = num('lab_dur');
    const weeks = dur / 7;
    const maxStTotal = 40 * weeks * qty;
    const totalHrs   = qty * hpd * dur;
    const stHrs      = Math.min(totalHrs, maxStTotal);
    const otHrs      = Math.max(0, totalHrs - maxStTotal);

    const stCost  = stHrs * stRate;
    const otCost  = otHrs * otRate;
    const totCost = stCost + otCost;

    const markupPct = num('lab_markup') / 100;
    const billable  = stHrs * billSt + otHrs * billOt;
    const billableWithMarkup = billable > 0 ? billable * (1 + markupPct) : totCost * (1 + markupPct);

    document.getElementById('lab_total').textContent      = fmtH(totalHrs)   + ' hrs';
    document.getElementById('lab_weeks').textContent      = weeks.toFixed(2);
    document.getElementById('lab_max_st').textContent     = fmtH(maxStTotal) + ' hrs';
    document.getElementById('lab_ot').textContent         = fmtH(otHrs)      + ' hrs';
    document.getElementById('lab_st_cost').textContent    = fmt(stCost);
    document.getElementById('lab_ot_cost').textContent    = fmt(otCost);
    document.getElementById('lab_total_cost').textContent = fmt(totCost);
    document.getElementById('lab_billable').textContent   = fmt(billableWithMarkup);
}

async function submitLaborBundle() {
    const form = document.getElementById('laborForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    const payload = {};
    fd.forEach((v, k) => { if (v !== '') payload[k] = v; });
    // markup % → decimal (10 → 0.10)
    if (payload.markup_percent) payload.markup_percent = (parseFloat(payload.markup_percent) || 0) / 100;
    // checkbox: present + checked → 1; not present → not billable (0)
    payload.is_billable = form.querySelector('input[name="is_billable"]').checked ? 1 : 0;

    try {
        const r = await fetch(EST_BASE + '/labor-bundle', {
            method: 'POST',
            headers: {
                'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(payload),
        });
        const body = await r.json().catch(() => ({}));
        if (!r.ok || !body.success) {
            const msg = body.message || ('HTTP ' + r.status);
            Swal.fire({ icon: 'error', title: 'Could not create labor lines', text: msg });
            return;
        }
        Toast.fire({ icon: 'success', title: body.message });
        closeModal('laborModal');
        setTimeout(() => location.reload(), 600);
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Network error', text: e.message });
    }
}

// 2026-05-23 (KH WBS): live Cost + Mark-Up + Billable Total preview as
// the user types Quote / Freight / Tax / Markup % in the Add Line modal.
function addLineRecalc() {
    const num = id => parseFloat(document.getElementById(id)?.value) || 0;
    const cost   = num('al_quote') + num('al_freight') + num('al_tax');
    const mPct   = num('al_markup') / 100;
    const markup = cost * mPct;
    const fmt    = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('al_cost_preview').textContent     = fmt(cost);
    document.getElementById('al_markup_preview').textContent   = fmt(markup);
    document.getElementById('al_billable_preview').textContent = fmt(cost + markup);
}

function submitAddLine() {
    var form = document.getElementById('addLineForm');
    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { if (v !== '') data[k] = v; });

    // 2026-05-23 (KH WBS): user types markup as percent ("10" = 10%) but
    // the server expects decimal (0.10). Convert before submit.
    if (data.markup_percent !== undefined && data.markup_percent !== '') {
        data.markup_percent = (parseFloat(data.markup_percent) || 0) / 100;
    }

    // 2026-05-23 (KH bug report — "Error adding line item"): client-side
    // sanity check so the user gets a clear message instead of a generic
    // server-side fail when they fill no money / qty / hours at all.
    var amt   = parseFloat(data.amount        || 0);
    var qty   = parseFloat(data.quantity      || 0);
    var unit  = parseFloat(data.unit_cost     || 0);
    var hrs   = parseFloat(data.labor_hours   || 0);
    var quote = parseFloat(data.quote_amount  || 0);
    var freight = parseFloat(data.freight_amount || 0);
    var tax   = parseFloat(data.tax_amount    || 0);
    var hasCost = (quote + freight + tax) > 0;
    if (amt <= 0 && (qty <= 0 || unit <= 0) && hrs <= 0 && !hasCost) {
        Swal.fire({
            icon: 'warning',
            title: 'Add at least one of: Amount, Qty × Unit Cost, or Labor Hours.',
            text: 'A line item needs a value somewhere — either a dollar amount, a quantity-times-cost, or labor hours for a craft.',
        });
        return;
    }
    var btn = form.querySelector('button[onclick="submitAddLine()"]');
    var origLabel = btn ? btn.textContent : null;
    if (btn) { btn.disabled = true; btn.textContent = 'Adding…'; }

    $.ajax({
        url: '{{ route("projects.estimates.add-line", [$project, $estimate]) }}',
        type: 'POST',
        data: data,
        success: function(res) {
            closeModal('addLineModal');
            window.location.reload();
        },
        error: function(xhr) {
            if (btn) { btn.disabled = false; btn.textContent = origLabel; }
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().map(function(e){
                    return '<li>'+ e.replace(/[<>&"']/g, '') +'</li>';
                }).join('');
                Swal.fire({icon: 'error', title: 'Please fix these fields',
                           html: '<ul style="text-align:left;margin:0 auto;display:inline-block;">' + msg + '</ul>'});
            } else {
                Swal.fire({icon: 'error', title: 'Could not save line',
                           text: xhr.responseJSON?.message || ('HTTP ' + xhr.status + ' — check the server logs.')});
            }
        }
    });
}

/* ─── Estimating Phase 1 — sections + typed lines builder ─────────────
   2026-05-01 (Brenda): "Add line on change order not working". Root cause
   was the same as the broken View Estimate button — EST_BASE depended on
   window.BASE_URL, which on production sometimes loaded after this script
   block. POSTs to /lines became POSTs to "undefined/projects/.../lines"
   → 404 → "Add Line silently does nothing". Switched to a server-rendered
   absolute URL via @json(url(...)) so it's always a real string.
*/
const EST_BASE = @json(url('/projects/' . $project->id . '/estimates/' . $estimate->id));

function estimateBuilder(estimateId) {
    return {
        // ── State ──
        openSectionModal:  false,
        openLineModalFlag: false,
        newSectionName:    '',
        newSectionDescription: '',
        // 2026-04-28 BUG FIX (Brenda): can't reference `this.blankLine()` during
        // object literal construction — `this` isn't bound to the object yet,
        // throws TypeError, Alpine catches it, and the whole component fails
        // to initialize (which is why the Add Section button stopped working).
        // Inline the same shape directly here, and `blankLine()` is still used
        // by openLineModal() / saveLine() to reset between entries.
        lineDraft: {
            line_type: 'other', section_id: null, description: '',
            cost_code_id: '', cost_type_id: '',
            craft_id: '', hours: '', hourly_cost_rate: '',
            material_id: '', equipment_id: '',
            quantity: '', unit: '', unit_cost: '',
            markup_percent: '', notes: '',
        },
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
            try {
                const r = await fetch(EST_BASE + '/sections', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: this.newSectionName.trim(), description: this.newSectionDescription }),
                });
                if (!r.ok) {
                    // 2026-05-23 (Brenda bug report): "I can't add a new section
                    // either." Old version showed generic "Save failed" with
                    // no clue. Now surfaces the real server message + status.
                    let msg = 'HTTP ' + r.status;
                    try { const j = await r.json(); if (j.message) msg = j.message; } catch (_) {}
                    Swal.fire({ icon: 'error', title: 'Could not save section', text: msg });
                    return;
                }
                this.openSectionModal = false;
                location.reload();
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Network error', text: e.message });
            }
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

// 2026-05-23 (Brenda bug report): every action button used to fail
// silently or show "Done" with no diagnostic when the server returned
// a non-2xx. Centralized error surfacing via reportAjaxError so users
// see WHAT went wrong (HTTP status, message, body excerpt) and we get
// actionable bug reports instead of "the button doesn't work."
async function reportAjaxError(response, fallbackTitle) {
    let bodyText = '';
    let parsed = null;
    try {
        bodyText = await response.text();
        parsed = JSON.parse(bodyText);
    } catch (_) { /* not JSON */ }
    const msg = parsed?.message
        || (bodyText && bodyText.length < 400 ? bodyText : null)
        || ('HTTP ' + response.status + ' — check the server logs.');
    Swal.fire({ icon: 'error', title: fallbackTitle, text: msg });
}

async function markEstimateSent() {
    try {
        const r = await fetch(EST_BASE + '/mark-sent', {
            method: 'POST',
            headers: { 'Accept': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        });
        if (!r.ok) { await reportAjaxError(r, 'Could not mark as sent'); return; }
        const b = await r.json();
        Toast.fire({ icon: 'success', title: b.message || 'Marked sent.' });
        setTimeout(() => location.reload(), 600);
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Network error', text: e.message });
    }
}

function recordEstimateResponse(response) {
    Swal.fire({
        title: 'Mark estimate as ' + response + '?',
        icon: response === 'accepted' ? 'success' : 'warning',
        showCancelButton: true,
        confirmButtonColor: response === 'accepted' ? '#059669' : '#d97706',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const resp = await fetch(EST_BASE + '/response', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ response }),
            });
            if (!resp.ok) { await reportAjaxError(resp, 'Could not record response'); return; }
            const b = await resp.json();
            Toast.fire({ icon: 'success', title: b.message || 'Done.' });
            setTimeout(() => location.reload(), 600);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: e.message });
        }
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
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const resp = await fetch(EST_BASE + '/convert', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({}),
            });
            if (!resp.ok) { await reportAjaxError(resp, 'Conversion failed'); return; }
            const b = await resp.json();
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
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: e.message });
        }
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

// ─── AI Estimate Builder (Brenda — Phase 6, 2026-05-12) ──────────────
// Paste scope → POST /projects/{p}/estimates/{e}/ai-suggest → render
// sections/lines with checkboxes. Each checked line commits via the
// existing addLine endpoint, optionally creating a new section first.
function aiEstimateBuilder() {
    return {
        stage: 'scope',
        scope: '',
        error: '',
        working: false,
        committing: false,
        summary: '',
        sections: [],

        init() {},

        get totalLines() {
            return this.sections.reduce((sum, s) => sum + s.lines.length, 0);
        },
        get selectedCount() {
            return this.sections.reduce((sum, s) => sum + s.lines.filter(l => l._sel).length, 0);
        },
        get selectedTotal() {
            return this.sections.reduce((sum, s) =>
                sum + s.lines.filter(l => l._sel).reduce((ls, l) => ls + (l.quantity || 0) * (l.unit_cost || 0), 0)
            , 0);
        },
        toggleAllInSection(si, val) {
            this.sections[si].lines.forEach(l => l._sel = val);
        },

        async run() {
            this.error = ''; this.working = true;
            try {
                const r = await fetch('{{ route("projects.estimates.ai-suggest", [$project, $estimate]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ scope: this.scope }),
                });
                const data = await r.json();
                if (!r.ok || !data.success) throw new Error(data.message || 'AI request failed');
                this.summary = data.summary || '';
                this.sections = (data.sections || []).map(sec => ({
                    name: sec.name,
                    target_section_id: '',
                    lines: sec.lines.map(l => ({ ...l, _sel: l.confidence >= 0.7 })),
                }));
                this.stage = 'review';
            } catch (e) {
                this.error = e.message;
            } finally {
                this.working = false;
            }
        },

        async commit() {
            this.committing = true;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const projectId  = {{ $project->id }};
            const estimateId = {{ $estimate->id }};
            const addLineUrl = window.BASE_URL + '/projects/' + projectId + '/estimates/' + estimateId + '/lines';
            const addSectionUrl = window.BASE_URL + '/projects/' + projectId + '/estimates/' + estimateId + '/sections';

            try {
                for (const sec of this.sections) {
                    const lines = sec.lines.filter(l => l._sel);
                    if (lines.length === 0) continue;

                    // Create a new section if the user didn't pick an existing one
                    let sectionId = sec.target_section_id || null;
                    if (!sectionId) {
                        const sr = await fetch(addSectionUrl, {
                            method: 'POST',
                            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json' },
                            body: JSON.stringify({ name: sec.name }),
                        });
                        if (!sr.ok) throw new Error('Could not create section: ' + sec.name);
                        const sj = await sr.json();
                        sectionId = sj.section?.id || sj.id;
                    }

                    for (const l of lines) {
                        const payload = {
                            section_id:   sectionId,
                            description:  l.description,
                            cost_code_id: l.cost_code_id || null,
                            quantity:     l.quantity || 1,
                            unit:         l.unit || 'EA',
                            unit_cost:    l.unit_cost || 0,
                            hours:        l.hours || 0,
                            notes:        l.notes || null,
                            line_type:    l.hours > 0 ? 'labor' : 'other',
                        };
                        const lr = await fetch(addLineUrl, {
                            method: 'POST',
                            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json' },
                            body: JSON.stringify(payload),
                        });
                        if (!lr.ok) {
                            const t = await lr.text();
                            throw new Error('Add line failed: ' + t.substring(0,200));
                        }
                    }
                }
                Toast.fire({ icon: 'success', title: this.selectedCount + ' line(s) added.' });
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                Toast.fire({ icon: 'error', title: e.message });
                this.committing = false;
            }
        },
    };
}
</script>
@endpush
@endsection
