@extends('layouts.app')
@section('title', 'Crafts')
@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Crafts</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('crafts.import.template') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-gray-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download Template
        </a>
        <button onclick="openModal('importModal')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/></svg>
            Import CSV
        </button>
        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Craft
        </button>
    </div>
</div>

@if(session('import_result'))
    @php $result = session('import_result'); @endphp
    <div class="mb-4 bg-white border border-gray-200 shadow-sm rounded-lg p-4">
        <p class="font-semibold text-gray-900">Import complete</p>
        <p class="text-sm text-gray-600 mt-1">Created: <span class="font-semibold text-green-700">{{ $result['created'] ?? 0 }}</span>, Updated: <span class="font-semibold text-blue-700">{{ $result['updated'] ?? 0 }}</span>, Skipped: <span class="font-semibold text-amber-700">{{ $result['skipped'] ?? 0 }}</span></p>
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

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('importModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Import Crafts from CSV</h3>
            <button onclick="closeModal('importModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('crafts.import') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-900">
                <p class="font-semibold mb-1">Before importing:</p>
                <ol class="list-decimal list-inside space-y-0.5 text-xs">
                    <li>Download the template using the "Download Template" button.</li>
                    <li>Fill in one row per craft; keep the header row.</li>
                    <li><code>code</code> and <code>name</code> are required. <code>is_active</code> accepts yes/no or true/false.</li>
                    <li>Existing crafts (matched by <code>code</code>) will be updated.</li>
                </ol>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV File *</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeModal('importModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>Code</th><th>Name</th><th>Hourly Rate</th><th>OT Multiplier</th><th>Billable Rate</th><th>Employees</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Craft</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Code *</label><input type="text" name="code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate *</label><input type="number" step="0.01" name="base_hourly_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Multiplier *</label><input type="number" step="0.01" name="overtime_multiplier" value="1.50" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Billable Rate *</label><input type="number" step="0.01" name="billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Billable Rate</label><input type="number" step="0.01" name="ot_billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">WC Rate</label><input type="number" step="0.0001" name="wc_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">FICA Rate</label><input type="number" step="0.0001" name="fica_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">SUTA Rate</label><input type="number" step="0.0001" name="suta_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Benefits $/hr</label><input type="number" step="0.01" name="benefits_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Overhead Rate</label><input type="number" step="0.0001" name="overhead_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("crafts.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Craft</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Craft</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="_id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Code *</label><input type="text" name="code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate *</label><input type="number" step="0.01" name="base_hourly_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Multiplier *</label><input type="number" step="0.01" name="overtime_multiplier" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Billable Rate *</label><input type="number" step="0.01" name="billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Billable Rate</label><input type="number" step="0.01" name="ot_billable_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">WC Rate</label><input type="number" step="0.0001" name="wc_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">FICA Rate</label><input type="number" step="0.0001" name="fica_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">SUTA Rate</label><input type="number" step="0.0001" name="suta_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Benefits $/hr</label><input type="number" step="0.01" name="benefits_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Overhead Rate</label><input type="number" step="0.0001" name="overhead_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update Craft</button>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('viewModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Craft Details</h3>
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
    ajax: '{{ route("crafts.index") }}',
    columns: [
        {data:'code'}, {data:'name'},
        {data:'base_hourly_rate', render: d=>'$'+parseFloat(d).toFixed(2)},
        {data:'overtime_multiplier', render: d=>parseFloat(d).toFixed(2)+'x'},
        {data:'billable_rate', render: d=>'$'+parseFloat(d).toFixed(2)},
        {data:'employees_count', className:'text-center'},
        {data:'is_active', className:'text-center', render: d=>d?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>':'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>'},
        {data:'actions', orderable:false, searchable:false, className:'text-center',
         render: function(data) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<button onclick="viewCraft('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>'+
                '<button onclick="editCraft('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>'+
                '<button onclick="confirmDelete(window.BASE_URL+\'/crafts/\'+data,table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});

function openCreateModal(){ document.getElementById('createForm').reset(); openModal('createModal'); }

function editCraft(id){
    $.get(window.BASE_URL+'/crafts/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="code"]').value=d.code;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="description"]').value=d.description||'';
        f.querySelector('[name="base_hourly_rate"]').value=d.base_hourly_rate;
        f.querySelector('[name="overtime_multiplier"]').value=d.overtime_multiplier;
        f.querySelector('[name="billable_rate"]').value=d.billable_rate;
        f.querySelector('[name="ot_billable_rate"]').value=d.ot_billable_rate||'';
        f.querySelector('[name="wc_rate"]').value=d.wc_rate||'';
        f.querySelector('[name="fica_rate"]').value=d.fica_rate||'';
        f.querySelector('[name="suta_rate"]').value=d.suta_rate||'';
        f.querySelector('[name="benefits_rate"]').value=d.benefits_rate||'';
        f.querySelector('[name="overhead_rate"]').value=d.overhead_rate||'';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm',window.BASE_URL+'/crafts/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}

function viewCraft(id){
    $.get(window.BASE_URL+'/crafts/'+id, function(d){
        var fmtRate = function(v){ return v ? parseFloat(v).toFixed(4) : '—'; };
        var fmtDollar = function(v){ return v ? '$'+parseFloat(v).toFixed(2) : '—'; };
        document.getElementById('viewContent').innerHTML=
            '<div class="space-y-4">'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Code</p><p class="text-sm font-semibold">'+d.code+'</p></div><div><p class="text-xs text-gray-500 mb-1">Name</p><p class="text-sm font-semibold">'+d.name+'</p></div></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Description</p><p class="text-sm">'+(d.description||'—')+'</p></div>'+
            '<div class="grid grid-cols-3 gap-4"><div><p class="text-xs text-gray-500 mb-1">Hourly Rate</p><p class="text-sm font-semibold">$'+parseFloat(d.base_hourly_rate).toFixed(2)+'</p></div><div><p class="text-xs text-gray-500 mb-1">OT Multiplier</p><p class="text-sm font-semibold">'+parseFloat(d.overtime_multiplier).toFixed(2)+'x</p></div><div><p class="text-xs text-gray-500 mb-1">Billable Rate</p><p class="text-sm font-semibold">$'+parseFloat(d.billable_rate).toFixed(2)+'</p></div></div>'+
            '<div class="grid grid-cols-3 gap-4"><div><p class="text-xs text-gray-500 mb-1">OT Billable Rate</p><p class="text-sm font-semibold">'+fmtDollar(d.ot_billable_rate)+'</p></div><div><p class="text-xs text-gray-500 mb-1">WC Rate</p><p class="text-sm font-semibold">'+fmtRate(d.wc_rate)+'</p></div><div><p class="text-xs text-gray-500 mb-1">FICA Rate</p><p class="text-sm font-semibold">'+fmtRate(d.fica_rate)+'</p></div></div>'+
            '<div class="grid grid-cols-3 gap-4"><div><p class="text-xs text-gray-500 mb-1">SUTA Rate</p><p class="text-sm font-semibold">'+fmtRate(d.suta_rate)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Benefits $/hr</p><p class="text-sm font-semibold">'+fmtDollar(d.benefits_rate)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Overhead Rate</p><p class="text-sm font-semibold">'+fmtRate(d.overhead_rate)+'</p></div></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Status</p>'+(d.is_active?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>':'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>')+'</div>'+
            (d.employees&&d.employees.length?'<div><p class="text-xs text-gray-500 mb-2">Employees ('+d.employees.length+')</p><div class="space-y-1">'+d.employees.map(e=>'<p class="text-sm">'+e.first_name+' '+e.last_name+'</p>').join('')+'</div></div>':'')+
            '</div>';
        openModal('viewModal');
    });
}
</script>
@endpush
@endsection
