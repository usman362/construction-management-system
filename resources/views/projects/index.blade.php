@extends('layouts.app')

@section('title', 'Projects')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
    <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Project
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>Project #</th><th>Name</th><th>Client</th><th>Status</th><th>Start Date</th><th>Budget</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Project</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Project Number *</label><input type="text" name="project_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label><input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label><input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Budget *</label><input type="number" step="0.01" name="budget" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contract Value</label><input type="number" step="0.01" name="contract_value" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="0.00"></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label><input type="text" name="po_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="e.g. PO-12345"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">PO Date</label><input type="date" name="po_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    @foreach(['bidding' => 'Bidding', 'awarded' => 'Awarded', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'closed' => 'Closed'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select></div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("projects.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Project</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Project</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="_id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Project Number *</label><input type="text" name="project_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label><input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label><input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Budget *</label><input type="number" step="0.01" name="budget" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contract Value</label><input type="number" step="0.01" name="contract_value" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="0.00"></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label><input type="text" name="po_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">PO Date</label><input type="date" name="po_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    @foreach(['bidding' => 'Bidding', 'awarded' => 'Awarded', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'closed' => 'Closed'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select></div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update Project</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var BASE_URL = '{{ url("/") }}';
var table = $('#dataTable').DataTable({
    ajax: '{{ route("projects.index") }}',
    columns: [
        {data:'project_number'}, {data:'name'}, {data:'client_name'},
        {data:'status', className:'text-center', render: function(d) {
            const colors = {'planning':'bg-yellow-100 text-yellow-700','active':'bg-green-100 text-green-700','on_hold':'bg-red-100 text-red-700','completed':'bg-green-100 text-green-700','closed':'bg-gray-100 text-gray-700'};
            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium '+colors[d]+'">'+d.replace('_',' ')+'</span>';
        }},
        {data:'start_date', render: d=>d?new Date(d).toLocaleDateString():'—'},
        {data:'budget', render: d=>d?'$'+parseFloat(d).toFixed(2):'$0.00'},
        {data:'id', orderable:false, searchable:false, className:'text-center',
         render: function(id) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<button onclick="window.location=BASE_URL+\'/projects/'+id+'\'" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>'+
                '<button onclick="editProject('+id+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>'+
                '<button onclick="confirmDelete(BASE_URL+\'/projects/'+id+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});

// Load clients for dropdowns
function loadClients(callback) {
    $.get(BASE_URL+'/clients', function(data) {
        if(data && data.data) {
            var opts = '<option value="">Select Client</option>';
            data.data.forEach(function(c){ opts += '<option value="'+c.id+'">'+c.name+'</option>'; });
            $('select[name="client_id"]').html(opts);
        }
        if(callback) callback();
    });
}
loadClients();

function openCreateModal(){ document.getElementById('createForm').reset(); loadClients(function(){ openModal('createModal'); }); }

function editProject(id){
    $.get(BASE_URL+'/projects/'+id+'/edit', function(d){
        var p = d.project;
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=p.id;
        f.querySelector('[name="project_number"]').value=p.project_number||'';
        f.querySelector('[name="name"]').value=p.name||'';
        f.querySelector('[name="description"]').value=p.description||'';
        f.querySelector('[name="start_date"]').value=p.start_date?p.start_date.substring(0,10):'';
        f.querySelector('[name="end_date"]').value=p.end_date?p.end_date.substring(0,10):'';
        f.querySelector('[name="budget"]').value=p.current_budget||p.budget||'';
        f.querySelector('[name="contract_value"]').value=p.contract_value||'';
        f.querySelector('[name="po_number"]').value=p.po_number||'';
        f.querySelector('[name="po_date"]').value=p.po_date?p.po_date.substring(0,10):'';
        var sel=f.querySelector('[name="client_id"]');
        var opts='<option value="">Select Client</option>';
        if(d.clients){d.clients.forEach(function(c){opts+='<option value="'+c.id+'" '+(c.id==p.client_id?'selected':'')+'>'+c.name+'</option>';});}
        sel.innerHTML=opts;
        f.querySelector('[name="status"]').value=p.status||'active';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm',BASE_URL+'/projects/'+p.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush

@endsection
