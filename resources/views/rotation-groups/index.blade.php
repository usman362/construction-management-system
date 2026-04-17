@extends('layouts.app')
@section('title', 'Rotation Groups')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Rotation Groups</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage crew rotation schedules (Rolling 4s, Rolling 8s, etc.)</p>
    </div>
    <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Rotation Group
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>Code</th><th>Name</th><th>Project</th><th>Pattern</th><th>Current Shift</th><th>Employees</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

@php
    $patterns = [
        '4_on_4_off' => 'Rolling 4s (4 days on, 4 days off)',
        '8_on_8_off_rotating' => 'Rolling 8s (4 on day, 4 off, 4 on night, 4 off)',
        '4_on_3_off' => '4 on / 3 off (weekly)',
        'custom' => 'Custom (manual week-by-week)',
    ];
@endphp

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add Rotation Group</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                <select name="project_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select project —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->project_number ? ' (' . $p->project_number . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" placeholder="e.g. G1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" placeholder="e.g. Group 1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pattern *</label>
                <select name="pattern" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($patterns as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Shift</label>
                <select name="current_shift" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="day">Day</option>
                    <option value="night">Night</option>
                    <option value="off">Off</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route('rotation-groups.store') }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Rotation Group</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="_id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                <select name="project_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->project_number ? ' (' . $p->project_number . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pattern *</label>
                <select name="pattern" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($patterns as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Shift</label>
                <select name="current_shift" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="day">Day</option>
                    <option value="night">Night</option>
                    <option value="off">Off</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </div>
</div>

<!-- View Modal (shows the 26-week schedule grid) -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('viewModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-gray-900">Schedule</h3>
            <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div id="viewContent" class="p-6">Loading...</div>
    </div>
</div>

@push('scripts')
<script>
var table = $('#dataTable').DataTable({
    ajax: '{{ route('rotation-groups.index') }}',
    columns: [
        {data:'code'},
        {data:'name'},
        {data:'project', render: d=>d||'<span class="text-gray-400">—</span>'},
        {data:'pattern', render: d=>'<span class="capitalize">'+d+'</span>'},
        {data:'current_shift', render: d=>d?'<span class="capitalize px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">'+d+'</span>':'<span class="text-gray-400">—</span>'},
        {data:'employees_count', className:'text-center'},
        {data:'is_active', className:'text-center', render: d=>d?'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>':'<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>'},
        {data:'actions', orderable:false, searchable:false, className:'text-center',
         render: function(data) {
            return '<div class="flex items-center justify-center gap-1">'+
                '<button onclick="viewGroup('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="Schedule"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg></button>'+
                '<button onclick="editGroup('+data+')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>'+
                '<button onclick="confirmDelete(window.BASE_URL+\'/rotation-groups/'+data+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M4.772 5.79L5.84 19.673a2.25 2.25 0 002.244 2.077h7.832a2.25 2.25 0 002.244-2.077L18.228 5.79"/></svg></button></div>';
        }}
    ]
});

function openCreateModal(){ document.getElementById('createForm').reset(); openModal('createModal'); }

function editGroup(id){
    $.get(window.BASE_URL+'/rotation-groups/'+id+'/edit', function(d){
        var f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="project_id"]').value=d.project_id;
        f.querySelector('[name="code"]').value=d.code;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="pattern"]').value=d.pattern;
        f.querySelector('[name="current_shift"]').value=d.current_shift||'day';
        f.querySelector('[name="notes"]').value=d.notes||'';
        document.getElementById('editSaveBtn').onclick=function(){
            submitForm('editForm', window.BASE_URL+'/rotation-groups/'+d.id, 'PUT', table, 'editModal');
        };
        openModal('editModal');
    });
}

function viewGroup(id){
    $.get(window.BASE_URL+'/rotation-groups/'+id, function(d){
        var rows = (d.schedule || []).map(function(s){
            var cls = s.is_working
                ? (s.shift_type === 'night' ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800')
                : 'bg-gray-100 text-gray-500';
            var lbl = s.is_working
                ? (s.shift_type === 'night' ? 'Night' : 'Day')
                : 'OFF';
            return '<div class="flex items-center justify-between px-3 py-2 border-b border-gray-100"><span class="text-xs text-gray-600">Week ending '+s.week_ending_date+'</span><span class="text-xs font-medium px-2 py-0.5 rounded '+cls+'">'+lbl+'</span></div>';
        }).join('');
        document.getElementById('viewContent').innerHTML=
            '<div class="mb-4"><p class="text-sm text-gray-500">'+(d.code||'')+' — '+(d.name||'')+'</p><p class="text-xs text-gray-400">Pattern: '+(d.pattern||'').replace(/_/g,' ')+'</p></div>'+
            (rows || '<p class="text-sm text-gray-500">No schedule generated.</p>');
        openModal('viewModal');
    });
}
</script>
@endpush
@endsection
