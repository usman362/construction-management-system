@extends('layouts.app')

@section('title', 'Timesheets')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Timesheets</h1>
    <div class="flex items-center gap-3">
        <a href="{{ route('exports.timesheets') }}" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-emerald-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm border border-emerald-200 transition" title="Download all timesheets as Excel">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export
        </a>
        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Timesheet
        </button>
        <a href="{{ route('timesheets.bulk-create') }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Bulk Entry
        </a>

        {{-- 2026-04-29 — "Snap-a-Timesheet" AI OCR. The standout button on
             this page: gradient + sparkle icon + "AI" pill so it visually
             pops. Click → modal where the user uploads a photo of a paper
             timesheet, AI extracts every row, office confirms in 1 click. --}}
        <button onclick="openScanModal()" class="relative inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 hover:from-purple-700 hover:via-fuchsia-700 hover:to-pink-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-md transition" title="Snap a photo of a paper timesheet — AI fills it in for you">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.898 20.572L16.5 21.75l-.398-1.178a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.178-.398a2.25 2.25 0 001.423-1.423l.398-1.178.398 1.178a2.25 2.25 0 001.423 1.423l1.178.398-1.178.398a2.25 2.25 0 00-1.423 1.423z"/></svg>
            Scan Timesheet
            <span class="absolute -top-1 -right-1 bg-yellow-400 text-[9px] font-black text-purple-900 px-1.5 py-0.5 rounded-full shadow">AI</span>
        </button>
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
                    <label class="block text-xs font-medium text-gray-600 mb-1">Crew <span class="text-gray-400 font-normal">(Print by crew)</span></label>
                    <select name="crew_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">All crews</option>
                        @foreach($crews as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}@if($c->project) ({{ $c->project->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
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

            {{-- 2026-04-28 (Brenda): Layout toggle — "weekly" prints one
                 landscape page per employee per Mon–Sun week, with all
                 7 days laid out in columns and weekly totals. The default
                 "daily" keeps the original one-page-per-timesheet output. --}}
            <div class="border-t border-gray-100 pt-4">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Print Layout</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="layout" value="daily" class="mt-1" checked>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Per Timesheet (Daily)</div>
                            <div class="text-xs text-gray-500">One page per timesheet entry. Best for daily client sign-off and field filing.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="layout" value="weekly" class="mt-1">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Weekly Summary (Per Employee)</div>
                            <div class="text-xs text-gray-500">One landscape page per employee per Mon–Sun week. All 7 days side-by-side with weekly totals — best for billing & payroll review.</div>
                        </div>
                    </label>
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

{{-- Bulk action bar — appears the moment any row is checked. Brenda asked
     for bulk approve 04.25.2026. Approve/Reject only act on rows whose
     status is currently 'submitted'; rows in other statuses get skipped
     server-side with a count returned in the response.

     2026-04-28 — only Admin + Site Manager can approve/reject (Brenda's
     policy). Other roles still see the table + filters but no bulk bar. --}}
@if (auth()->user()?->canApproveTimesheets())
<div id="bulkActionBar" class="hidden mb-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-center justify-between flex-wrap gap-2">
    <div class="text-sm text-blue-900">
        <strong id="bulkSelectedCount">0</strong> timesheet(s) selected
        <span class="text-xs text-blue-700">— only "Submitted" rows will be acted on; others are skipped</span>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="bulkApproveSelected()" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            ✓ Approve Selected
        </button>
        <button type="button" onclick="bulkRejectSelected()" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            ✗ Reject Selected
        </button>
        <button type="button" onclick="clearBulkSelection()" class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-3 py-2 rounded-lg border border-gray-200">
            Clear
        </button>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table id="dataTable" class="w-full">
        <thead><tr>
            <th width="30"><input type="checkbox" id="bulkSelectAll" onclick="toggleAllBulk(this)" title="Select all on this page"></th>
            <th>Date</th><th>Employee</th><th>Project</th><th>Phase code</th><th>Project #</th><th>Regular</th><th>OT</th><th>DT</th><th>Total</th><th>Cost</th><th>Status</th><th class="text-center" width="100">Actions</th>
        </tr></thead>
    </table>
</div>

{{-- ───── Snap-a-Timesheet (AI OCR) modal ─────
     Brenda 04.29.2026 killer feature. Three views inside the same shell:
       1) "upload"    — drag-drop / file picker, plus a sample image hint
       2) "extracting"— spinner with rotating witty status lines
       3) "review"    — side-by-side: photo on the left, AI-extracted rows
                       on the right (editable selects/inputs). User fixes
                       any wrong matches and clicks "Create All".
     The Alpine component owns all state and posts twice:
       POST /timesheets/scan-photo  → returns extracted entries
       POST /timesheets/scan-commit → creates the timesheets
--}}
<div id="scanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay"
     x-data="snapTimesheet()" x-init="init()"
     onclick="if(event.target===this)closeModal('scanModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl mx-4 max-h-[92vh] overflow-hidden flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-600 via-fuchsia-600 to-pink-600 text-white">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                <div>
                    <h3 class="text-lg font-bold">Snap-a-Timesheet</h3>
                    <p class="text-xs text-purple-100" x-text="stage === 'review' ? summary : 'Photo of a paper timesheet → AI fills in every row'"></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('scanModal')" class="text-purple-100 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- ───── STAGE 1: upload ───── --}}
        <div x-show="stage === 'upload'" class="p-6">
            <label class="block">
                <input type="file" accept="image/*" capture="environment" class="hidden" @change="onFileSelected($event)">
                <div class="border-2 border-dashed border-purple-300 rounded-xl p-12 text-center hover:bg-purple-50 transition cursor-pointer"
                     @dragover.prevent @drop.prevent="onFileDropped($event)">
                    <svg class="w-16 h-16 mx-auto text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <p class="mt-4 text-base font-semibold text-gray-900">Drop a photo here, or click to choose</p>
                    <p class="mt-1 text-xs text-gray-500">Supports JPG, PNG, HEIC up to 10 MB. Snap with your phone, paste from email — anything works.</p>
                </div>
            </label>

            <div class="mt-6 grid grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">📸 What works</div>
                    <ul class="text-gray-600 space-y-0.5">
                        <li>• Daily roster sheets</li>
                        <li>• Foreman's notebook page</li>
                        <li>• Sign-in / sign-out logs</li>
                    </ul>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">🤖 What AI extracts</div>
                    <ul class="text-gray-600 space-y-0.5">
                        <li>• Each employee + hours</li>
                        <li>• Date, project, cost code</li>
                        <li>• ST / OT / PR splits</li>
                    </ul>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="font-bold text-gray-900 mb-1">✅ You stay in control</div>
                    <ul class="text-gray-600 space-y-0.5">
                        <li>• Review every row</li>
                        <li>• Fix anything wrong</li>
                        <li>• One-click to save all</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ───── STAGE 2: extracting ───── --}}
        <div x-show="stage === 'extracting'" class="p-12 text-center">
            <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-500 animate-pulse">
                <svg class="w-10 h-10 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <p class="mt-6 text-lg font-bold text-gray-900" x-text="extractStatus"></p>
            <p class="mt-2 text-sm text-gray-500">This usually takes 3–8 seconds depending on the image.</p>
        </div>

        {{-- ───── STAGE 3: review ───── --}}
        <div x-show="stage === 'review'" class="flex-1 overflow-hidden flex flex-col">
            <div class="grid grid-cols-12 gap-4 p-6 overflow-y-auto" style="max-height: calc(92vh - 180px);">
                {{-- LEFT: original photo --}}
                <div class="col-span-4">
                    <div class="sticky top-0">
                        <p class="text-xs font-semibold text-gray-700 uppercase mb-2">Original photo</p>
                        <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                            <img :src="photoPreview" alt="Uploaded timesheet" class="w-full h-auto">
                        </div>
                        <div class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                            <p class="text-xs font-semibold text-purple-900 mb-1">AI Summary</p>
                            <p class="text-xs text-purple-800" x-text="summary || 'No summary'"></p>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: extracted entries --}}
                <div class="col-span-8">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-700 uppercase">
                            Extracted entries — <span x-text="entries.length"></span> row(s)
                        </p>
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-800 rounded-full">●&nbsp;Matched</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full">●&nbsp;Guessed</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-800 rounded-full">●&nbsp;Pick one</span>
                        </div>
                    </div>

                    {{-- Common fields strip — what AI extracted at the form level --}}
                    <div class="grid grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg mb-3 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-600 uppercase tracking-wide mb-1">Date (applies to all)</label>
                            <input type="date" x-model="commonDate" @change="syncCommonDate()" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 uppercase tracking-wide mb-1">Project (applies to all)</label>
                            <select x-model="commonProjectId" @change="syncCommonProject()" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                <option value="">— pick a project —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 border-b border-gray-200">
                                <tr class="text-[10px] uppercase text-gray-600">
                                    <th class="px-2 py-2 text-center w-8"><input type="checkbox" :checked="allSelected" @change="toggleAll($event)"></th>
                                    <th class="px-2 py-2 text-left">Employee</th>
                                    <th class="px-2 py-2 text-right">ST</th>
                                    <th class="px-2 py-2 text-right">OT</th>
                                    <th class="px-2 py-2 text-right">PR</th>
                                    <th class="px-2 py-2 text-center">Cat.</th>
                                    <th class="px-2 py-2 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(row, idx) in entries" :key="idx">
                                    <tr :class="row.selected ? '' : 'opacity-40'">
                                        <td class="px-2 py-2 text-center"><input type="checkbox" x-model="row.selected"></td>
                                        <td class="px-2 py-1.5">
                                            <select x-model="row.employee_id" :class="employeeRowClass(row)" class="w-full border rounded px-1.5 py-1 text-xs">
                                                <option value="">— pick employee —</option>
                                                @foreach($employees as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->employee_number }} — {{ $emp->last_name }}, {{ $emp->first_name }}</option>
                                                @endforeach
                                            </select>
                                            <p class="text-[10px] text-gray-500 mt-0.5">
                                                <span class="italic">As written:</span> <span x-text="row.employee_name || '—'"></span>
                                                <span x-show="row.confidence !== undefined" class="ml-2">·&nbsp;<span x-text="(Math.round((row.confidence||0)*100)) + '%'"></span> conf.</span>
                                            </p>
                                        </td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.25" min="0" x-model.number="row.regular_hours" class="w-16 border border-gray-300 rounded px-1.5 py-1 text-xs text-right"></td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.25" min="0" x-model.number="row.overtime_hours" class="w-16 border border-gray-300 rounded px-1.5 py-1 text-xs text-right"></td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.25" min="0" x-model.number="row.double_time_hours" class="w-16 border border-gray-300 rounded px-1.5 py-1 text-xs text-right"></td>
                                        <td class="px-2 py-1.5">
                                            <select x-model="row.earnings_category" class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs">
                                                <option value="HE">HE</option>
                                                <option value="HO">HO</option>
                                                <option value="VA">VA</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1.5"><input type="text" x-model="row.notes" placeholder="—" class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs"></td>
                                    </tr>
                                </template>
                                <tr x-show="entries.length === 0">
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-xs">
                                        AI didn't find any rows in this image. Try a clearer photo.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr class="font-bold text-xs">
                                    <td class="px-2 py-2 text-right" colspan="2">Totals (selected only)</td>
                                    <td class="px-2 py-2 text-right" x-text="totals.st.toFixed(2)"></td>
                                    <td class="px-2 py-2 text-right" x-text="totals.ot.toFixed(2)"></td>
                                    <td class="px-2 py-2 text-right" x-text="totals.pr.toFixed(2)"></td>
                                    <td class="px-2 py-2 text-right" colspan="2">
                                        <span x-text="(totals.st + totals.ot + totals.pr).toFixed(2)"></span> hrs
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 bg-gray-50 flex items-center justify-between">
                <div class="text-xs text-gray-600">
                    <span x-text="selectedCount"></span> of <span x-text="entries.length"></span> rows selected ·
                    Unmatched employees must be picked before saving.
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="reset()" class="px-4 py-2 text-sm bg-white border border-gray-300 hover:bg-gray-50 rounded-lg">Start over</button>
                    <button type="button" @click="commit()" :disabled="!canCommit" class="px-5 py-2 text-sm bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!committing">✓ Create <span x-text="selectedCount"></span> Timesheet(s)</span>
                        <span x-show="committing">Creating…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Status banner (errors / success) --}}
        <div x-show="banner.text" x-transition class="px-6 py-3" :class="banner.kind === 'error' ? 'bg-red-50 text-red-800 border-t border-red-200' : 'bg-green-50 text-green-800 border-t border-green-200'">
            <p class="text-sm font-semibold" x-text="banner.text"></p>
        </div>
    </div>
</div>

<!-- Create Modal — mirrors the Edit modal's UX so the add + edit flows feel
     identical (weekly OT split preview, Force OT, per-diem toggle). -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add New Timesheet</h3>
            <button type="button" onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" class="p-6 space-y-4">
            @csrf
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
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phase code</label>
                    <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">— Optional —</option>
                        @foreach($costCodes ?? [] as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Type</label>
                    {{-- Direct Labor / Indirect Labor etc. — feeds cost analyst's
                         labor-cost breakdowns so single-entry timesheets get the
                         same categorization as bulk entries. --}}
                    <select name="cost_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">— Optional —</option>
                        @foreach($costTypes ?? [] as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Work Order # <span class="text-gray-400 font-normal">(shop's internal WO, optional)</span></label>
                <input type="text" name="work_order_number" maxlength="100" placeholder="e.g. WO-12345" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            {{-- Hours Worked shortcut — type a daily total and the server re-splits
                 into Reg/OT using the weekly 40-hr rule. Leave blank if you're
                 entering Reg/OT/DT manually below. --}}
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-900 mb-1">Hours Worked (auto-split)</label>
                        <input type="number" step="0.25" min="0" name="hours_worked" id="create_hours_worked" placeholder="blank = use fields below" class="w-full border border-blue-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-[11px] text-blue-700 mt-1">OT after 40 hrs/week.</p>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2">
                            {{-- Sentinel so unchecked submits 0 --}}
                            <input type="hidden" name="force_overtime" value="0">
                            <input type="checkbox" name="force_overtime" id="create_force_overtime" value="1" class="w-4 h-4 border border-amber-400 rounded focus:ring-2 focus:ring-amber-500">
                            <span class="text-sm font-medium text-amber-900">Force OT</span>
                        </label>
                    </div>
                    <div class="bg-white rounded-lg p-2 text-xs">
                        <div class="flex justify-between"><span class="text-gray-600">Week so far:</span> <span id="create_week_so_far" class="font-semibold">—</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">→ Reg / OT:</span>   <span id="create_split_preview" class="font-semibold">—</span></div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Regular Hrs</label><input type="number" step="0.25" name="regular_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">OT Hrs</label><input type="number" step="0.25" name="overtime_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">DT Hrs</label><input type="number" step="0.25" name="double_time_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
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
            <div class="grid grid-cols-3 gap-4">
                <div class="flex items-center gap-3 pt-5">
                    {{-- Hidden sentinel so an unchecked box still submits 0 --}}
                    <input type="hidden" name="is_billable" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_billable" value="1" checked class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"><span class="text-sm font-medium text-gray-700">Billable</span></label>
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <input type="hidden" name="per_diem" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="per_diem" id="create_per_diem" value="1" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"><span class="text-sm font-medium text-gray-700">Pay per diem</span></label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Per diem $</label>
                    <input type="number" step="0.01" min="0" name="per_diem_amount" id="create_per_diem_amount" placeholder="default" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" onclick="submitForm('createForm','{{ route("timesheets.store") }}','POST',table,'createModal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Timesheet</button>
        </div>
    </div>
</div>

@include('timesheets.partials.timesheet-edit-modal')

@push('scripts')
<script>
var table = $('#dataTable').DataTable({
    ajax: '{{ route("timesheets.index") }}',
    columns: [
        // Bulk-select checkbox column. Re-rendered on every redraw because
        // server-side DataTables wipe the DOM each page change.
        {data:'id', orderable:false, searchable:false, className:'text-center',
         render: function(id, type, row) {
            const status = row.status;
            const dis = (status === 'submitted') ? '' : 'disabled title="Only Submitted timesheets can be bulk-acted on"';
            return '<input type="checkbox" class="ts-bulk-check" value="'+id+'" '+dis+' onchange="refreshBulkSelection()">';
         }
        },
        {data:'date', render: function(d) {
            if (!d) return '—';
            // Server sends Y-m-d string. Parse manually to avoid JS timezone shift
            // (new Date("2026-03-31") is treated as UTC midnight and becomes 3/30 in US zones).
            var s = String(d).substring(0, 10); // strip any T... suffix
            var parts = s.split('-');
            if (parts.length !== 3) return s;
            return parts[1] + '/' + parts[2] + '/' + parts[0];
        }}, {data:'employee_name'}, {data:'project_name'}, {data:'cost_code'},
        // 2026-05-01 (Brenda): Project # column in place of Crew —
        // sortable so the office can group all rows for a given job together.
        {data:'project_number'},
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
                '<a href="'+window.BASE_URL+'/timesheets/'+id+'/print" target="_blank" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-700 hover:bg-gray-100" title="Print"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg></a>'+
                '<button onclick="editTimesheet('+id+', table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-amber-600 hover:bg-amber-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>'+
                '<button onclick="confirmDelete(window.BASE_URL+\'/timesheets/'+id+'\',table)" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></div>';
        }}
    ]
});

// ─── Bulk approve/reject ──────────────────────────────────────────
const bulkSelectedIds = new Set();

function refreshBulkSelection() {
    bulkSelectedIds.clear();
    document.querySelectorAll('.ts-bulk-check:checked').forEach(cb => bulkSelectedIds.add(parseInt(cb.value)));
    document.getElementById('bulkSelectedCount').textContent = bulkSelectedIds.size;
    document.getElementById('bulkActionBar').classList.toggle('hidden', bulkSelectedIds.size === 0);
}

function toggleAllBulk(master) {
    document.querySelectorAll('.ts-bulk-check:not([disabled])').forEach(cb => cb.checked = master.checked);
    refreshBulkSelection();
}

function clearBulkSelection() {
    document.querySelectorAll('.ts-bulk-check:checked').forEach(cb => cb.checked = false);
    document.getElementById('bulkSelectAll').checked = false;
    refreshBulkSelection();
}

function bulkApproveSelected() {
    if (bulkSelectedIds.size === 0) return;
    Swal.fire({
        title: 'Approve ' + bulkSelectedIds.size + ' timesheet(s)?',
        text: 'Only rows still in "Submitted" status will be approved; others are skipped.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Approve all',
    }).then(r => {
        if (!r.isConfirmed) return;
        $.ajax({
            url: '{{ route("timesheets.bulk-approve") }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ ids: Array.from(bulkSelectedIds) }),
            success: function (res) {
                Toast.fire({ icon: 'success', title: res.message });
                clearBulkSelection();
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                Toast.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Bulk approve failed' });
            },
        });
    });
}

function bulkRejectSelected() {
    if (bulkSelectedIds.size === 0) return;
    Swal.fire({
        title: 'Reject ' + bulkSelectedIds.size + ' timesheet(s)?',
        input: 'textarea',
        inputLabel: 'Optional reason (added to notes on each rejected timesheet)',
        inputPlaceholder: 'e.g. Hours don\'t match crew sign-in sheet',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Reject all',
    }).then(r => {
        if (!r.isConfirmed) return;
        $.ajax({
            url: '{{ route("timesheets.bulk-reject") }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ ids: Array.from(bulkSelectedIds), rejection_reason: r.value || null }),
            success: function (res) {
                Toast.fire({ icon: 'success', title: res.message });
                clearBulkSelection();
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                Toast.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Bulk reject failed' });
            },
        });
    });
}

// Reset master checkbox + selection state on every DataTable redraw (page change, filter, etc.)
table.on('draw', () => { document.getElementById('bulkSelectAll').checked = false; });

function openCreateModal(){
    document.getElementById('createForm').reset();
    // Reset the live preview so old values don't linger between opens
    document.getElementById('create_week_so_far').textContent = '—';
    document.getElementById('create_split_preview').textContent = '—';
    openModal('createModal');
}

// Mirror of the edit modal's preview logic. Lets the Add form show the same
// "Week so far" + "Reg / OT split" hints so office data-entry matches Edit UX.
let createWeekSoFar = 0;

function createUpdateSplitPreview() {
    const f = document.getElementById('createForm');
    const hw = parseFloat(f.querySelector('[name="hours_worked"]').value) || 0;
    const forceOT = f.querySelector('#create_force_overtime').checked;
    let reg = 0, ot = 0;
    if (hw > 0) {
        if (forceOT) {
            ot = hw;
        } else {
            const cap = Math.max(0, 40 - createWeekSoFar);
            reg = Math.min(hw, cap);
            ot  = Math.max(0, hw - reg);
        }
        document.getElementById('create_split_preview').textContent = reg.toFixed(2) + ' / ' + ot.toFixed(2);
    } else {
        document.getElementById('create_split_preview').textContent = '(manual)';
    }
}

async function createFetchWeekHours() {
    const f = document.getElementById('createForm');
    const empId = f.querySelector('[name="employee_id"]').value;
    const date  = f.querySelector('[name="date"]').value;
    const sfEl  = document.getElementById('create_week_so_far');
    if (!empId || !date) { sfEl.textContent = '—'; return; }
    try {
        const res = await fetch(`{{ route('timesheets.week-hours') }}?employee_id=${empId}&date=${encodeURIComponent(date)}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        createWeekSoFar = parseFloat(data.week_hours_before || 0);
        sfEl.textContent = createWeekSoFar.toFixed(2) + ' hrs';
        sfEl.className = 'font-semibold ' + (createWeekSoFar >= 40 ? 'text-amber-600' : 'text-gray-900');
        createUpdateSplitPreview();
    } catch (e) { /* ignore */ }
}

document.addEventListener('DOMContentLoaded', function() {
    var f = document.getElementById('createForm');
    if (!f) return;
    f.querySelector('[name="hours_worked"]').addEventListener('input', createUpdateSplitPreview);
    f.querySelector('[name="force_overtime"]')?.addEventListener('change', createUpdateSplitPreview);
    f.querySelector('[name="employee_id"]').addEventListener('change', createFetchWeekHours);
    f.querySelector('[name="date"]').addEventListener('change', createFetchWeekHours);
});

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

// ─── Snap-a-Timesheet (Brenda 04.29.2026) ────────────────────────────
// All UI state for the AI scanner lives in the Alpine component below
// (snapTimesheet()). This wrapper just shows the modal — Alpine takes
// over from there so we can keep reactive state for stage transitions,
// the editable entries grid, and the upload/extract/commit pipeline.
function openScanModal(){
    openModal('scanModal');
    // Reset Alpine state on each open so a stale review doesn't reappear
    const modal = document.getElementById('scanModal');
    if (modal && modal._x_dataStack) {
        Alpine.$data(modal).reset();
    }
}

function snapTimesheet(){
    return {
        // Pipeline stages: upload → extracting → review
        stage: 'upload',
        // Photo + AI response
        photoFile: null,
        photoPreview: null,
        scanLogId: null,
        summary: null,
        commonDate: '',
        commonProjectId: '',
        // Editable rows the user reviews + commits
        entries: [],
        // UX
        extractStatus: 'Reading your timesheet…',
        extractStatuses: [
            'Reading your timesheet…',
            'Identifying employees…',
            'Pulling out hours…',
            'Matching against your roster…',
            'Almost done…',
        ],
        extractTimer: null,
        committing: false,
        banner: { kind: 'success', text: '' },

        init() { /* nothing on first mount; reset() handles it */ },

        reset() {
            if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }
            this.stage = 'upload';
            this.photoFile = null;
            this.photoPreview = null;
            this.scanLogId = null;
            this.summary = null;
            this.commonDate = '';
            this.commonProjectId = '';
            this.entries = [];
            this.banner = { kind: 'success', text: '' };
            this.committing = false;
        },

        onFileSelected(e) {
            const f = e.target.files && e.target.files[0];
            if (f) this.uploadPhoto(f);
        },
        onFileDropped(e) {
            const f = e.dataTransfer.files && e.dataTransfer.files[0];
            if (f) this.uploadPhoto(f);
        },

        async uploadPhoto(file) {
            this.photoFile = file;
            this.photoPreview = URL.createObjectURL(file);
            this.stage = 'extracting';
            this.banner = { kind: 'success', text: '' };

            // Rotate witty status lines so the wait feels alive
            let i = 0;
            this.extractStatus = this.extractStatuses[0];
            this.extractTimer = setInterval(() => {
                i = (i + 1) % this.extractStatuses.length;
                this.extractStatus = this.extractStatuses[i];
            }, 1800);

            const fd = new FormData();
            fd.append('photo', file);
            try {
                const r = await fetch('{{ route("timesheets.scan-photo") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await r.json();
                if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }

                if (!r.ok || !data.success) {
                    this.banner = { kind: 'error', text: data.message || 'AI extraction failed.' };
                    this.stage = 'upload';
                    return;
                }

                // Hydrate the review grid
                this.scanLogId = data.scan_log_id;
                this.summary = data.summary;
                this.commonDate = (data.common && data.common.date) || new Date().toISOString().slice(0, 10);
                this.entries = (data.entries || []).map(e => ({
                    selected: true,
                    employee_id: e.employee_id || '',
                    employee_name: e.employee_name || '',
                    confidence: e.confidence,
                    project_id: e.project_id || '',
                    date: e.date || this.commonDate,
                    regular_hours: Number(e.regular_hours || 0),
                    overtime_hours: Number(e.overtime_hours || 0),
                    double_time_hours: Number(e.double_time_hours || 0),
                    earnings_category: e.earnings_category || 'HE',
                    notes: e.notes || '',
                    cost_code_id: e.cost_code_id || null,
                    cost_type_id: e.cost_type_id || null,
                    shift_id: null,
                    match_status: e.match_status || 'unmatched',
                }));
                // Default the project dropdown to whatever the AI guessed
                // for the form so all rows pick it up via syncCommonProject.
                const firstWithProj = this.entries.find(e => e.project_id);
                if (firstWithProj) this.commonProjectId = String(firstWithProj.project_id);
                else this.commonProjectId = '';
                this.syncCommonProject();
                this.stage = 'review';
            } catch (err) {
                if (this.extractTimer) { clearInterval(this.extractTimer); this.extractTimer = null; }
                this.banner = { kind: 'error', text: 'Network error: ' + (err.message || err) };
                this.stage = 'upload';
            }
        },

        syncCommonDate() {
            this.entries.forEach(e => { if (!e.date) e.date = this.commonDate; });
        },
        syncCommonProject() {
            if (!this.commonProjectId) return;
            this.entries.forEach(e => { if (!e.project_id) e.project_id = this.commonProjectId; });
        },

        toggleAll(e) {
            const v = e.target.checked;
            this.entries.forEach(row => row.selected = v);
        },

        get allSelected() {
            return this.entries.length > 0 && this.entries.every(e => e.selected);
        },
        get selectedCount() {
            return this.entries.filter(e => e.selected).length;
        },
        get totals() {
            return this.entries.reduce((acc, e) => {
                if (!e.selected) return acc;
                acc.st += Number(e.regular_hours) || 0;
                acc.ot += Number(e.overtime_hours) || 0;
                acc.pr += Number(e.double_time_hours) || 0;
                return acc;
            }, { st: 0, ot: 0, pr: 0 });
        },
        get canCommit() {
            if (this.committing) return false;
            const selected = this.entries.filter(e => e.selected);
            if (selected.length === 0) return false;
            // Every selected row must have employee + project + date
            return selected.every(e => e.employee_id && (e.project_id || this.commonProjectId) && (e.date || this.commonDate));
        },

        employeeRowClass(row) {
            if (!row.employee_id) return 'border-red-400 bg-red-50';
            if (row.match_status === 'matched') return 'border-green-400 bg-green-50';
            if (row.match_status === 'guessed') return 'border-amber-400 bg-amber-50';
            return 'border-gray-300';
        },

        async commit() {
            if (!this.canCommit) return;
            this.committing = true;
            this.banner = { kind: 'success', text: '' };

            const payload = {
                scan_log_id: this.scanLogId,
                entries: this.entries.filter(e => e.selected).map(e => ({
                    employee_id: parseInt(e.employee_id, 10),
                    project_id: parseInt(e.project_id || this.commonProjectId, 10),
                    date: e.date || this.commonDate,
                    regular_hours: e.regular_hours,
                    overtime_hours: e.overtime_hours,
                    double_time_hours: e.double_time_hours,
                    earnings_category: e.earnings_category,
                    cost_code_id: e.cost_code_id,
                    cost_type_id: e.cost_type_id,
                    shift_id: e.shift_id,
                    notes: e.notes,
                })),
            };

            try {
                const r = await fetch('{{ route("timesheets.scan-commit") }}', {
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
                    this.committing = false;
                    return;
                }
                this.banner = { kind: 'success', text: data.message };
                // Close after a short victory beat + reload the table
                setTimeout(() => {
                    closeModal('scanModal');
                    this.reset();
                    if (typeof table !== 'undefined' && table.ajax) table.ajax.reload();
                }, 1200);
            } catch (err) {
                this.banner = { kind: 'error', text: 'Network error: ' + (err.message || err) };
                this.committing = false;
            }
        },
    };
}
</script>
@include('timesheets.partials.timesheet-edit-script')
@endpush

@endsection
