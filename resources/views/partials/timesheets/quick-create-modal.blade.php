{{--
    Dashboard "Quick Add Timesheet" modal.

    This is intentionally a SLIM version of the full Timesheet create modal —
    just the essential fields needed for a fast dashboard entry. For advanced
    features (force-OT, per-diem override, work-through-lunch, signature),
    the full modal lives at `/timesheets` index.

    After save we toast + reload the page so the dashboard pending-approvals
    counter ticks up.

    Required props (from host page):
    - $employees    Collection<Employee> — for the employee picker
    - $projects     Collection<Project> — for the project picker
    - $costCodes    Collection<CostCode> — phase code picker
--}}
<div id="quickTimesheetModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('quickTimesheetModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Quick Add Timesheet</h3>
                <p class="text-xs text-gray-500 mt-0.5">For full options (force OT, per diem, signature) use Time &amp; Labor → Timesheets.</p>
            </div>
            <button onclick="closeModal('quickTimesheetModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="quickTimesheetForm" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee *</label>
                    <select name="employee_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">— Pick —</option>
                        @foreach($employees ?? [] as $e)
                            <option value="{{ $e->id }}">{{ $e->employee_number }} — {{ $e->first_name }} {{ $e->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                <select name="project_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">— Pick —</option>
                    @foreach($projects ?? [] as $p)
                        <option value="{{ $p->id }}">{{ $p->project_number }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phase Code</label>
                    <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">— Optional —</option>
                        @foreach($costCodes ?? [] as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hours Worked *</label>
                    <input type="number" step="0.25" min="0" name="hours_worked" required placeholder="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-[11px] text-gray-500 mt-1">Auto-split into Reg/OT (40 hrs/week rule).</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('quickTimesheetModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="submitForm('quickTimesheetForm','{{ route('timesheets.store') }}','POST', null, 'quickTimesheetModal')" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Save Timesheet</button>
        </div>
    </div>
</div>
