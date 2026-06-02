@extends('layouts.app')

@section('title', 'Change Orders - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Change Orders</h1>
        </div>
        <div class="flex items-center gap-2">
            {{-- Project-scoped export: filter the company-wide change-orders endpoint
                 down to just this project. --}}
            <a href="{{ route('exports.change-orders', ['project_id' => $project->id]) }}"
               class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-emerald-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-emerald-200 transition"
               title="Download change orders for this project as Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export
            </a>
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Change Order
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    @php
        $approvedTotal = \App\Models\ChangeOrder::where('project_id', $project->id)->where('status', 'approved')->sum('amount');
        $pendingTotal  = \App\Models\ChangeOrder::where('project_id', $project->id)->where('status', 'pending')->sum('amount');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Approved CO Amount</h3>
            <p class="text-3xl font-bold text-green-600">${{ number_format($approvedTotal, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Pending</h3>
            <p class="text-3xl font-bold text-yellow-600">${{ number_format($pendingTotal, 2) }}</p>
        </div>
    </div>

    {{-- 2026-05-23 (KH): filter strip — status dropdown + amount range +
         pricing type. The DataTables built-in search box (top right) still
         works for free-text. Combined with column sort headers, this
         covers the "sort / filter" ask on her CO screenshot. --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 p-3 mb-4">
        <div class="flex items-center gap-3 flex-wrap">
            <label class="text-xs font-semibold text-gray-600">Filter:</label>
            {{-- 2026-05-23 (Brenda): filter list includes her 5 + legacy
                 rejected/voided so old data is still discoverable. --}}
            <select id="coStatusFilter" onchange="reloadCOs()" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                <option value="">Any status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="revising">Revising</option>
                <option value="cancelled">Cancelled</option>
                <option value="potential">Potential</option>
                <option value="rejected">Rejected (legacy)</option>
                <option value="voided">Voided (legacy)</option>
            </select>
            <select id="coPricingFilter" onchange="reloadCOs()" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                <option value="">Any pricing type</option>
                <option value="lump_sum">Lump Sum</option>
                <option value="t_and_m">T &amp; M</option>
            </select>
            <button type="button" onclick="document.getElementById('coStatusFilter').value=''; document.getElementById('coPricingFilter').value=''; reloadCOs();"
                    class="text-xs text-gray-600 hover:text-gray-900 underline">Reset</button>
            <span class="ml-auto text-[11px] text-gray-500">Click any column header to sort • Use the search box (top right) to filter by text</span>
        </div>
    </div>

    <!-- Change Orders Table (layout mirrors Commitments — CO #, Client PO #,
         Phase Code, Cost Type, Description, Amount, Type, Status, Actions) -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table id="dataTable" class="w-full">
            {{-- 2026-05-23 (Brenda): hid Phase Code + Cost Type columns
                 — they always rendered "—" because COs aggregate line
                 items rather than carrying direct phase_code/cost_type
                 fields. Removed from header AND from the JS columns
                 list below. --}}
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">CO #</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Client PO #</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Type</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="createModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Change Order</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CO Number</label>
                    <input type="text" name="co_number" placeholder="Auto-generate if blank" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label>
                    <input type="text" name="client_po" placeholder="Client's PO reference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pricing Type *</label>
                    <select name="pricing_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="lump_sum">Lump Sum</option>
                        <option value="t_and_m">T &amp; M</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    {{-- 2026-05-23 (Brenda): her requested 5 options.
                         Legacy 'rejected'/'voided' still in the DB enum
                         for backward compat but not surfaced here. --}}
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Select Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="revising">Revising</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="potential">Potential</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('createForm','{{ route("projects.change-orders.store", $project) }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Change Order</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="editModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Change Order</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="_id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CO Number</label>
                    <input type="text" name="co_number" placeholder="Auto-generate if blank" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label>
                    <input type="text" name="client_po" placeholder="Client's PO reference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pricing Type *</label>
                    <select name="pricing_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="lump_sum">Lump Sum</option>
                        <option value="t_and_m">T &amp; M</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    {{-- 2026-05-23 (Brenda): her requested 5 options.
                         Legacy 'rejected'/'voided' still in the DB enum
                         for backward compat but not surfaced here. --}}
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Select Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="revising">Revising</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="potential">Potential</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update Change Order</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var table;

// 2026-05-23 (KH): trigger a fresh DataTables fetch when status/pricing
// filters change. The server-side dataTable reads these query params.
function reloadCOs() { if (table) table.ajax.reload(null, false); }

$(document).ready(function() {
    table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route("projects.change-orders.index", $project) }}',
            data: function (d) {
                d.status        = document.getElementById('coStatusFilter')?.value  || '';
                d.pricing_type  = document.getElementById('coPricingFilter')?.value || '';
            },
        },
        columns: [
            {data:'co_number', name:'co_number'},
            {data:'client_po', name:'client_po', render: function(d){return d?d:'<span class="text-gray-400">—</span>';}},
            // 2026-05-23 (Brenda): phase_code + cost_type columns removed.
            {data:'description', name:'description', render: function(d){return d?d:'<span class="text-gray-400">—</span>';}},
            // 2026-05-23 (Brenda): comma-format dollar amounts.
            {data:'amount', name:'amount', render: d=>'$'+Number(d||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}), className:'text-right'},
            {data:'pricing_type', name:'pricing_type', orderable:false, className:'text-center', render: function(d) {
                if (d === 't_and_m') return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">T &amp; M</span>';
                return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Lump Sum</span>';
            }},
            {data:'status', name:'status', render: function(d) {
                // 2026-05-23 (Brenda): colors picked to roughly match the
                // tint strip she sent in her screenshot.
                const statusColors = {
                    'pending':   'bg-pink-100 text-pink-800',
                    'approved':  'bg-green-100 text-green-800',
                    'revising':  'bg-rose-100 text-rose-700',
                    'cancelled': 'bg-red-100 text-red-800',
                    'potential': 'bg-purple-100 text-purple-800',
                    'rejected':  'bg-red-100 text-red-700',
                    'voided':    'bg-gray-200 text-gray-700',
                };
                const labels = {
                    'pending':'Pending','approved':'Approved','revising':'Revising',
                    'cancelled':'Cancelled','potential':'Potential',
                    'rejected':'Rejected','voided':'Voided',
                };
                const label = labels[d] || (d ? d.charAt(0).toUpperCase()+d.slice(1) : '—');
                return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium '+(statusColors[d]||'bg-gray-100 text-gray-800')+'">'+label+'</span>';
            }, className:'text-center'},
            {data:'actions', orderable:false, searchable:false, className:'text-center',
                render: function(id) {
                    return `<div class="flex items-center justify-center gap-1">
                        <button onclick="viewChangeOrder(${id})" class="p-1 text-gray-400 hover:text-blue-600" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                        <a href="${window.BASE_URL}/projects/{{ $project->id }}/change-orders/${id}/pdf" class="p-1 text-gray-400 hover:text-green-600" title="Download PDF"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></a>
                        <button onclick="editChangeOrder(${id})" class="p-1 text-gray-400 hover:text-amber-600" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                        <button onclick="deleteChangeOrder(${id})" class="p-1 text-gray-400 hover:text-red-600" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>`;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        language: {
            emptyTable: 'No change orders found.',
            processing: 'Loading...'
        }
    });
});

function openCreateModal() {
    document.getElementById('createForm').reset();
    openModal('createModal');
}

function deleteChangeOrder(id) {
    confirmDelete(window.BASE_URL+'/projects/{{ $project->id }}/change-orders/' + id, table);
}

function editChangeOrder(id) {
    $.get(window.BASE_URL+'/projects/{{ $project->id }}/change-orders/' + id + '/edit', function(d) {
        let f = document.getElementById('editForm');
        f.querySelector('#edit_id').value = d.id;
        f.querySelectorAll('[name="co_number"]').forEach(el => el.value = d.co_number || '');
        f.querySelectorAll('[name="client_po"]').forEach(el => el.value = d.client_po || '');
        f.querySelector('[name="title"]').value = d.title || '';
        f.querySelector('[name="description"]').value = d.description || '';
        f.querySelector('[name="amount"]').value = d.amount || '';
        f.querySelector('[name="status"]').value = d.status || '';
        f.querySelector('[name="pricing_type"]').value = d.pricing_type || 'lump_sum';
        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', window.BASE_URL+'/projects/{{ $project->id }}/change-orders/' + d.id, 'PUT', table, 'editModal');
        };
        openModal('editModal');
    });
}

function viewChangeOrder(id) {
    window.location.href = window.BASE_URL+'/projects/{{ $project->id }}/change-orders/' + id;
}

// Auto-open edit modal if ?edit= param is in URL
$(document).ready(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var editId = urlParams.get('edit');
    if (editId) {
        editChangeOrder(editId);
    }
});
</script>
@endpush

@endsection
