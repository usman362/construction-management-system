<!-- Edit Timesheet Modal (shared: index + show) -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Timesheet</h3>
            <button type="button" onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_id" id="edit_id">
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
                    <select name="cost_code_id" id="edit_cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">— Optional —</option>
                        @foreach($costCodes ?? [] as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Type</label>
                    <select name="cost_type_id" id="edit_cost_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">— Optional —</option>
                        @foreach($costTypes ?? [] as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Work Order # <span class="text-gray-400 font-normal">(shop's internal WO, optional)</span></label>
                <input type="text" name="work_order_number" id="edit_work_order_number" maxlength="100" placeholder="e.g. WO-12345" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            {{-- Hours Worked shortcut: typing a total here tells the server to re-split into
                 Reg/OT using the weekly 40-hr rule. Leaving it blank keeps the Reg/OT/DT
                 values you type below as-is (manual override). --}}
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-900 mb-1">Hours Worked (re-split)</label>
                        <input type="number" step="0.25" min="0" name="hours_worked" id="edit_hours_worked" placeholder="blank = keep current" class="w-full border border-blue-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-[11px] text-blue-700 mt-1">OT after 40 hrs/week.</p>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2">
                            {{-- Sentinel so unchecked submits 0 --}}
                            <input type="hidden" name="force_overtime" value="0">
                            <input type="checkbox" name="force_overtime" id="edit_force_overtime" value="1" class="w-4 h-4 border border-amber-400 rounded focus:ring-2 focus:ring-amber-500">
                            <span class="text-sm font-medium text-amber-900">Force OT</span>
                        </label>
                    </div>
                    <div class="bg-white rounded-lg p-2 text-xs">
                        <div class="flex justify-between"><span class="text-gray-600">Week so far:</span> <span id="edit_week_so_far" class="font-semibold">—</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">→ Reg / OT:</span>   <span id="edit_split_preview" class="font-semibold">—</span></div>
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
                    {{-- Hidden sentinel so an unchecked box still submits 0 (Laravel pattern) --}}
                    <input type="hidden" name="is_billable" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_billable" value="1" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"><span class="text-sm font-medium text-gray-700">Billable</span></label>
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <input type="hidden" name="per_diem" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="per_diem" id="edit_per_diem" value="1" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"><span class="text-sm font-medium text-gray-700">Pay per diem</span></label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Per diem $</label>
                    <input type="number" step="0.01" min="0" name="per_diem_amount" id="edit_per_diem_amount" placeholder="default" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update Timesheet</button>
        </div>
    </div>
</div>
