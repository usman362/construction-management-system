@extends('layouts.app')

@section('title', 'Employees')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Employees</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('employees.import.template') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-gray-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download Template
        </a>
        <button onclick="openModal('importModal')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/></svg>
            Import CSV
        </button>
        <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Employee
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>ID</th><th>Legacy ID</th><th>Name</th><th>Email</th><th>Role</th><th>Craft</th><th>Hourly Rate</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('importModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Import Employees from CSV</h3>
            <button onclick="closeModal('importModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="importForm" method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-900">
                <p class="font-semibold mb-1">Before importing:</p>
                <ol class="list-decimal list-inside space-y-0.5">
                    <li>Download the template using the "Download Template" button.</li>
                    <li>Fill in one row per employee; keep the header row.</li>
                    <li>Upload the saved .csv file below.</li>
                </ol>
                <p class="mt-2 text-xs">Existing employees (matched by <span class="font-mono">employee_number</span>) will be updated.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV File *</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" onclick="closeModal('importModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" onclick="document.getElementById('importForm').submit()" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Upload & Import</button>
        </div>
    </div>
</div>

@if(session('import_result'))
    @php $ir = session('import_result'); @endphp
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var errors = @json($ir['errors'] ?? []);
        var errorHtml = '';
        if (errors.length > 0) {
            errorHtml = '<br><br><strong>Errors:</strong><ul style="text-align:left;max-height:200px;overflow:auto;font-size:13px;margin-top:8px">';
            errors.forEach(function(e) { errorHtml += '<li>Row ' + e.row + ': ' + e.message + '</li>'; });
            errorHtml += '</ul>';
        }
        Swal.fire({
            icon: {{ ($ir['created'] ?? 0) + ($ir['updated'] ?? 0) > 0 ? "'success'" : "'warning'" }},
            title: 'Import Complete',
            html: '<b style="color:#15803d">{{ $ir['created'] ?? 0 }} created</b> &nbsp; <b style="color:#1d4ed8">{{ $ir['updated'] ?? 0 }} updated</b> &nbsp; <b style="color:#b45309">{{ $ir['skipped'] ?? 0 }} skipped</b>' + errorHtml,
            confirmButtonColor: '#2563eb',
            width: errors.length > 0 ? '600px' : '400px',
        });
    });
    </script>
    @endpush
@endif

@push('scripts')
<script>
var table = $('#dataTable').DataTable({
    ajax: '{{ route("employees.index") }}',
    columns: [
        {data:'employee_number'},
        {data:'legacy_employee_id', render: d => d ? '<span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">'+d+'</span>' : '<span class="text-gray-400">—</span>'},
        {data:'full_name'},
        {data:'email', render: d => d || '<span class="text-gray-400">—</span>'},
        {data:'role'},
        {data:'craft_name', render: d => d || '<span class="text-gray-400">—</span>'},
        {data:'hourly_rate', render: d=>'$'+parseFloat(d).toFixed(2)},
        {data:'status', className:'text-center', render: function(d) {
            const colors = {'active':'bg-green-100 text-green-700','inactive':'bg-gray-100 text-gray-700','terminated':'bg-red-100 text-red-700'};
            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium '+(colors[d]||'bg-gray-100 text-gray-700')+'">'+d+'</span>';
        }},
        {data:'id', orderable:false, searchable:false, className:'text-center',
         render: function(id) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<a href="'+window.BASE_URL+'/employees/'+id+'" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a>'+
                '<a href="'+window.BASE_URL+'/employees/'+id+'/edit" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></a>'+
                '<button onclick="confirmDelete(window.BASE_URL+\'/employees/'+id+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});
</script>
@endpush

@endsection
