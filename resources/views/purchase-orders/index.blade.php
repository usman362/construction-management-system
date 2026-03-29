@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Purchase Orders</h1>
    <button onclick="openModal('createModal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Create Purchase Order
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th>PO #</th><th>Date</th><th>Vendor</th><th>Project</th><th>Total</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="text-lg font-bold text-gray-900">Create Purchase Order</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                    <select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select project</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select vendor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Code *</label>
                    <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select cost code</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PO Date *</label>
                    <input type="date" name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                    <input type="date" name="delivery_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="2"></textarea>
                </div>
            </div>

            <!-- Dynamic Items Section -->
            <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Line Items</h4>
                    <button type="button" onclick="addItemRow('createItemsTable')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Line Item
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table id="createItemsTable" class="w-full text-sm border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Description</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-16">Qty</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700 w-16">UOM</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Unit Cost</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Total</th>
                                <th class="px-3 py-2 text-center w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="createItemsBody"></tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-900">Subtotal:</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">$<span id="createSubtotal">0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitCreateForm()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Create Purchase Order</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="text-lg font-bold text-gray-900">Edit Purchase Order</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <input type="hidden" name="po_id" id="editPoId">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                    <select name="project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select project</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select vendor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Code *</label>
                    <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                        <option value="">Select cost code</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PO Date *</label>
                    <input type="date" name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                    <input type="date" name="delivery_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="2"></textarea>
                </div>
            </div>

            <!-- Dynamic Items Section -->
            <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Line Items</h4>
                    <button type="button" onclick="addItemRow('editItemsTable')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Line Item
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table id="editItemsTable" class="w-full text-sm border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Description</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-16">Qty</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700 w-16">UOM</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Unit Cost</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Total</th>
                                <th class="px-3 py-2 text-center w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="editItemsBody"></tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-900">Subtotal:</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">$<span id="editSubtotal">0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitEditForm()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Changes</button>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('viewModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="text-lg font-bold text-gray-900">Purchase Order <span id="viewPoNumber"></span></h3>
            <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Project</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewProject">—</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Vendor</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewVendor">—</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Cost Code</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewCostCode">—</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">PO Date</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewPoDate">—</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Delivery Date</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewDeliveryDate">—</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Status</label>
                    <p class="text-sm font-medium text-gray-900 mt-1" id="viewStatus">—</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Description</label>
                    <p class="text-sm text-gray-700 mt-1" id="viewDescription">—</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Notes</label>
                    <p class="text-sm text-gray-700 mt-1" id="viewNotes">—</p>
                </div>
            </div>

            <!-- Line Items -->
            <div class="border-t border-gray-200 pt-4">
                <h4 class="font-semibold text-gray-900 mb-3">Line Items</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Description</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-16">Qty</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700 w-16">UOM</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Unit Cost</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700 w-24">Total</th>
                            </tr>
                        </thead>
                        <tbody id="viewItemsBody"></tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-900">Subtotal:</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">$<span id="viewSubtotal">0.00</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('viewModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var table;
var projects = [];
var vendors = [];
var costCodes = [];
var materials = [];

function loadDropdowns() {
    $.ajax({
        url: '/projects',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            projects = data;
            populateDropdown('createForm [name="project_id"]', data);
            populateDropdown('editForm [name="project_id"]', data);
        }
    });

    $.ajax({
        url: '/vendors',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            vendors = data;
            populateDropdown('createForm [name="vendor_id"]', data);
            populateDropdown('editForm [name="vendor_id"]', data);
        }
    });

    $.ajax({
        url: '/cost-codes',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            costCodes = data;
            populateDropdown('createForm [name="cost_code_id"]', data);
            populateDropdown('editForm [name="cost_code_id"]', data);
        }
    });

    $.ajax({
        url: '/materials',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            materials = data;
        }
    });
}

function populateDropdown(selector, data) {
    var $select = $(selector);
    data.forEach(function(item) {
        var label = item.name || item.project_name || item.code_name || item.vendor_name || item.description;
        $select.append('<option value="' + item.id + '">' + label + '</option>');
    });
}

function addItemRow(tableId) {
    var tbody = document.getElementById(tableId === 'createItemsTable' ? 'createItemsBody' : 'editItemsBody');
    var rowCount = tbody.children.length;
    var row = document.createElement('tr');
    row.className = 'border-t border-gray-200 hover:bg-gray-50';
    row.innerHTML = '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm" placeholder="Item description"></td>' +
        '<td class="px-3 py-2"><input type="number" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right qty-input" placeholder="0" step="0.01"></td>' +
        '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center" placeholder="EA"></td>' +
        '<td class="px-3 py-2"><input type="number" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right unit-cost-input" placeholder="0.00" step="0.01"></td>' +
        '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right bg-gray-100 font-medium item-total" readonly value="$0.00"></td>' +
        '<td class="px-3 py-2 text-center"><button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    tbody.appendChild(row);
    attachItemEventListeners(row);
}

function attachItemEventListeners(row) {
    var qtyInput = row.querySelector('.qty-input');
    var unitCostInput = row.querySelector('.unit-cost-input');

    qtyInput.addEventListener('input', function() { calculateItemTotal(row); });
    unitCostInput.addEventListener('input', function() { calculateItemTotal(row); });
}

function calculateItemTotal(row) {
    var qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    var unitCost = parseFloat(row.querySelector('.unit-cost-input').value) || 0;
    var total = qty * unitCost;
    row.querySelector('.item-total').value = '$' + total.toFixed(2);
    calculateTotals();
}

function removeItemRow(btn) {
    btn.closest('tr').remove();
    calculateTotals();
}

function calculateTotals() {
    var createItems = document.querySelectorAll('#createItemsBody tr');
    var createTotal = 0;
    createItems.forEach(function(row) {
        var itemTotal = parseFloat(row.querySelector('.item-total').value.replace('$', '')) || 0;
        createTotal += itemTotal;
    });
    document.getElementById('createSubtotal').textContent = createTotal.toFixed(2);

    var editItems = document.querySelectorAll('#editItemsBody tr');
    var editTotal = 0;
    editItems.forEach(function(row) {
        var itemTotal = parseFloat(row.querySelector('.item-total').value.replace('$', '')) || 0;
        editTotal += itemTotal;
    });
    document.getElementById('editSubtotal').textContent = editTotal.toFixed(2);
}

function submitCreateForm() {
    if (!document.getElementById('createForm').checkValidity()) {
        Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.' });
        return;
    }

    var items = [];
    document.querySelectorAll('#createItemsBody tr').forEach(function(row) {
        items.push({
            description: row.cells[0].querySelector('input').value,
            quantity: parseFloat(row.querySelector('.qty-input').value) || 0,
            unit_of_measure: row.cells[2].querySelector('input').value,
            unit_cost: parseFloat(row.querySelector('.unit-cost-input').value) || 0
        });
    });

    var data = {
        project_id: document.querySelector('#createForm [name="project_id"]').value,
        vendor_id: document.querySelector('#createForm [name="vendor_id"]').value,
        cost_code_id: document.querySelector('#createForm [name="cost_code_id"]').value,
        date: document.querySelector('#createForm [name="date"]').value,
        delivery_date: document.querySelector('#createForm [name="delivery_date"]').value,
        description: document.querySelector('#createForm [name="description"]').value,
        notes: document.querySelector('#createForm [name="notes"]').value,
        items: items
    };

    $.ajax({
        url: '{{ route("purchase-orders.store") }}',
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            Swal.fire({ icon: 'success', title: 'Success', text: 'Purchase Order created successfully!', timer: 2000 });
            closeModal('createModal');
            document.getElementById('createForm').reset();
            document.getElementById('createItemsBody').innerHTML = '';
            document.getElementById('createSubtotal').textContent = '0.00';
            table.ajax.reload();
        },
        error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to create purchase order.' });
        }
    });
}

function editPO(id) {
    $.ajax({
        url: '/purchase-orders/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(po) {
            document.getElementById('editPoId').value = id;
            document.querySelector('#editForm [name="project_id"]').value = po.project_id;
            document.querySelector('#editForm [name="vendor_id"]').value = po.vendor_id;
            document.querySelector('#editForm [name="cost_code_id"]').value = po.cost_code_id;
            document.querySelector('#editForm [name="date"]').value = po.date;
            document.querySelector('#editForm [name="delivery_date"]').value = po.delivery_date || '';
            document.querySelector('#editForm [name="description"]').value = po.description || '';
            document.querySelector('#editForm [name="notes"]').value = po.notes || '';

            var itemsBody = document.getElementById('editItemsBody');
            itemsBody.innerHTML = '';
            if (po.items && po.items.length > 0) {
                po.items.forEach(function(item) {
                    var row = document.createElement('tr');
                    row.className = 'border-t border-gray-200 hover:bg-gray-50';
                    row.innerHTML = '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm" value="' + (item.description || '') + '"></td>' +
                        '<td class="px-3 py-2"><input type="number" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right qty-input" value="' + item.quantity + '" step="0.01"></td>' +
                        '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center" value="' + (item.unit_of_measure || '') + '"></td>' +
                        '<td class="px-3 py-2"><input type="number" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right unit-cost-input" value="' + item.unit_cost + '" step="0.01"></td>' +
                        '<td class="px-3 py-2"><input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-right bg-gray-100 font-medium item-total" readonly value="$' + (item.total_cost || 0).toFixed(2) + '"></td>' +
                        '<td class="px-3 py-2 text-center"><button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
                    itemsBody.appendChild(row);
                    attachItemEventListeners(row);
                });
            }
            calculateTotals();
            openModal('editModal');
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load purchase order.' });
        }
    });
}

function submitEditForm() {
    var poId = document.getElementById('editPoId').value;
    if (!document.getElementById('editForm').checkValidity()) {
        Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.' });
        return;
    }

    var items = [];
    document.querySelectorAll('#editItemsBody tr').forEach(function(row) {
        items.push({
            description: row.cells[0].querySelector('input').value,
            quantity: parseFloat(row.querySelector('.qty-input').value) || 0,
            unit_of_measure: row.cells[2].querySelector('input').value,
            unit_cost: parseFloat(row.querySelector('.unit-cost-input').value) || 0
        });
    });

    var data = {
        project_id: document.querySelector('#editForm [name="project_id"]').value,
        vendor_id: document.querySelector('#editForm [name="vendor_id"]').value,
        cost_code_id: document.querySelector('#editForm [name="cost_code_id"]').value,
        date: document.querySelector('#editForm [name="date"]').value,
        delivery_date: document.querySelector('#editForm [name="delivery_date"]').value,
        description: document.querySelector('#editForm [name="description"]').value,
        notes: document.querySelector('#editForm [name="notes"]').value,
        items: items
    };

    $.ajax({
        url: '/purchase-orders/' + poId,
        type: 'PUT',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            Swal.fire({ icon: 'success', title: 'Success', text: 'Purchase Order updated successfully!', timer: 2000 });
            closeModal('editModal');
            table.ajax.reload();
        },
        error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to update purchase order.' });
        }
    });
}

function viewPO(id) {
    $.ajax({
        url: '/purchase-orders/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(po) {
            document.getElementById('viewPoNumber').textContent = po.po_number;
            document.getElementById('viewProject').textContent = po.project || '—';
            document.getElementById('viewVendor').textContent = po.vendor || '—';
            document.getElementById('viewCostCode').textContent = po.cost_code || '—';
            document.getElementById('viewPoDate').textContent = po.date ? new Date(po.date).toLocaleDateString() : '—';
            document.getElementById('viewDeliveryDate').textContent = po.delivery_date ? new Date(po.delivery_date).toLocaleDateString() : '—';

            var statusColors = {
                'draft': 'bg-gray-100 text-gray-700',
                'issued': 'bg-blue-100 text-blue-700',
                'partial': 'bg-yellow-100 text-yellow-700',
                'received': 'bg-green-100 text-green-700',
                'closed': 'bg-gray-100 text-gray-700',
                'cancelled': 'bg-red-100 text-red-700'
            };
            var statusClass = statusColors[po.status] || 'bg-gray-100 text-gray-700';
            document.getElementById('viewStatus').innerHTML = '<span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ' + statusClass + '">' + po.status + '</span>';

            document.getElementById('viewDescription').textContent = po.description || '—';
            document.getElementById('viewNotes').textContent = po.notes || '—';

            var itemsBody = document.getElementById('viewItemsBody');
            itemsBody.innerHTML = '';
            var subtotal = 0;
            if (po.items && po.items.length > 0) {
                po.items.forEach(function(item) {
                    var row = document.createElement('tr');
                    row.className = 'border-t border-gray-200';
                    row.innerHTML = '<td class="px-3 py-2">' + (item.description || '—') + '</td>' +
                        '<td class="px-3 py-2 text-right">' + item.quantity + '</td>' +
                        '<td class="px-3 py-2 text-center">' + (item.unit_of_measure || '—') + '</td>' +
                        '<td class="px-3 py-2 text-right">$' + parseFloat(item.unit_cost).toFixed(2) + '</td>' +
                        '<td class="px-3 py-2 text-right font-medium">$' + parseFloat(item.total_cost).toFixed(2) + '</td>';
                    itemsBody.appendChild(row);
                    subtotal += parseFloat(item.total_cost) || 0;
                });
            }
            document.getElementById('viewSubtotal').textContent = subtotal.toFixed(2);
            openModal('viewModal');
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load purchase order.' });
        }
    });
}

function deletePO(id) {
    Swal.fire({
        title: 'Delete Purchase Order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/purchase-orders/' + id,
                type: 'DELETE',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire({ icon: 'success', title: 'Success', text: 'Purchase Order deleted successfully!', timer: 2000 });
                    table.ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to delete purchase order.' });
                }
            });
        }
    });
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

$(document).ready(function() {
    loadDropdowns();

    table = $('#dataTable').DataTable({
        ajax: '{{ route("purchase-orders.index") }}',
        columns: [
            {data: 'po_number'},
            {data: 'date'},
            {data: 'vendor'},
            {data: 'project'},
            {data: 'total_amount', render: d => '$' + parseFloat(d || 0).toFixed(2), className: 'text-right'},
            {data: 'status', className: 'text-center', render: function(d) {
                var colors = {'draft':'bg-gray-100 text-gray-700','issued':'bg-blue-100 text-blue-700','partial':'bg-yellow-100 text-yellow-700','received':'bg-green-100 text-green-700','closed':'bg-gray-100 text-gray-700','cancelled':'bg-red-100 text-red-700'};
                return '<span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ' + (colors[d] || 'bg-gray-100 text-gray-700') + '">' + d + '</span>';
            }},
            {data: 'id', orderable: false, searchable: false, className: 'text-center',
             render: function(id) {
                return '<div class="flex items-center justify-center gap-1">' +
                    '<button onclick="viewPO(' + id + ')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>' +
                    '<button onclick="editPO(' + id + ')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>' +
                    '<button onclick="deletePO(' + id + ')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button>' +
                    '</div>';
            }}
        ],
        serverSide: true,
        processing: true,
        pagingType: 'simple_numbers',
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        language: {
            loadingRecords: 'Loading...',
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
        }
    });
});
</script>
@endpush

@endsection
