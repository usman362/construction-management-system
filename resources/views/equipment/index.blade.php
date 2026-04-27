@extends('layouts.app')
@section('title', 'Equipment')
@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Equipment</h1>
    <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Equipment
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>Name</th><th>Type</th><th>Model#</th><th>Serial#</th><th>Day</th><th>Week</th><th>Month</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Equipment</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Select…</option>
                        <option value="owned">Owned</option>
                        <option value="rented">Rented</option>
                        <option value="third_party">Third party</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Model Number</label><input type="text" name="model_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label><input type="text" name="serial_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Rates (day / week / month)</p>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Day *</label><input type="number" step="0.01" name="daily_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required placeholder="0.00"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Week</label><input type="number" step="0.01" name="weekly_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="0.00"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Month</label><input type="number" step="0.01" name="monthly_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="0.00"></div>
                </div>
            </div>
            {{-- 2026-04-28: vendor + description fields merged in from the deleted equipment/create.blade.php --}}
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required><option value="">Select...</option><option value="available">Available</option><option value="in_use">In Use</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option></select></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                    <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">— None —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("equipment.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Equipment</button>
        </div>
    </div>
</div>

@include('equipment.partials.equipment-edit-modal')

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('viewModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Equipment Details</h3>
            <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div id="viewContent" class="p-6">Loading...</div>
        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('viewModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var table = $('#dataTable').DataTable({
    ajax: '{{ route("equipment.index") }}',
    columns: [
        {data:'name'},
        {data:'type', render: function(t){ if(!t) return '—'; var m={owned:'Owned',rented:'Rented',third_party:'Third party'}; return m[t]||t; }},
        {data:'model_number', render: d => d ? d : '<span class="text-gray-400">—</span>'},
        {data:'serial_number', render: d => d ? d : '<span class="text-gray-400">—</span>'},
        {data:'daily_rate', className:'text-right', render: function(d){ return '$'+parseFloat(d||0).toFixed(2); }},
        {data:'weekly_rate', className:'text-right', render: function(d){ return '$'+parseFloat(d||0).toFixed(2); }},
        {data:'monthly_rate', className:'text-right', render: function(d){ return '$'+parseFloat(d||0).toFixed(2); }},
        {data:'status', className:'text-center', render: d=>d==='available'?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Available</span>':(d==='in_use'?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">In Use</span>':(d==='maintenance'?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Maintenance</span>':'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Retired</span>'))},
        {data:'actions', orderable:false, searchable:false, className:'text-center',
         render: function(data) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<a href="{{ url('/equipment') }}/'+data+'/qr-sticker" target="_blank" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-purple-600 hover:bg-purple-50" title="Print QR sticker"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg></a>'+
                '<button onclick="viewEquipment('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>'+
                '<button onclick="editEquipment('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>'+
                '<button onclick="confirmDelete(\'{{ url('/equipment') }}/'+data+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});

function openCreateModal(){ document.getElementById('createForm').reset(); openModal('createModal'); }

@include('partials.auto-open-create-modal')

function editEquipment(id){
    $.get('{{ url('/equipment') }}/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="type"]').value=d.type;
        f.querySelector('[name="model_number"]').value=d.model_number||'';
        f.querySelector('[name="serial_number"]').value=d.serial_number||'';
        f.querySelector('[name="daily_rate"]').value=d.daily_rate;
        f.querySelector('[name="weekly_rate"]').value=d.weekly_rate != null ? d.weekly_rate : '';
        f.querySelector('[name="monthly_rate"]').value=d.monthly_rate != null ? d.monthly_rate : '';
        f.querySelector('[name="status"]').value=d.status;
        // 2026-04-28: populate the merged vendor + description fields
        if(f.querySelector('[name="vendor_id"]'))   f.querySelector('[name="vendor_id"]').value=d.vendor_id || '';
        if(f.querySelector('[name="description"]')) f.querySelector('[name="description"]').value=d.description || '';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/equipment') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}

function viewEquipment(id){
    $.get('{{ url('/equipment') }}/'+id, function(d){
        var typeLabel={owned:'Owned',rented:'Rented',third_party:'Third party'}[d.type]||d.type;
        let statusBadge='<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Retired</span>';
        if(d.status==='available') statusBadge='<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Available</span>';
        else if(d.status==='in_use') statusBadge='<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">In use</span>';
        else if(d.status==='maintenance') statusBadge='<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Maintenance</span>';
        document.getElementById('viewContent').innerHTML=
            '<div class="space-y-4">'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Name</p><p class="text-sm font-semibold">'+d.name+'</p></div><div><p class="text-xs text-gray-500 mb-1">Type</p><p class="text-sm">'+typeLabel+'</p></div></div>'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Model Number</p><p class="text-sm">'+(d.model_number||'—')+'</p></div><div><p class="text-xs text-gray-500 mb-1">Serial Number</p><p class="text-sm">'+(d.serial_number||'—')+'</p></div></div>'+
            '<div class="grid grid-cols-3 gap-3"><div><p class="text-xs text-gray-500 mb-1">Day</p><p class="text-sm font-semibold">$'+parseFloat(d.daily_rate||0).toFixed(2)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Week</p><p class="text-sm font-semibold">$'+parseFloat(d.weekly_rate||0).toFixed(2)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Month</p><p class="text-sm font-semibold">$'+parseFloat(d.monthly_rate||0).toFixed(2)+'</p></div></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Status</p>'+statusBadge+'</div>'+
            '</div>';
        openModal('viewModal');
    });
}
</script>
@endpush
@endsection