@extends('layouts.app')

@section('title', $equipment->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('equipment.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Equipment</a>
        <div class="space-x-2">
            <button type="button" onclick="editEquipment({{ $equipment->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('equipment.destroy', $equipment) }}', null, '{{ route('equipment.index') }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $equipment->name }}</h1>
                <p class="text-gray-600 mt-2">
                    @php
                        $typeLabel = match ($equipment->type) {
                            'owned' => 'Owned',
                            'rented' => 'Rented',
                            'third_party' => 'Third party',
                            default => $equipment->type,
                        };
                    @endphp
                    {{ $typeLabel }}
                    @if($equipment->model_number || $equipment->serial_number)
                        <span class="text-sm text-gray-500">
                            @if($equipment->model_number) · Model {{ $equipment->model_number }} @endif
                            @if($equipment->serial_number) · Serial {{ $equipment->serial_number }} @endif
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">General Information</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Status</p>
                        @php
                            $statusClass = match ($equipment->status) {
                                'available' => 'bg-green-100 text-green-800',
                                'in_use' => 'bg-blue-100 text-blue-800',
                                'maintenance' => 'bg-amber-100 text-amber-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <p class="text-gray-800">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $equipment->status)) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Description</p>
                        <p class="text-gray-800">{{ $equipment->description ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Rental Rates</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Daily Rate</p>
                        <p class="text-lg font-bold text-gray-800">${{ number_format($equipment->daily_rate ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Weekly Rate</p>
                        <p class="text-lg font-bold text-gray-800">${{ number_format($equipment->weekly_rate ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Monthly Rate</p>
                        <p class="text-lg font-bold text-gray-800">${{ number_format($equipment->monthly_rate ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Vendor & Assignment</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Vendor</p>
                        <p class="text-gray-800">
                            @if($equipment->vendor_id && $equipment->vendor)
                                <a href="{{ route('vendors.show', $equipment->vendor) }}" class="text-blue-600 hover:underline">{{ $equipment->vendor->name }}</a>
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Current assignment</p>
                        <p class="text-gray-800">
                            @if($equipment->currentAssignment && $equipment->currentAssignment->project)
                                <a href="{{ route('projects.show', $equipment->currentAssignment->project_id) }}" class="text-blue-600 hover:underline">{{ $equipment->currentAssignment->project->name }}</a>
                            @else
                                <span class="text-gray-500">Unassigned</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Assignment history</h2>
            @if($equipment->assignments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-blue-100 border border-gray-300">
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Project</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Assigned</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Returned</th>
                                <th class="border border-gray-300 px-4 py-2 text-right font-bold">Days</th>
                                <th class="border border-gray-300 px-4 py-2 text-right font-bold">Daily cost</th>
                                <th class="border border-gray-300 px-4 py-2 text-right font-bold">Est. total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalCost = 0; @endphp
                            @foreach($equipment->assignments as $assignment)
                                @php
                                    $days = $assignment->returned_date
                                        ? $assignment->assigned_date->diffInDays($assignment->returned_date) + 1
                                        : null;
                                    $line = ($days && $assignment->daily_cost) ? (float) $assignment->daily_cost * $days : (float) ($assignment->daily_cost ?? 0);
                                    $totalCost += $line;
                                @endphp
                                <tr class="border border-gray-300 {{ $loop->iteration % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                                    <td class="border border-gray-300 px-4 py-2">{{ $assignment->project->name ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $assignment->assigned_date?->format('M j, Y') ?? 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $assignment->returned_date?->format('M j, Y') ?? '—' }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ $days ?? '—' }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($assignment->daily_cost ?? 0, 2) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($line, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-blue-100 border border-gray-300 font-bold">
                                <td colspan="5" class="border border-gray-300 px-4 py-2 text-right">Total (estimated)</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 text-center">
                    <p class="text-gray-500">No assignment history.</p>
                </div>
            @endif
        </div>

        @if($equipment->status === 'available')
            <div class="mb-8">
                <p class="text-sm text-gray-600">Use the API or future UI to assign this equipment to a project.</p>
            </div>
        @endif
    </div>
</div>

@include('equipment.partials.equipment-edit-modal')

@push('scripts')
<script>
var table = null;
function editEquipment(id){
    $.get('{{ url('/equipment') }}/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="type"]').value=d.type;
        f.querySelector('[name="model_number"]').value=d.model_number||'';
        f.querySelector('[name="serial_number"]').value=d.serial_number||'';
        f.querySelector('[name="daily_rate"]').value=d.daily_rate;
        f.querySelector('[name="status"]').value=d.status;
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/equipment') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush
@endsection
