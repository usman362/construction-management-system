@extends('layouts.app')
@section('title', 'Fuel Logs')
@section('content')

<div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fuel Logs</h1>
            <p class="text-sm text-gray-500 mt-1">Every equipment fill-up by project. Spot fuel theft and track maintenance hours.</p>
        </div>
        <button onclick="openModal('addFuelModal')" class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm">
            + Log Fuel Fill
        </button>
    </div>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Total Gallons</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($summary['total_gallons'], 2) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg border border-amber-200 p-3">
            <p class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Total Cost</p>
            <p class="text-xl font-bold text-amber-900">${{ number_format($summary['total_cost'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Fill-ups</p>
            <p class="text-xl font-bold text-gray-900">{{ $summary['count'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <select name="equipment_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Any equipment</option>
                @foreach($equipment as $eq)
                    <option value="{{ $eq->id }}" @selected((int)($filters['equipment_id'] ?? 0) === $eq->id)>{{ $eq->name }}</option>
                @endforeach
            </select>
            <select name="project_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Any project</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" @selected((int)($filters['project_id'] ?? 0) === $p->id)>{{ $p->project_number }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
                <a href="{{ route('fuel-logs.index') }}" class="bg-gray-100 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
            </div>
        </div>
    </form>

    {{-- Logs table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($logs->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">No fuel logs yet — click "Log Fuel Fill" to start.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Date</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Equipment</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Project</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Gallons</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">$/Gal</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Total</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Hours/Miles</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Vendor</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($logs as $log)
                        <tr class="hover:bg-amber-50/30">
                            <td class="px-3 py-2 text-gray-700 text-xs">{{ $log->fuel_date?->format('M j, Y') }}</td>
                            <td class="px-3 py-2 text-gray-900">{{ $log->equipment?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $log->project?->project_number ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $log->gallons, 2) }}</td>
                            <td class="px-3 py-2 text-right">${{ number_format((float) $log->price_per_gallon, 4) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-amber-700">${{ number_format((float) $log->total_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right text-xs text-gray-500">
                                @if($log->hour_meter_reading) {{ number_format($log->hour_meter_reading) }} hr @endif
                                @if($log->odometer_reading) {{ number_format($log->odometer_reading) }} mi @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $log->vendor_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="confirmDelete(`{{ url('/fuel-logs/' . $log->id) }}`, null, '{{ route('fuel-logs.index') }}')" class="text-red-600 hover:text-red-800 text-xs">×</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

{{-- Add fuel modal --}}
<div id="addFuelModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('addFuelModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Log Fuel Fill</h3>
        <form id="addFuelForm" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Equipment *</label>
                    <select name="equipment_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— Pick —</option>
                        @foreach($equipment as $eq)
                            <option value="{{ $eq->id }}">{{ $eq->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date *</label>
                    <input type="date" name="fuel_date" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Project</label>
                    <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— Optional —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->project_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cost Code</label>
                    <select name="cost_code_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— Optional —</option>
                        @foreach($costCodes as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fuel Type</label>
                    <select name="fuel_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="diesel">Diesel</option>
                        <option value="unleaded">Unleaded</option>
                        <option value="premium">Premium</option>
                        <option value="off_road">Off-road Diesel</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Gallons *</label>
                    <input type="number" step="0.001" name="gallons" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" oninput="updateTotal()">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">$/Gallon *</label>
                    <input type="number" step="0.0001" name="price_per_gallon" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" oninput="updateTotal()">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Total (auto)</label>
                    <input type="text" id="fuelTotalDisplay" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Hour Meter</label>
                    <input type="number" name="hour_meter_reading" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Odometer</label>
                    <input type="number" name="odometer_reading" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Vendor / Station</label>
                    <input type="text" name="vendor_name" placeholder="e.g. Murphy USA" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Receipt #</label>
                    <input type="text" name="receipt_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('addFuelModal')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="saveFuelLog()" class="px-3 py-2 text-sm bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateTotal() {
    const form = document.getElementById('addFuelForm');
    const g = parseFloat(form.gallons.value) || 0;
    const p = parseFloat(form.price_per_gallon.value) || 0;
    document.getElementById('fuelTotalDisplay').value = '$' + (g * p).toFixed(2);
}

async function saveFuelLog() {
    const form = document.getElementById('addFuelForm');
    if (!form.reportValidity()) return;
    const fd = new FormData(form);
    const r = await fetch('{{ route("fuel-logs.store") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: fd,
    });
    const b = await r.json();
    if (!r.ok) { Toast.fire({icon:'error', title: b.message || 'Save failed'}); return; }
    Toast.fire({icon:'success', title: b.message});
    location.reload();
}
</script>
@endpush
@endsection
