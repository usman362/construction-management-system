@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('exports.invoices') }}" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-emerald-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-emerald-200 transition" title="Download all invoices as Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export
            </a>
            <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Invoice
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table id="dataTable" class="w-full">
            <thead><tr>
                <th>Number</th><th>Date</th><th>Vendor</th><th>Project</th><th>Amount</th><th>Status</th><th class="text-right" width="100">Actions</th>
            </tr></thead>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add Invoice</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number *</label><input type="text" name="invoice_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date *</label><input type="date" name="invoice_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Project *</label><select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required id="createProjectId"></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label><select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required id="createVendorId"></select></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label><input type="number" step="0.01" name="amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label><input type="date" name="due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required><option value="draft">Draft</option><option value="submitted">Submitted</option><option value="approved">Approved</option><option value="paid">Paid</option></select></div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("invoices.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Invoice</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="_id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number *</label><input type="text" name="invoice_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date *</label><input type="date" name="invoice_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Project *</label><select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required id="editProjectId"></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label><select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required id="editVendorId"></select></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label><input type="number" step="0.01" name="amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label><input type="date" name="due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required><option value="draft">Draft</option><option value="submitted">Submitted</option><option value="approved">Approved</option><option value="paid">Paid</option></select></div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('viewModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Invoice Details</h3>
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
    ajax: '{{ route("invoices.index") }}',
    columns: [
        {data:'invoice_number'}, {data:'invoice_date'},
        {data:'vendor'}, {data:'project'},
        {data:'amount', render: d=>'$'+parseFloat(d).toFixed(2)},
        {data:'status', render: d=>'<span class="px-2 py-1 rounded text-xs font-medium '+(d==='paid'?'bg-green-100 text-green-700':d==='approved'?'bg-blue-100 text-blue-700':d==='submitted'?'bg-yellow-100 text-yellow-700':'bg-gray-100 text-gray-700')+'">'+d.charAt(0).toUpperCase()+d.slice(1)+'</span>'},
        {data:'actions', orderable:false, searchable:false, className:'text-right',
         render: function(id) {
            return `<div class="flex items-center justify-end gap-1">
                <button onclick="viewInvoice(${id})" class="p-1 text-gray-400 hover:text-blue-600" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                <button onclick="editInvoice(${id})" class="p-1 text-gray-400 hover:text-amber-600" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                <button onclick="confirmDelete(window.BASE_URL+'/invoices/${id}',table)" class="p-1 text-gray-400 hover:text-red-600" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
            </div>`;
        }}
    ]
});

// Load projects and vendors for dropdowns
function loadDropdowns() {
    $.get(window.BASE_URL+'/projects', function(data) {
        if (data && data.data) {
            var opts = '<option value="">Select Project</option>';
            data.data.forEach(function(p) { opts += '<option value="'+p.id+'">'+p.name+'</option>'; });
            $('#createProjectId, #editProjectId').html(opts);
        }
    });
    $.get(window.BASE_URL+'/vendors', function(data) {
        if (data && data.data) {
            var opts = '<option value="">Select Vendor</option>';
            data.data.forEach(function(v) { opts += '<option value="'+v.id+'">'+v.name+'</option>'; });
            $('#createVendorId, #editVendorId').html(opts);
        }
    });
}
loadDropdowns();

function openCreateModal(){ document.getElementById('createForm').reset(); openModal('createModal'); }

function editInvoice(id){
    $.get(window.BASE_URL+'/invoices/'+id+'/edit', function(d){
        let f = document.getElementById('editForm');
        f.querySelector('#edit_id').value = d.id;
        f.querySelector('[name="invoice_number"]').value = d.invoice_number||'';
        f.querySelector('[name="invoice_date"]').value = d.invoice_date||'';
        f.querySelector('[name="project_id"]').value = d.project_id||'';
        f.querySelector('[name="vendor_id"]').value = d.vendor_id||'';
        f.querySelector('[name="amount"]').value = d.amount;
        f.querySelector('[name="description"]').value = d.description||'';
        f.querySelector('[name="due_date"]').value = d.due_date||'';
        f.querySelector('[name="status"]').value = d.status;
        document.getElementById('editSaveBtn').onclick = function(){ submitForm('editForm',window.BASE_URL+'/invoices/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}

function viewInvoice(id){
    $.get(window.BASE_URL+'/invoices/'+id, function(d){
        document.getElementById('viewContent').innerHTML =
            '<div class="space-y-4">'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Invoice Number</p><p class="text-sm font-semibold">'+(d.invoice_number||'—')+'</p></div><div><p class="text-xs text-gray-500 mb-1">Invoice Date</p><p class="text-sm font-semibold">'+(d.invoice_date||'—')+'</p></div></div>'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Project</p><p class="text-sm">'+(d.project?.name||'—')+'</p></div><div><p class="text-xs text-gray-500 mb-1">Vendor</p><p class="text-sm">'+(d.vendor?.name||'—')+'</p></div></div>'+
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Amount</p><p class="text-sm font-semibold">$'+parseFloat(d.amount).toFixed(2)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Status</p><p class="text-sm font-semibold capitalize">'+d.status+'</p></div></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Due Date</p><p class="text-sm">'+(d.due_date||'—')+'</p></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Description</p><p class="text-sm">'+(d.description||'—')+'</p></div>'+
            '</div>';
        openModal('viewModal');
    });
}
</script>
@endpush

@endsection
