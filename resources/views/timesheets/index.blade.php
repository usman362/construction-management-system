@extends('layouts.app')

@section('title', 'Timesheets')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Timesheets</h1>
    <div class="flex items-center gap-3">
        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Timesheet
        </button>
        <a href="{{ route('timesheets.bulk-create') }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Bulk Entry
        </a>
        <button type="button" onclick="openBatchPrint()" class="inline-flex items-center gap-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition" title="Print timesheets for billing (date range + filters)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print for Billing
        </button>
    </div>
</div>

{{-- Batch print modal — lets office pick a date range / project / employee and
     opens the print view in a new tab with those filters applied. --}}
<div id="batchPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('batchPrintModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Print Timesheets for Billing</h3>
            <button onclick="closeModal('batchPrintModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="batchPrintForm" class="p-6 space-y-4">
            <p class="text-xs text-gray-500">Pick a date range + any filter. One printable page per timesheet, with the same info the field collected.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Date from</label>
                    <input type="date" name="date_from" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Date to</label>
                    <input type="date" name="date_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Project</label>
                <select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option value="">All projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Employee</label>
                    <select name="employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">All employees</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->employee_number }} — {{ $e->first_name }} {{ $e->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">Any status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('batchPrintModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" onclick="submitBatchPrint('pdf')" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Download PDF</button>
            <button type="button" onclick="submitBatchPrint('html')" class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800">Open Print View</button>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>Date</th><th>Employee</th><th>Project</th><th>Phase code</th><th>Crew</th><th>Regular</th><th>OT</th><th>DT</th><th>Total</th><th>Cost</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Timesheet</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Date *</label><input type="date" name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee *</label>
                    <select name="employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->employee_number }} — {{ $e->first_name }} {{ $e->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                    <select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select project</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Crew</label>
                    <select name="crew_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">—</option>
                        @foreach($crews as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}@if($c->project) ({{ $c->project->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phase code</label>
                <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option value="">— Optional —</option>
                    @foreach($costCodes ?? [] as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Regular Hrs *</label><input type="number" step="0.5" name="regular_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Hrs</label><input type="number" step="0.5" name="overtime_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">DT Hrs</label><input type="number" step="0.5" name="double_time_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                    <select name="shift_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">—</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_billable" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_billable" value="1" checked class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"><span class="text-sm font-medium text-gray-700">Billable</span></label>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("timesheets.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Timesheet</button>
        </div>
    </div>
</div>

@include('timesheets.partials.timesheet-edit-modal')

@push('scripts')
<script>
var table = $('#dataTable').DataTable({
    ajax: '{{ route("timesheets.index") }}',
    columns: [
        {data:'date', render: d=>d?new Date(d).toLocaleDateString():'—'}, {data:'employee_name'}, {data:'project_name'}, {data:'cost_code'},
        {data:'crew_name'},
        {data:'regular_hours', className:'text-right'}, {data:'overtime_hours', className:'text-right'}, {data:'double_time_hours', className:'text-right'},
        {data:'total_hours', className:'text-right font-semibold'}, {data:'cost', render: d=>'$'+parseFloat(d||0).toFixed(2), className:'text-right'},
        {data:'status', className:'text-center', render: function(d) {
            const colors = {'draft':'bg-gray-100 text-gray-700','submitted':'bg-yellow-100 text-yellow-700','approved':'bg-green-100 text-green-700','rejected':'bg-red-100 text-red-700'};
            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium '+colors[d]+'">'+d+'</span>';
        }},
        {data:'id', orderable:false, searchable:false, className:'text-center',
         render: function(id) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<button onclick="window.location=window.BASE_URL+\'/timesheets/'+id+'\'" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>'+
                '<a href="'+window.BASE_URL+\'/timesheets/\'+id+\'/print\'+\'" target="_blank" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-700 hover:bg-gray-100" title="Print"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg></a>'+
                '<button onclick="editTimesheet('+id+', table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>'+
                '<button onclick="confirmDelete(window.BASE_URL+\'/timesheets/'+id+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});

function openCreateModal(){ document.getElementById('createForm').reset(); openModal('createModal'); }

// Batch print — builds a query string from the modal form and opens the
// print-batch route in a new tab (either the HTML print view or the PDF).
function openBatchPrint(){
    document.getElementById('batchPrintForm').reset();
    openModal('batchPrintModal');
}
function submitBatchPrint(mode){
    var form = document.getElementById('batchPrintForm');
    var params = new URLSearchParams();
    new FormData(form).forEach(function(v, k){ if (v) params.append(k, v); });
    params.append('mode', mode);
    var url = '{{ route("timesheets.print-batch") }}?' + params.toString();
    window.open(url, '_blank');
    closeModal('batchPrintModal');
}
</script>
@include('timesheets.partials.timesheet-edit-script')
@endpush

@endsection
