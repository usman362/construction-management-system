@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage system users and their roles</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add User
        </button>
    </div>

    <!-- Role Legend -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Role Permissions</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-xs">
            <div class="p-2 rounded bg-red-50 border border-red-100">
                <span class="font-bold text-red-800">Admin</span>
                <p class="text-red-600 mt-1">Full access to everything</p>
            </div>
            <div class="p-2 rounded bg-blue-50 border border-blue-100">
                <span class="font-bold text-blue-800">Project Manager</span>
                <p class="text-blue-600 mt-1">Projects, workforce, timesheets, procurement, reports</p>
            </div>
            <div class="p-2 rounded bg-green-50 border border-green-100">
                <span class="font-bold text-green-800">Accountant</span>
                <p class="text-green-600 mt-1">Payroll, billing, invoices, cost codes, timesheets</p>
            </div>
            <div class="p-2 rounded bg-amber-50 border border-amber-100">
                <span class="font-bold text-amber-800">Field Staff</span>
                <p class="text-amber-600 mt-1">Timesheets, equipment, materials, daily logs, crews</p>
            </div>
            <div class="p-2 rounded bg-gray-50 border border-gray-100">
                <span class="font-bold text-gray-800">Viewer</span>
                <p class="text-gray-600 mt-1">Dashboard and projects (read-only)</p>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table id="dataTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Role</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Joined</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New User</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required minlength="8">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("users.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Create User</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit User</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" id="edit_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" id="edit_email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></label>
                <input type="password" name="password" id="edit_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" minlength="8">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" id="edit_role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_active" id="edit_is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update User</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var table;

$(document).ready(function() {
    table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("users.index") }}',
        columns: [
            {data: 'id', name: 'id', width: '50px'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'role_label', name: 'role', render: function(data, type, row) {
                var colors = {
                    'admin': 'bg-red-100 text-red-800',
                    'project_manager': 'bg-blue-100 text-blue-800',
                    'accountant': 'bg-green-100 text-green-800',
                    'field': 'bg-amber-100 text-amber-800',
                    'viewer': 'bg-gray-100 text-gray-800'
                };
                var cls = colors[row.role] || 'bg-gray-100 text-gray-800';
                return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + cls + '">' + data + '</span>';
            }, className: 'text-center'},
            {data: 'is_active', name: 'is_active', render: function(data) {
                if (data) {
                    return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>';
                }
                return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>';
            }, className: 'text-center'},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', orderable: false, searchable: false, className: 'text-center',
                render: function(id) {
                    return '<div class="flex items-center justify-center gap-1">' +
                        '<button onclick="editUser(' + id + ')" class="p-1 text-gray-400 hover:text-amber-600" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>' +
                        '<button onclick="deleteUser(' + id + ')" class="p-1 text-gray-400 hover:text-red-600" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                    '</div>';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 15
    });
});

function openCreateModal() {
    document.getElementById('createForm').reset();
    openModal('createModal');
}

function editUser(id) {
    $.get('/users/' + id + '/edit', function(d) {
        document.getElementById('edit_name').value = d.name || '';
        document.getElementById('edit_email').value = d.email || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role').value = d.role || 'viewer';
        document.getElementById('edit_is_active').value = d.is_active ? '1' : '0';
        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', '/users/' + d.id, 'PUT', table, 'editModal');
        };
        openModal('editModal');
    });
}

function deleteUser(id) {
    confirmDelete('/users/' + id, table);
}
</script>
@endpush

@endsection
