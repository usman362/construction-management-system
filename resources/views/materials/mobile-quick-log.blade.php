@extends('layouts.app')
@section('title', 'Material Usage — ' . $project->project_number)
@section('content')

{{--
    Mobile-first material quick-log. Foreman opens this on their phone, picks
    a material, types the qty + cost code, hits Save. Uses the existing
    materials.record-usage endpoint.

    UX details:
    - Material picker grouped by category for fast scrolling
    - Cost code picker with search box (since cost codes are dozens long)
    - Quick-pick "Yesterday / Today" date buttons since most logs happen
      same-day or next-morning
--}}

<div class="max-w-xl mx-auto px-4 py-6 space-y-5">
    <div>
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to {{ $project->project_number }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Log Material Usage</h1>
        <p class="text-sm text-gray-500">{{ $project->name }}</p>
    </div>

    <div id="mqStatus" class="hidden rounded-lg p-3 text-sm"></div>

    <form id="materialQuickForm" class="space-y-4 bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        @csrf

        {{-- Material picker --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Material *</label>
            <select name="material_id" id="material_id" required
                    onchange="onMaterialChange(this)"
                    class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
                <option value="">— Pick a material —</option>
                @php
                    $byCategory = $materials->groupBy(fn ($m) => $m->category ?: 'Uncategorized');
                @endphp
                @foreach($byCategory as $cat => $rows)
                    <optgroup label="{{ $cat }}">
                        @foreach($rows as $m)
                            <option value="{{ $m->id }}"
                                    data-unit="{{ $m->unit_of_measure }}"
                                    data-cost="{{ $m->unit_cost }}">
                                {{ $m->name }}{{ $m->unit_of_measure ? ' (' . $m->unit_of_measure . ')' : '' }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        {{-- Quantity + unit display --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" id="quantity" step="0.01" min="0" required
                       inputmode="decimal" placeholder="0"
                       class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Unit</label>
                <input type="text" id="unitDisplay" disabled readonly placeholder="—"
                       class="w-full px-3 py-3 text-base border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
            </div>
        </div>

        {{-- Date with quick buttons --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Date *</label>
            <div class="flex gap-2 mb-2">
                <button type="button" onclick="setDate(0)" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold py-2 rounded-lg">Today</button>
                <button type="button" onclick="setDate(-1)" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold py-2 rounded-lg">Yesterday</button>
            </div>
            <input type="date" name="usage_date" id="usage_date" required
                   value="{{ now()->toDateString() }}"
                   class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
        </div>

        {{-- Cost code --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Cost Code</label>
            <select name="cost_code_id" class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
                <option value="">— Optional —</option>
                @foreach($costCodes as $cc)
                    <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-gray-500 mt-1">Pick the phase code this material was used on, if known.</p>
        </div>

        {{-- Optional override --}}
        <details class="bg-gray-50 rounded-lg border border-gray-200">
            <summary class="px-3 py-2 text-sm font-semibold text-gray-700 cursor-pointer">Override unit cost (optional)</summary>
            <div class="p-3">
                <input type="number" name="unit_cost" id="unit_cost" step="0.01" min="0" placeholder="leaves the catalog price"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <p class="text-[11px] text-gray-500 mt-1">Leave blank to use the material's standard cost.</p>
            </div>
        </details>

        {{-- Notes --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="description" rows="2" placeholder="Where it went, batch ticket #, etc."
                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"></textarea>
        </div>

        {{-- Live total preview --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-900">
            Estimated cost: <strong id="estCost">$0.00</strong>
            <span class="text-xs text-blue-700" id="estDetail"></span>
        </div>

        <button type="submit" id="submitBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg py-4 rounded-xl shadow-md transition disabled:opacity-50">
            Save &amp; Log Another
        </button>

        <button type="button" onclick="saveAndExit()" id="saveExitBtn"
                class="w-full bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm py-3 rounded-xl border border-gray-300">
            Save &amp; Done
        </button>
    </form>
</div>

@push('scripts')
<script>
const MQ_PROJECT_ID = {{ $project->id }};
const MQ_USAGE_URL  = '{{ route("materials.record-usage") }}';
const MQ_BACK_URL   = '{{ route("projects.show", $project) }}';

function onMaterialChange(sel) {
    const opt = sel.selectedOptions[0];
    const unit = opt?.dataset.unit ?? '';
    const cost = opt?.dataset.cost ?? '';
    document.getElementById('unitDisplay').value = unit || '—';
    document.getElementById('unit_cost').placeholder = cost ? `${cost} per ${unit || 'unit'}` : 'leaves the catalog price';
    updateEstimate();
}

function setDate(daysAgo) {
    const d = new Date(); d.setDate(d.getDate() + daysAgo);
    document.getElementById('usage_date').value = d.toISOString().substring(0, 10);
}

function updateEstimate() {
    const qty = parseFloat(document.getElementById('quantity').value) || 0;
    const opt = document.getElementById('material_id').selectedOptions[0];
    const overrideCost = parseFloat(document.getElementById('unit_cost').value);
    const cost = (isFinite(overrideCost) && overrideCost > 0) ? overrideCost : parseFloat(opt?.dataset.cost ?? 0);
    const total = qty * cost;
    document.getElementById('estCost').textContent = '$' + total.toFixed(2);
    document.getElementById('estDetail').textContent = (qty > 0 && cost > 0)
        ? `(${qty} × $${cost.toFixed(2)})`
        : '';
}
['quantity', 'unit_cost', 'material_id'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateEstimate);
});

function showMq(kind, msg) {
    const el = document.getElementById('mqStatus');
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok'
        ? 'bg-green-50 border border-green-200 text-green-800'
        : 'bg-red-50 border border-red-200 text-red-800');
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}

let exitOnSave = false;

async function submitForm(reset = true) {
    const form = document.getElementById('materialQuickForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    fd.append('project_id', MQ_PROJECT_ID);
    const payload = {};
    fd.forEach((v, k) => { if (v !== '') payload[k] = v; });

    const btn = document.getElementById('submitBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    try {
        const r = await fetch(MQ_USAGE_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(payload),
        });
        const body = await r.json();
        if (!r.ok) throw new Error(body.message || 'Save failed');

        showMq('ok', body.message + ' ($' + (body.total_cost || 0).toFixed(2) + ')');
        if (exitOnSave) { setTimeout(() => location.href = MQ_BACK_URL, 600); return; }

        // Reset for the next entry
        if (reset) {
            document.getElementById('quantity').value = '';
            document.getElementById('unit_cost').value = '';
            document.querySelector('textarea[name=description]').value = '';
            document.getElementById('estCost').textContent = '$0.00';
            document.getElementById('estDetail').textContent = '';
            document.getElementById('material_id').focus();
        }
    } catch (e) {
        showMq('error', e.message);
    } finally {
        btn.disabled = false; btn.textContent = 'Save & Log Another';
    }
}

document.getElementById('materialQuickForm').addEventListener('submit', (e) => {
    e.preventDefault();
    exitOnSave = false;
    submitForm(true);
});

function saveAndExit() {
    exitOnSave = true;
    submitForm(false);
}
</script>
@endpush

@endsection
