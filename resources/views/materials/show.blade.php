@extends('layouts.app')

@section('title', $material->name)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('materials.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Materials</a>
        <div class="space-x-2">
            <button type="button" onclick="editMaterial({{ $material->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('materials.destroy', $material) }}', null, '{{ route('materials.index') }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8 space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $material->name }}</h1>
            @if($material->category)
                <p class="text-sm text-gray-500 mt-1">{{ $material->category }}</p>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase mb-3">Details</h2>
                <p class="text-gray-800">{{ $material->description ?? 'No description.' }}</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Unit</dt><dd class="font-medium text-gray-900">{{ $material->unit_of_measure ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Unit cost</dt><dd class="font-medium text-gray-900">${{ number_format($material->unit_cost ?? 0, 2) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Vendor</dt><dd class="text-gray-900">
                        @if($material->vendor_id && $material->vendor)
                            <a href="{{ route('vendors.show', $material->vendor) }}" class="text-blue-600 hover:underline">{{ $material->vendor->name }}</a>
                        @else
                            —
                        @endif
                    </dd></div>
                </dl>
            </div>

            <div class="bg-gray-50 rounded-lg border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase mb-3">Recent usage</h2>
                @if($material->usages->isNotEmpty())
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach($material->usages->take(10) as $usage)
                            <li class="py-2 flex justify-between gap-4 flex-wrap">
                                <span class="text-gray-700">{{ $usage->date?->format('M j, Y') }} · {{ $usage->project->name ?? '—' }}</span>
                                <span class="text-gray-600">{{ number_format($usage->quantity, 2) }} @ ${{ number_format($usage->unit_cost ?? 0, 2) }} = ${{ number_format($usage->total_cost ?? 0, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500 text-sm">No usage recorded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@include('materials.partials.material-edit-modal')

@push('scripts')
<script>
var table = null;
function editMaterial(id){
    $.get('{{ url('/materials') }}/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="description"]').value=d.description||'';
        f.querySelector('[name="category"]').value=d.category||'';
        f.querySelector('[name="unit_of_measure"]').value=d.unit_of_measure||'';
        f.querySelector('[name="unit_cost"]').value=d.unit_cost||'';
        f.querySelector('[name="vendor_id"]').value=d.vendor_id||'';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/materials') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush
@endsection
