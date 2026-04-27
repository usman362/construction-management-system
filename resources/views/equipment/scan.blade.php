@extends('layouts.app')
@section('title', 'Scan: ' . $equipment->name)
@section('content')

<div class="max-w-md mx-auto px-4 py-6 space-y-5">

    {{-- Equipment header card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs uppercase font-bold text-gray-500 tracking-wider">Equipment</p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $equipment->name }}</h1>
                <p class="text-sm text-gray-500">{{ $equipment->model_number ?? '—' }}</p>
                @if($equipment->serial_number)
                    <p class="text-xs text-gray-400 font-mono">SN: {{ $equipment->serial_number }}</p>
                @endif
            </div>
            <span class="inline-flex px-2 py-1 rounded text-xs font-semibold
                @switch($equipment->status)
                    @case('available')   bg-green-100 text-green-800   @break
                    @case('in_use')      bg-amber-100 text-amber-800   @break
                    @case('maintenance') bg-orange-100 text-orange-800 @break
                    @case('retired')     bg-gray-200 text-gray-700     @break
                    @default             bg-gray-100 text-gray-700
                @endswitch">
                {{ ucfirst($equipment->status) }}
            </span>
        </div>

        @if($equipment->daily_rate)
            <p class="text-xs text-gray-500 mt-2">Daily rate: <strong>${{ number_format((float) $equipment->daily_rate, 2) }}</strong></p>
        @endif
    </div>

    {{-- Status banner --}}
    @if($isCheckedOut)
        <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-4">
            <p class="text-xs uppercase font-bold text-amber-700 tracking-wider">Currently Checked Out</p>
            <p class="text-base font-bold text-amber-900 mt-1">
                {{ $equipment->currentAssignment->project->project_number ?? '?' }} — {{ $equipment->currentAssignment->project->name ?? '—' }}
            </p>
            <p class="text-xs text-amber-700 mt-1">
                Since {{ optional($equipment->currentAssignment->assigned_date)->format('M j, Y') }}
            </p>
        </div>
    @else
        <div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-4 text-center">
            <p class="text-base font-bold text-emerald-900">✓ Available</p>
            <p class="text-xs text-emerald-700 mt-1">Pick a project below to check this out.</p>
        </div>
    @endif

    <div id="scanStatus" class="hidden rounded-lg p-3 text-sm"></div>

    {{-- Action --}}
    @if($isCheckedOut)
        <button type="button" onclick="checkInEquipment()"
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg py-5 rounded-xl shadow-md transition">
            Check In (Mark Returned)
        </button>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Check Out To Project</h3>
            <select id="projectId" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
                <option value="">— Pick a project —</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                @endforeach
            </select>
            <textarea id="scanNotes" rows="2" placeholder="Notes (optional)"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            <button type="button" onclick="checkOutEquipment()"
                    class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-lg py-5 rounded-xl shadow-md transition">
                Check Out
            </button>
        </div>
    @endif

    <div class="text-center pt-4">
        <a href="{{ route('equipment.show', $equipment) }}" class="text-xs text-gray-500 hover:text-gray-700 underline">
            View full equipment detail
        </a>
    </div>
</div>

@push('scripts')
<script>
const QR_TOKEN = @json($equipment->qr_token);
const QR_OUT_URL = '{{ url("/equipment/scan") }}/' + QR_TOKEN + '/check-out';
const QR_IN_URL  = '{{ url("/equipment/scan") }}/' + QR_TOKEN + '/check-in';
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function showScanStatus(kind, msg) {
    const el = document.getElementById('scanStatus');
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok'
        ? 'bg-green-50 border border-green-200 text-green-800'
        : 'bg-red-50 border border-red-200 text-red-800');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function checkOutEquipment() {
    const projectId = document.getElementById('projectId').value;
    const notes = document.getElementById('scanNotes').value || null;
    if (!projectId) { showScanStatus('error', 'Pick a project first.'); return; }

    const r = await fetch(QR_OUT_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ project_id: projectId, notes }),
    });
    const body = await r.json();
    if (!r.ok) { showScanStatus('error', body.message || 'Check-out failed.'); return; }
    showScanStatus('ok', body.message);
    setTimeout(() => location.reload(), 700);
}

async function checkInEquipment() {
    if (!confirm('Mark this equipment as returned?')) return;
    const r = await fetch(QR_IN_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const body = await r.json();
    if (!r.ok) { showScanStatus('error', body.message || 'Check-in failed.'); return; }
    showScanStatus('ok', body.message);
    setTimeout(() => location.reload(), 700);
}
</script>
@endpush

@endsection
