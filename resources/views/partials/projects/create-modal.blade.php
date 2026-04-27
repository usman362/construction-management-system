{{--
    Reusable Project Create Modal.

    Included by both `projects/index.blade.php` and `dashboard.blade.php` so the
    same modal HTML lives in one file — no field-shape drift between the two
    places (the bug Brenda hit on 04.27).

    The host page must:
    - Provide a `loadClients(callback)` JS helper that fills `select[name=client_id]`
    - Define `openCreateModal()` that calls `loadClients(() => openModal('createModal'))`
    - Define `var table = ...` for DataTables, OR pass `null`/omit the param so
      submitForm() falls back to `window.location.reload()` after success
--}}
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
            {{-- `typeof table !== 'undefined' ? table : null` lets the same partial work
                 on projects/index (where `table` is the DataTable) and on the dashboard
                 (where it's undefined → submitForm reloads the page on success). --}}
            <button onclick="submitForm('createForm','{{ route('projects.store') }}','POST', typeof table!=='undefined'?table:null, 'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Project</button>
        </div>
    </div>
</div>
