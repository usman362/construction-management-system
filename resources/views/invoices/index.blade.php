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
            {{-- 2026-05-10 (Brenda): Snap-an-Invoice — AI image capture for
                 vendor invoices. Same UX as Snap-a-Timesheet. --}}
            <button onclick="openInvoiceScanModal()" class="relative inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 hover:from-purple-700 hover:via-fuchsia-700 hover:to-pink-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-md transition" title="Snap a photo of a vendor invoice — AI fills it in for you">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                Scan Invoice
                <span class="absolute -top-1 -right-1 bg-yellow-400 text-[9px] font-black text-purple-900 px-1.5 py-0.5 rounded-full shadow">AI</span>
            </button>
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
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="createModal">
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
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="editModal">
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
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" data-modal-id="viewModal">
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

{{-- ───── Snap-an-Invoice (AI OCR) modal ─────
     Brenda 2026-05-10. Same UX as Snap-a-Timesheet: 3 stages — upload,
     extracting, review — POSTs twice (/invoices/scan-photo for the AI
     extraction, /invoices/scan-commit to save the resulting Invoice). --}}
<div id="invoiceScanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay"
     x-data="snapInvoice()" x-init="init()"
     data-modal-id="invoiceScanModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 max-h-[92vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 text-white">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                <div>
                    <h3 class="text-lg font-bold">Snap-an-Invoice</h3>
                    <p class="text-xs text-purple-100" x-text="stage === 'review' ? (summary || 'Review and save') : 'Photo of a vendor invoice → AI fills in the form'"></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('invoiceScanModal')" class="text-purple-100 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- STAGE 1: upload --}}
        <div x-show="stage === 'upload'" class="p-6">
            <label class="block">
                <input type="file" accept="image/*" capture="environment" class="hidden" @change="onFileSelected($event)">
                <div class="border-2 border-dashed border-purple-300 rounded-xl p-12 text-center hover:bg-purple-50 transition cursor-pointer"
                     @dragover.prevent @drop.prevent="onFileDropped($event)">
                    <svg class="w-16 h-16 mx-auto text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <p class="mt-4 text-base font-semibold text-gray-900">Drop a vendor invoice here, or click to choose</p>
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, HEIC up to 10 MB. Snap with your phone, paste from email — anything works.</p>
                </div>
            </label>
            <div class="mt-6 grid grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">📸 What works</div>
                    <ul class="text-gray-600 space-y-0.5"><li>• Vendor invoices / bills</li><li>• PDF screenshots</li><li>• Scanned receipts</li></ul>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">🤖 What AI extracts</div>
                    <ul class="text-gray-600 space-y-0.5"><li>• Vendor + invoice #</li><li>• Date + total amount</li><li>• PO reference</li></ul>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">✅ You stay in control</div>
                    <ul class="text-gray-600 space-y-0.5"><li>• Review every field</li><li>• Pick project + vendor</li><li>• One click to save</li></ul>
                </div>
            </div>
        </div>

        {{-- STAGE 2: extracting --}}
        <div x-show="stage === 'extracting'" class="p-12 text-center">
            <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-500 animate-pulse">
                <svg class="w-10 h-10 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <p class="mt-6 text-lg font-bold text-gray-900" x-text="extractStatus"></p>
            <p class="mt-2 text-sm text-gray-500">This usually takes 3–8 seconds.</p>
        </div>

        {{-- STAGE 3: review --}}
        <div x-show="stage === 'review'" class="flex-1 overflow-hidden flex flex-col">
            <div class="grid grid-cols-12 gap-4 p-6 overflow-y-auto" style="max-height: calc(92vh - 180px);">
                <div class="col-span-5">
                    <p class="text-xs font-semibold text-gray-700 uppercase mb-2">Original photo</p>
                    <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                        <img :src="photoPreview" alt="Uploaded invoice" class="w-full h-auto">
                    </div>
                    <div class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                        <p class="text-xs font-semibold text-purple-900 mb-1">AI Summary</p>
                        <p class="text-xs text-purple-800" x-text="summary || 'No summary'"></p>
                    </div>
                </div>
                <div class="col-span-7 space-y-3">
                    <p class="text-xs font-semibold text-gray-700 uppercase">Extracted invoice — review &amp; correct</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Project *</label>
                            <select x-model="form.project_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">— pick a project —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->project_number ?? $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Vendor *</label>
                            <select x-model="form.vendor_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">— pick a vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] mt-0.5 text-gray-500" x-show="form.vendor_name_hint">
                                AI saw: <span class="font-semibold" x-text="form.vendor_name_hint"></span>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Invoice # *</label>
                            <input type="text" x-model="form.invoice_number" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Amount $ *</label>
                            <input type="number" step="0.01" min="0" x-model="form.amount" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-semibold text-right">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Invoice Date *</label>
                            <input type="date" x-model="form.invoice_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Due Date</label>
                            <input type="date" x-model="form.due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cost Code *</label>
                        <select x-model="form.cost_code_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">— pick cost code —</option>
                            @foreach($costCodes as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description / Notes</label>
                        <textarea x-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Optional"></textarea>
                        <p class="text-[10px] mt-0.5 text-gray-500" x-show="form.po_reference_hint">
                            AI saw PO ref: <span class="font-semibold" x-text="form.po_reference_hint"></span>
                        </p>
                    </div>

                    <template x-if="lineItems.length > 0">
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Line items detected (informational)</p>
                            <div class="text-xs border border-gray-200 rounded p-2 bg-gray-50 max-h-32 overflow-y-auto">
                                <template x-for="(li, idx) in lineItems" :key="idx">
                                    <div class="flex justify-between border-b border-gray-100 last:border-0 py-1">
                                        <span x-text="li.description || '—'"></span>
                                        <span class="font-mono" x-text="'$' + Number(li.amount || 0).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" @click="reset()" class="px-4 py-2 text-sm bg-white border border-gray-300 hover:bg-gray-50 rounded-lg">Start over</button>
                <button type="button" @click="commit()" :disabled="!canCommit"
                        class="px-5 py-2 text-sm bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!committing">✓ Save Invoice</span>
                    <span x-show="committing">Saving…</span>
                </button>
            </div>
        </div>

        <div x-show="banner.text" x-transition class="px-6 py-3" :class="banner.kind === 'error' ? 'bg-red-50 text-red-800 border-t border-red-200' : 'bg-green-50 text-green-800 border-t border-green-200'">
            <p class="text-sm font-semibold" x-text="banner.text"></p>
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
        {data:'amount', render: d=>window.fmtMoney(d)},
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
            '<div class="grid grid-cols-2 gap-4"><div><p class="text-xs text-gray-500 mb-1">Amount</p><p class="text-sm font-semibold">'+window.fmtMoney(d.amount)+'</p></div><div><p class="text-xs text-gray-500 mb-1">Status</p><p class="text-sm font-semibold capitalize">'+d.status+'</p></div></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Due Date</p><p class="text-sm">'+(d.due_date||'—')+'</p></div>'+
            '<div><p class="text-xs text-gray-500 mb-1">Description</p><p class="text-sm">'+(d.description||'—')+'</p></div>'+
            '</div>';
        openModal('viewModal');
    });
}

// 2026-05-10 (Brenda): Snap-an-Invoice — opens the AI capture modal.
function openInvoiceScanModal(){
    openModal('invoiceScanModal');
    const modal = document.getElementById('invoiceScanModal');
    if (modal && modal._x_dataStack) Alpine.$data(modal).reset();
}

function snapInvoice(){
    return {
        stage: 'upload',
        photoFile: null, photoPreview: null, summary: null,
        form: {
            project_id: '', vendor_id: '', vendor_name_hint: '',
            invoice_number: '', invoice_date: '', due_date: '',
            amount: '', cost_code_id: '', commitment_id: null,
            po_reference_hint: '', description: '',
        },
        lineItems: [],
        committing: false,
        banner: { kind: 'success', text: '' },
        extractStatus: 'Reading your invoice…',
        extractStatuses: [
            'Reading your invoice…',
            'Identifying the vendor…',
            'Pulling out the total…',
            'Matching against your records…',
            'Almost done…',
        ],
        extractTimer: null,

        init() {},

        reset() {
            if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }
            this.stage = 'upload';
            this.photoFile = null; this.photoPreview = null; this.summary = null;
            this.form = { project_id: '', vendor_id: '', vendor_name_hint: '',
                          invoice_number: '', invoice_date: '', due_date: '',
                          amount: '', cost_code_id: '', commitment_id: null,
                          po_reference_hint: '', description: '' };
            this.lineItems = [];
            this.banner = { kind: 'success', text: '' };
            this.committing = false;
        },

        onFileSelected(e) { const f = e.target.files && e.target.files[0]; if (f) this.upload(f); },
        onFileDropped(e)  { const f = e.dataTransfer.files && e.dataTransfer.files[0]; if (f) this.upload(f); },

        async upload(file) {
            this.photoFile = file;
            this.photoPreview = URL.createObjectURL(file);
            this.stage = 'extracting';
            this.banner = { kind: 'success', text: '' };

            let i = 0; this.extractStatus = this.extractStatuses[0];
            this.extractTimer = setInterval(() => {
                i = (i + 1) % this.extractStatuses.length;
                this.extractStatus = this.extractStatuses[i];
            }, 1800);

            const fd = new FormData(); fd.append('photo', file);
            try {
                const r = await fetch('{{ route("invoices.scan-photo") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await r.json();
                if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }
                if (!r.ok || !data.success) {
                    this.banner = { kind: 'error', text: data.message || 'AI extraction failed.' };
                    this.stage = 'upload';
                    return;
                }
                const h = data.header || {};
                this.summary = data.summary;
                this.form.vendor_name_hint   = h.vendor_name || '';
                this.form.vendor_id          = h.vendor_id || '';
                this.form.invoice_number     = h.invoice_number || '';
                this.form.invoice_date       = h.invoice_date || '';
                this.form.due_date           = h.due_date || '';
                this.form.amount             = h.total_amount || '';
                this.form.po_reference_hint  = h.po_reference || '';
                if (h.purchase_order_id) this.form.commitment_id = h.purchase_order_id;
                this.lineItems = data.line_items || [];
                this.stage = 'review';
            } catch (err) {
                if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }
                this.banner = { kind: 'error', text: 'Network error: ' + (err.message || err) };
                this.stage = 'upload';
            }
        },

        get canCommit() {
            if (this.committing) return false;
            return this.form.project_id && this.form.vendor_id && this.form.cost_code_id
                && this.form.invoice_number && this.form.invoice_date && this.form.amount;
        },

        async commit() {
            if (!this.canCommit) return;
            this.committing = true; this.banner = { kind: 'success', text: '' };
            const payload = {
                project_id:     parseInt(this.form.project_id, 10),
                vendor_id:      parseInt(this.form.vendor_id, 10),
                commitment_id:  this.form.commitment_id || null,
                cost_code_id:   this.form.cost_code_id || null,
                invoice_number: this.form.invoice_number,
                invoice_date:   this.form.invoice_date,
                due_date:       this.form.due_date || null,
                amount:         parseFloat(this.form.amount),
                description:    [this.form.description, this.form.po_reference_hint ? ('PO ref: ' + this.form.po_reference_hint) : ''].filter(Boolean).join(' — ') || null,
            };
            try {
                const r = await fetch('{{ route("invoices.scan-commit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await r.json();
                if (!r.ok || !data.success) {
                    this.banner = { kind: 'error', text: data.message || 'Save failed.' };
                    this.committing = false; return;
                }
                this.banner = { kind: 'success', text: data.message };
                setTimeout(() => {
                    closeModal('invoiceScanModal');
                    this.reset();
                    if (typeof table !== 'undefined' && table.ajax) table.ajax.reload();
                }, 1000);
            } catch (err) {
                this.banner = { kind: 'error', text: 'Network error: ' + (err.message || err) };
                this.committing = false;
            }
        },
    };
}
</script>
@endpush

@endsection
