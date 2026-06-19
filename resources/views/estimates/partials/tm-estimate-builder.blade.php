{{-- T&M Estimate Builder — mirrors Brenda's Estimate_Template.xlsx layout.
     Replaces the free-form sections builder with fixed template sections:
     3 labor categories + materials + equipment + subcontractors + summary. --}}
<div class="bg-white rounded-lg shadow mb-6" x-data="tmEstimate()" x-init="init()"
     @tm-edit.window="openEdit($event.detail)"
     @tm-totals-updated.window="applyServerTotals($event.detail)">

    {{-- ── Estimate Schedule Header ── --}}
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xl font-bold text-gray-900">T&M ESTIMATE SUMMARY</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.estimates.pdf', [$project, $estimate]) }}" class="text-xs font-semibold text-gray-600 hover:text-blue-700 border border-gray-300 rounded px-2 py-1">PDF</a>
            </div>
        </div>
        <div class="grid grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-0.5">Project Duration</label>
                <div class="flex items-center gap-1">
                    <input type="number" min="0" max="999" class="w-16 border border-gray-300 rounded px-2 py-1 text-sm text-right"
                           x-model.number="schedule.project_duration_weeks" @change="saveSchedule()">
                    <span class="text-xs text-gray-500">Weeks</span>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-0.5">Work Schedule</label>
                <input type="text" placeholder="e.g. 5-10" class="w-20 border border-gray-300 rounded px-2 py-1 text-sm"
                       x-model="schedule.work_schedule" @change="saveSchedule()">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-0.5">Field Staff Duration</label>
                <div class="flex items-center gap-1">
                    <input type="number" min="0" max="999" class="w-16 border border-gray-300 rounded px-2 py-1 text-sm text-right"
                           x-model.number="schedule.field_staff_duration_weeks" @change="saveSchedule()">
                    <span class="text-xs text-gray-500">Weeks</span>
                </div>
            </div>
            <div class="flex items-end">
                <p class="text-[10px] text-gray-400 italic">Enter Labor Rates on Labor Rates Tab</p>
            </div>
        </div>
    </div>

    {{-- ── Totals strip (sticky) ── --}}
    <div class="grid grid-cols-3 gap-3 px-6 py-3 sticky top-0 z-30 bg-white border-b border-gray-100">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Total Cost</p>
            <p class="text-xl font-bold text-gray-900" x-text="'$' + fmtM(summary.totalCost)"></p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p class="text-[10px] uppercase tracking-wide text-blue-700 font-semibold">Total Price (Billable)</p>
            <p class="text-xl font-bold text-blue-900" x-text="'$' + fmtM(summary.totalPrice)"></p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
            <p class="text-[10px] uppercase tracking-wide text-emerald-700 font-semibold">Margin</p>
            <p class="text-xl font-bold text-emerald-900"
               x-text="summary.totalPrice > 0 ? (((summary.totalPrice - summary.totalCost) / summary.totalPrice) * 100).toFixed(1) + '%' : '—'"></p>
        </div>
    </div>

    {{-- ═══════ LABOR SECTIONS ═══════ --}}
    @foreach($laborCategories as $catKey => $catLabel)
    <div class="px-4 pt-4 pb-2">
        <details open class="group">
            <summary class="flex items-center justify-between cursor-pointer py-2 px-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide">{{ $catLabel }}</h3>
                    <span class="text-xs text-indigo-600" x-text="'(' + (sectionCounts['{{ $catKey }}'] || 0) + ' lines)'"></span>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <span class="text-gray-600">Total: <strong class="text-gray-900" x-text="'$' + fmtM(sectionTotals['{{ $catKey }}']?.price || 0)"></strong></span>
                </div>
            </summary>

            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <th class="px-1.5 py-1.5 text-left font-semibold text-gray-700 w-24">Cost Code</th>
                            <th class="px-1.5 py-1.5 text-left font-semibold text-gray-700 w-14">Work Sch</th>
                            <th class="px-1.5 py-1.5 text-left font-semibold text-gray-700 min-w-[120px]">Craft</th>
                            <th class="px-1.5 py-1.5 text-left font-semibold text-gray-700 w-20">Role</th>
                            <th class="px-1.5 py-1.5 text-center font-semibold text-gray-700 w-12">Crew</th>
                            <th class="px-1.5 py-1.5 text-center font-semibold text-gray-700 w-12">Weeks</th>
                            <th class="px-1.5 py-1.5 text-center font-semibold text-gray-700 w-10">D/W</th>
                            <th class="px-1.5 py-1.5 text-center font-semibold text-gray-700 w-10">H/D</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-gray-700 w-14">Tot Hrs</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-blue-700 w-14 border-l border-blue-200 bg-blue-50">ST Hrs</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-blue-700 w-14 bg-blue-50">ST Rate</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-blue-700 w-20 bg-blue-50">ST Total</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-amber-700 w-16 border-l border-amber-200 bg-amber-50">OT Hrs</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-amber-700 w-16 bg-amber-50">OT Rate</th>
                            <th class="px-1.5 py-1.5 text-right font-semibold text-amber-700 w-24 bg-amber-50">OT Total</th>
                            <th class="px-1.5 py-1.5 text-right font-bold text-gray-900 w-28 border-l border-gray-300">Total $</th>
                            <th class="px-1.5 py-1.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($laborByCategory[$catKey] ?? [] as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="tmLaborRow({{ json_encode([
                            'id' => $line->id,
                            'cost_code_id' => $line->cost_code_id,
                            'work_schedule' => $line->work_schedule,
                            'craft_id' => $line->craft_id,
                            'description' => $line->description,
                            'role' => $line->role,
                            'crew_size' => (int) $line->crew_size,
                            'weeks' => (float) $line->weeks,
                            'days_per_week' => (int) $line->days_per_week,
                            'hours_per_day' => (float) $line->hours_per_day,
                            'hours' => (float) $line->hours,
                            'hourly_billable_rate' => (float) $line->hourly_billable_rate,
                            'ot_hours' => (float) $line->ot_hours,
                            'ot_hourly_billable_rate' => (float) $line->ot_hourly_billable_rate,
                            'premium_hours' => (float) $line->premium_hours,
                            'premium_hourly_billable_rate' => (float) $line->premium_hourly_billable_rate,
                            'price_amount' => (float) $line->price_amount,
                            'labor_category' => $catKey,
                        ]) }})">
                            <td class="px-1 py-1"><select x-model="d.cost_code_id" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded">
                                <option value="">—</option>
                                @foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach
                            </select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.work_schedule" @change="parseWorkSchedule(); recalc(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded" placeholder="5-10" title="e.g. 5-10 = 5 days/week × 10 hours/day. Auto-fills D/W and H/D."></td>
                            <td class="px-1 py-1"><select x-model="d.craft_id" @change="onCraftPick(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded">
                                <option value="">—</option>
                                @foreach($crafts as $c)<option value="{{ $c->id }}" data-rate="{{ $c->base_hourly_rate }}" data-billable="{{ $c->billable_rate ?? '' }}" data-ot-rate="{{ $c->base_ot_hourly_rate ?? '' }}" data-ot-billable="{{ $c->ot_billable_rate ?? '' }}">{{ $c->name }}</option>@endforeach
                            </select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.role" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded" placeholder="Role"></td>
                            <td class="px-1 py-1"><input type="number" min="0" max="999" x-model.number="d.crew_size" @change="recalc(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.5" x-model.number="d.weeks" @change="recalc(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" max="7" x-model.number="d.days_per_week" @change="recalc(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" max="24" step="0.5" x-model.number="d.hours_per_day" @change="recalc(); save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 text-right font-semibold text-gray-700" x-text="fmtN(totalHours())"></td>
                            {{-- ST --}}
                            <td class="px-1 py-1 bg-blue-50/50 border-l border-blue-100"><input type="number" min="0" step="1" x-model.number="d.hours" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 bg-blue-50/50"><input type="number" min="0" step="0.01" x-model.number="d.hourly_billable_rate" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 bg-blue-50/50 text-right font-semibold text-blue-800" x-text="'$' + fmtM(d.hours * d.hourly_billable_rate)"></td>
                            {{-- OT --}}
                            <td class="px-1 py-1 bg-amber-50/50 border-l border-amber-100"><input type="number" min="0" step="1" x-model.number="d.ot_hours" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 bg-amber-50/50"><input type="number" min="0" step="0.01" x-model.number="d.ot_hourly_billable_rate" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 bg-amber-50/50 text-right font-semibold text-amber-800" x-text="'$' + fmtM(d.ot_hours * d.ot_hourly_billable_rate)"></td>
                            {{-- Row total --}}
                            <td class="px-1 py-1 text-right font-bold text-gray-900 border-l border-gray-200" x-text="'$' + fmtM(rowTotal())"></td>
                            <td class="px-1 py-1 text-center whitespace-nowrap">
                                <button @click="$dispatch('tm-edit', d)" class="text-indigo-500 hover:text-indigo-700 mr-1" title="Edit line">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                </button>
                                <button @click="removeLine()" class="text-red-400 hover:text-red-600" title="Delete">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-indigo-50/50 border-t-2 border-indigo-200">
                            <td class="px-1.5 py-2 text-right font-bold text-indigo-900" colspan="8">Totals</td>
                            <td class="px-1.5 py-2 text-right font-bold text-gray-900" x-text="fmtN(sectionTotals['{{ $catKey }}']?.totalHours || 0)"></td>
                            <td class="px-1.5 py-2 text-right font-bold text-blue-900 bg-blue-50/50 border-l border-blue-100" x-text="fmtN(sectionTotals['{{ $catKey }}']?.stHours || 0)"></td>
                            <td class="px-1.5 py-2 bg-blue-50/50"></td>
                            <td class="px-1.5 py-2 bg-blue-50/50"></td>
                            <td class="px-1.5 py-2 text-right font-bold text-amber-900 bg-amber-50/50 border-l border-amber-100" x-text="fmtN(sectionTotals['{{ $catKey }}']?.otHours || 0)"></td>
                            <td class="px-1.5 py-2 bg-amber-50/50" colspan="2"></td>
                            <td class="px-1.5 py-2 text-right font-bold text-gray-900 border-l border-gray-200" x-text="'$' + fmtM(sectionTotals['{{ $catKey }}']?.price || 0)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Add labor line button --}}
            <div class="py-2 px-3">
                <button @click="addLaborLine('{{ $catKey }}')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add {{ $catLabel }} Line
                </button>
            </div>
        </details>
    </div>
    @endforeach

    {{-- ═══════ DIRECT MATERIALS ═══════ --}}
    <div class="px-4 pt-4 pb-2">
        <details open class="group">
            <summary class="flex items-center justify-between cursor-pointer py-2 px-3 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-bold text-green-900 uppercase tracking-wide">Direct Materials</h3>
                    <span class="text-xs text-green-600" x-text="'(' + (sectionCounts['material'] || 0) + ' lines)'"></span>
                </div>
                <span class="text-xs text-gray-600">Total: <strong class="text-gray-900" x-text="'$' + fmtM(sectionTotals['material']?.price || 0)"></strong></span>
            </summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <th class="px-2 py-1.5 text-left font-semibold w-24">Cost Code</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[140px]">Material</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[140px]">Description</th>
                            <th class="px-2 py-1.5 text-left font-semibold w-28">Vendor</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-24">Cost</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Freight</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Tax</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-16">Markup %</th>
                            <th class="px-2 py-1.5 text-right font-bold w-24">Total $</th>
                            <th class="px-2 py-1.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materialLines ?? [] as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="tmMaterialRow({{ json_encode([
                            'id' => $line->id,
                            'cost_code_id' => $line->cost_code_id,
                            'description' => $line->description,
                            'vendor_name' => $line->vendor_name,
                            'quote_amount' => (float) $line->quote_amount,
                            'freight_amount' => (float) $line->freight_amount,
                            'tax_amount' => (float) $line->tax_amount,
                            'markup_percent' => (float) $line->markup_percent,
                            'price_amount' => (float) $line->price_amount,
                        ]) }})">
                            <td class="px-1 py-1"><select x-model="d.cost_code_id" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded">
                                <option value="">—</option>@foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach
                            </select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.description" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 text-xs text-gray-500">{{ $line->craft?->name ?? $line->material?->name ?? '' }}</td>
                            <td class="px-1 py-1"><input type="text" x-model="d.vendor_name" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.quote_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.freight_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.tax_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="1" max="1000" :value="Math.round((d.markup_percent||0)*100)" @change="d.markup_percent = (parseFloat($event.target.value)||0)/100; save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded" title="Enter as percent (30 = 30%)"></td>
                            <td class="px-1 py-1 text-right font-bold text-gray-900" x-text="'$' + fmtM(matTotal())"></td>
                            <td class="px-1 py-1 text-center whitespace-nowrap">
                                <button @click="$dispatch('tm-edit', d)" class="text-indigo-500 hover:text-indigo-700 mr-1" title="Edit line"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                                <button @click="removeLine()" class="text-red-400 hover:text-red-600" title="Delete"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="py-2 px-3">
                <button @click="addMaterialLine()" class="text-xs font-semibold text-green-600 hover:text-green-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Material Line
                </button>
            </div>
        </details>
    </div>

    {{-- ═══════ 3rd PARTY EQUIPMENT ═══════ --}}
    <div class="px-4 pt-4 pb-2">
        <details open class="group">
            <summary class="flex items-center justify-between cursor-pointer py-2 px-3 bg-orange-50 hover:bg-orange-100 rounded-lg border border-orange-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-bold text-orange-900 uppercase tracking-wide">3rd Party Equipment</h3>
                    <span class="text-xs text-orange-600" x-text="'(' + (sectionCounts['equip_3p'] || 0) + ' lines)'"></span>
                </div>
                <span class="text-xs text-gray-600">Total: <strong class="text-gray-900" x-text="'$' + fmtM(sectionTotals['equip_3p']?.price || 0)"></strong></span>
            </summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <th class="px-2 py-1.5 text-left font-semibold w-24">Cost Code</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[160px]">Description</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-12">Qty</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-12">Duration</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-20">UOM</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Unit Rate</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Freight</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Fuel</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-16">Markup %</th>
                            <th class="px-2 py-1.5 text-right font-bold w-24">Total $</th>
                            <th class="px-2 py-1.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipmentLines3p ?? [] as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="tmEquipRow({{ json_encode([
                            'id' => $line->id,
                            'cost_code_id' => $line->cost_code_id,
                            'description' => $line->description,
                            'quantity' => (float) $line->quantity,
                            'equipment_duration' => (float) $line->equipment_duration,
                            'duration_uom' => $line->duration_uom ?? 'monthly',
                            'unit_cost' => (float) $line->unit_cost,
                            'freight_amount' => (float) $line->freight_amount,
                            'fuel_cost' => (float) $line->fuel_cost,
                            'markup_percent' => (float) $line->markup_percent,
                            'price_amount' => (float) $line->price_amount,
                            'equipment_category' => '3rd_party',
                        ]) }})">
                            <td class="px-1 py-1"><select x-model="d.cost_code_id" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"><option value="">—</option>@foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach</select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.description" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" x-model.number="d.quantity" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.5" x-model.number="d.equipment_duration" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><select x-model="d.duration_uom" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.unit_cost" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.freight_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.fuel_cost" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="1" max="1000" :value="Math.round((d.markup_percent||0)*100)" @change="d.markup_percent = (parseFloat($event.target.value)||0)/100; save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded" title="Enter as percent (30 = 30%)"></td>
                            <td class="px-1 py-1 text-right font-bold text-gray-900" x-text="'$' + fmtM(equipTotal())"></td>
                            <td class="px-1 py-1 text-center whitespace-nowrap">
                                <button @click="$dispatch('tm-edit', d)" class="text-indigo-500 hover:text-indigo-700 mr-1" title="Edit line"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                                <button @click="removeLine()" class="text-red-400 hover:text-red-600" title="Delete"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="py-2 px-3">
                <button @click="addEquipLine('3rd_party')" class="text-xs font-semibold text-orange-600 hover:text-orange-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add 3rd Party Equipment Line
                </button>
            </div>
        </details>
    </div>

    {{-- ═══════ COMPANY OWNED EQUIPMENT ═══════ --}}
    <div class="px-4 pt-4 pb-2">
        <details class="group">
            <summary class="flex items-center justify-between cursor-pointer py-2 px-3 bg-orange-50 hover:bg-orange-100 rounded-lg border border-orange-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-bold text-orange-900 uppercase tracking-wide">Company Owned Equipment</h3>
                    <span class="text-xs text-orange-600" x-text="'(' + (sectionCounts['equip_coe'] || 0) + ' lines)'"></span>
                </div>
                <span class="text-xs text-gray-600">Total: <strong class="text-gray-900" x-text="'$' + fmtM(sectionTotals['equip_coe']?.price || 0)"></strong></span>
            </summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <th class="px-2 py-1.5 text-left font-semibold w-24">Cost Code</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[160px]">Description</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-12">Qty</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-12">Duration</th>
                            <th class="px-2 py-1.5 text-center font-semibold w-20">UOM</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Unit Rate</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Freight</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-20">Fuel</th>
                            <th class="px-2 py-1.5 text-right font-bold w-24">Total $</th>
                            <th class="px-2 py-1.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipmentLinesCoe ?? [] as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="tmEquipRow({{ json_encode([
                            'id' => $line->id,
                            'cost_code_id' => $line->cost_code_id,
                            'description' => $line->description,
                            'quantity' => (float) $line->quantity,
                            'equipment_duration' => (float) $line->equipment_duration,
                            'duration_uom' => $line->duration_uom ?? 'monthly',
                            'unit_cost' => (float) $line->unit_cost,
                            'freight_amount' => (float) $line->freight_amount,
                            'fuel_cost' => (float) $line->fuel_cost,
                            'markup_percent' => (float) $line->markup_percent,
                            'price_amount' => (float) $line->price_amount,
                            'equipment_category' => 'company_owned',
                        ]) }})">
                            <td class="px-1 py-1"><select x-model="d.cost_code_id" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"><option value="">—</option>@foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach</select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.description" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" x-model.number="d.quantity" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.5" x-model.number="d.equipment_duration" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-center focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><select x-model="d.duration_uom" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.unit_cost" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.freight_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.fuel_cost" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1 text-right font-bold text-gray-900" x-text="'$' + fmtM(equipTotal())"></td>
                            <td class="px-1 py-1 text-center whitespace-nowrap">
                                <button @click="$dispatch('tm-edit', d)" class="text-indigo-500 hover:text-indigo-700 mr-1" title="Edit line"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                                <button @click="removeLine()" class="text-red-400 hover:text-red-600" title="Delete"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="py-2 px-3">
                <button @click="addEquipLine('company_owned')" class="text-xs font-semibold text-orange-600 hover:text-orange-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Company Equipment Line
                </button>
            </div>
        </details>
    </div>

    {{-- ═══════ SUBCONTRACTORS ═══════ --}}
    <div class="px-4 pt-4 pb-2">
        <details class="group">
            <summary class="flex items-center justify-between cursor-pointer py-2 px-3 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-bold text-rose-900 uppercase tracking-wide">Subcontractors</h3>
                    <span class="text-xs text-rose-600" x-text="'(' + (sectionCounts['subcontractor'] || 0) + ' lines)'"></span>
                </div>
                <span class="text-xs text-gray-600">Total: <strong class="text-gray-900" x-text="'$' + fmtM(sectionTotals['subcontractor']?.price || 0)"></strong></span>
            </summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <th class="px-2 py-1.5 text-left font-semibold w-24">Cost Code</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[120px]">Discipline</th>
                            <th class="px-2 py-1.5 text-left font-semibold min-w-[140px]">Description / Notes</th>
                            <th class="px-2 py-1.5 text-left font-semibold w-36">Subcontractor Name</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-24">Cost</th>
                            <th class="px-2 py-1.5 text-right font-semibold w-16">Markup %</th>
                            <th class="px-2 py-1.5 text-right font-bold w-24">Total $</th>
                            <th class="px-2 py-1.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcontractorLines ?? [] as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="tmSubRow({{ json_encode([
                            'id' => $line->id,
                            'cost_code_id' => $line->cost_code_id,
                            'discipline' => $line->discipline,
                            'description' => $line->description,
                            'subcontractor_name' => $line->subcontractor_name,
                            'quote_amount' => (float) $line->quote_amount,
                            'markup_percent' => (float) $line->markup_percent,
                            'price_amount' => (float) $line->price_amount,
                        ]) }})">
                            <td class="px-1 py-1"><select x-model="d.cost_code_id" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"><option value="">—</option>@foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach</select></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.discipline" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded" placeholder="e.g. Electrical"></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.description" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="text" x-model="d.subcontractor_name" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="0.01" x-model.number="d.quote_amount" @change="save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded"></td>
                            <td class="px-1 py-1"><input type="number" min="0" step="1" max="1000" :value="Math.round((d.markup_percent||0)*100)" @change="d.markup_percent = (parseFloat($event.target.value)||0)/100; save()" class="w-full border-0 bg-transparent text-xs px-1 py-0.5 text-right focus:ring-1 focus:ring-blue-400 rounded" title="Enter as percent (30 = 30%)"></td>
                            <td class="px-1 py-1 text-right font-bold text-gray-900" x-text="'$' + fmtM(subTotal())"></td>
                            <td class="px-1 py-1 text-center whitespace-nowrap">
                                <button @click="$dispatch('tm-edit', d)" class="text-indigo-500 hover:text-indigo-700 mr-1" title="Edit line"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                                <button @click="removeLine()" class="text-red-400 hover:text-red-600" title="Delete"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="py-2 px-3">
                <button @click="addSubLine()" class="text-xs font-semibold text-rose-600 hover:text-rose-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Subcontractor Line
                </button>
            </div>
        </details>
    </div>

    {{-- ═══════ COST SUMMARY ═══════ --}}
    <div class="px-4 pt-6 pb-6">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Cost Summary</h3>
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Direct Labor Cost</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['direct_labor']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Indirect Field Labor Cost</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['indirect_field_labor']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Field Staff Labor Cost</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['field_staff']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-200 bg-gray-100"><td class="py-1.5 font-bold text-gray-900">Total Labor</td><td class="py-1.5 text-right font-bold text-gray-900" x-text="'$' + fmtM((sectionTotals['direct_labor']?.price||0) + (sectionTotals['indirect_field_labor']?.price||0) + (sectionTotals['field_staff']?.price||0))"></td></tr>
                    <tr><td colspan="2" class="py-1"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Material Cost</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['material']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total 3rd Party Equipment</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['equip_3p']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Company Owned Equipment</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['equip_coe']?.price || 0)"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">Total Subcontractors</td><td class="py-1.5 text-right font-semibold" x-text="'$' + fmtM(sectionTotals['subcontractor']?.price || 0)"></td></tr>
                    <tr><td colspan="2" class="py-1"></td></tr>
                    <tr class="border-t-2 border-gray-400 bg-blue-50"><td class="py-2 font-bold text-blue-900 text-base">Total T&M Budgetary Quote</td><td class="py-2 text-right font-bold text-blue-900 text-base" x-text="'$' + fmtM(summary.totalPrice)"></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════ EDIT LINE MODAL ═══════
         Opens when any row's pencil icon is clicked ($dispatch('tm-edit', d)).
         Form adapts to the line's type — labor / material / equipment / sub. --}}
    <div x-show="editOpen" x-cloak @keydown.escape.window="editOpen = false" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="editOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
                <h3 class="text-lg font-bold text-gray-900">
                    Edit Line —
                    <span x-text="(edit.line_type || '').replace('_',' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                </h3>
                <button @click="editOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                {{-- Common fields --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost Code</label>
                        <select x-model="edit.cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach($costCodes as $cc)<option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description</label>
                        <input type="text" x-model="edit.description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                {{-- LABOR fields --}}
                <template x-if="edit.line_type === 'labor'">
                    <div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Work Schedule</label>
                                <input type="text" x-model="edit.work_schedule" placeholder="5-10" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Craft</label>
                                <select x-model="edit.craft_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">—</option>
                                    @foreach($crafts as $c)<option value="{{ $c->id }}" data-rate="{{ $c->base_hourly_rate }}" data-billable="{{ $c->billable_rate ?? '' }}" data-ot-billable="{{ $c->ot_billable_rate ?? '' }}">{{ $c->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Role</label>
                                <input type="text" x-model="edit.role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Crew Size</label>
                                <input type="number" min="0" x-model.number="edit.crew_size" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Weeks</label>
                                <input type="number" min="0" step="0.5" x-model.number="edit.weeks" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Days/Week</label>
                                <input type="number" min="0" max="7" x-model.number="edit.days_per_week" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Hours/Day</label>
                                <input type="number" min="0" max="24" step="0.5" x-model.number="edit.hours_per_day" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4 p-3 bg-blue-50 rounded-lg">
                            <div>
                                <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">ST Hours</label>
                                <input type="number" min="0" step="0.01" x-model.number="edit.hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">ST Billable Rate</label>
                                <input type="number" min="0" step="0.01" x-model.number="edit.hourly_billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3 p-3 bg-amber-50 rounded-lg">
                            <div>
                                <label class="block text-xs font-semibold text-amber-700 uppercase mb-1">OT Hours</label>
                                <input type="number" min="0" step="0.01" x-model.number="edit.ot_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-amber-700 uppercase mb-1">OT Billable Rate</label>
                                <input type="number" min="0" step="0.01" x-model.number="edit.ot_hourly_billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                    </div>
                </template>

                {{-- MATERIAL fields --}}
                <template x-if="edit.line_type === 'material'">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Vendor</label>
                            <input type="text" x-model="edit.vendor_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.quote_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Freight</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.freight_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tax</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.tax_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Markup %</label>
                            <input type="number" min="0" step="1" max="1000" :value="Math.round((edit.markup_percent||0)*100)" @change="edit.markup_percent = (parseFloat($event.target.value)||0)/100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="30 = 30%">
                        </div>
                    </div>
                </template>

                {{-- EQUIPMENT fields --}}
                <template x-if="edit.line_type === 'equipment'">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Quantity</label>
                            <input type="number" min="0" x-model.number="edit.quantity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Duration</label>
                            <input type="number" min="0" step="0.5" x-model.number="edit.equipment_duration" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">UOM</label>
                            <select x-model="edit.duration_uom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Unit Rate</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.unit_cost" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Freight</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.freight_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fuel</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.fuel_cost" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Markup %</label>
                            <input type="number" min="0" step="1" max="1000" :value="Math.round((edit.markup_percent||0)*100)" @change="edit.markup_percent = (parseFloat($event.target.value)||0)/100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="30 = 30%">
                        </div>
                    </div>
                </template>

                {{-- SUBCONTRACTOR fields --}}
                <template x-if="edit.line_type === 'subcontractor'">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Discipline</label>
                            <input type="text" x-model="edit.discipline" placeholder="e.g. Electrical" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Subcontractor Name</label>
                            <input type="text" x-model="edit.subcontractor_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost</label>
                            <input type="number" min="0" step="0.01" x-model.number="edit.quote_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Markup %</label>
                            <input type="number" min="0" step="1" max="1000" :value="Math.round((edit.markup_percent||0)*100)" @change="edit.markup_percent = (parseFloat($event.target.value)||0)/100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="30 = 30%">
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2 sticky bottom-0 bg-white">
                <button @click="editOpen = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button @click="saveEdit()" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">Save Line</button>
            </div>
        </div>
    </div>

</div>
