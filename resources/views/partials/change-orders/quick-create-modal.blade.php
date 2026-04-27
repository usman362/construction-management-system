{{--
    Dashboard "Quick Add Change Order" modal.

    A CO needs a project context. Instead of a separate project-picker step,
    the project dropdown is the FIRST field in this modal — pick project,
    fill in CO details, save. The form's action URL is built dynamically
    from the chosen project_id since the route is project-scoped.

    Required props:
    - $allProjects   Collection<Project> — for the picker
--}}
<div id="quickCoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('quickCoModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Quick Add Change Order</h3>
                <p class="text-xs text-gray-500 mt-0.5">Pick the project, then enter CO details. For line items + labor breakdown, open the CO from the project page after saving.</p>
            </div>
            <button onclick="closeModal('quickCoModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="quickCoForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                <select id="quickCoProjectId" name="project_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">— Pick a project —</option>
                    @foreach($allProjects ?? [] as $p)
                        <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CO Number</label>
                    <input type="text" name="co_number" placeholder="Auto-generate if blank" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label>
                    <input type="text" name="client_po" placeholder="Client's PO reference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pricing Type *</label>
                    <select name="pricing_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="lump_sum">Lump Sum</option>
                        <option value="time_and_materials">T&amp;M</option>
                        <option value="not_to_exceed">Not to Exceed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Days</label>
                <input type="number" name="contract_time_change_days" placeholder="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('quickCoModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="saveQuickCo()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">Save Change Order</button>
        </div>
    </div>
</div>
