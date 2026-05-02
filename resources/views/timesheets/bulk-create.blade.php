@extends('layouts.app')

@section('title', 'Bulk Timesheet Entry')

{{--
    2026-04-28: Brenda asked for the bulk entry page to look like her legacy
    Foundation Software / ComputerEase payroll batch-entry screen — dense
    keyboard-driven form, "Save Record" running list at the bottom, classic
    Win95 yellow-input aesthetic so her data-entry clerks feel at home.

    Confirmed scope from Brenda (04.28.2026):
      - Same data fields as the existing single + bulk timesheet (no new fields)
      - Use Employee # (not SSN) as the keyed identifier
      - Earnings Category: HE = Hourly Earnings, HO = Holiday, VA = Vacation
      - No subjob field needed at this point

    Implementation choice: each "Save Record" click hits the existing single
    `timesheets.store` endpoint with `force_overtime: true` so the manually
    keyed ST/OT/PR are preserved verbatim (no weekly 40-hr re-split). All
    observers, cost calc, and audit logging stay intact — no new bulkStore
    endpoint needed for this flow.
--}}

@push('styles')
<style>
    .legacy-frame { background: #ece9d8; border: 2px solid #4b5563; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; box-shadow: 2px 2px 0 #000; }
    .legacy-titlebar { background: linear-gradient(to right, #1e3a8a, #1e40af 50%, #3b82f6); color: #fff; padding: 6px 10px; font-weight: 600; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
    .legacy-titlebar .ctrls { display: flex; gap: 4px; }
    .legacy-titlebar .ctrls span { display: inline-block; width: 18px; height: 16px; background: #d1d5db; border: 1px solid #6b7280; text-align: center; line-height: 14px; font-size: 11px; color: #1f2937; }
    .legacy-headerstrip { background: #1e3a8a; color: #fff; padding: 6px 10px; display: flex; gap: 16px; flex-wrap: wrap; align-items: center; font-size: 12px; }
    .legacy-headerstrip label { color: #cbd5e1; margin-right: 4px; font-weight: 600; }
    .legacy-headerstrip input[type=date], .legacy-headerstrip input[type=text] { background: #fffbeb; border: 1px inset #94a3b8; padding: 2px 4px; font-size: 12px; }
    .legacy-body { padding: 10px; display: grid; grid-template-columns: 1fr 220px; gap: 10px; }
    .legacy-fieldgrid { display: grid; grid-template-columns: 140px 1fr 140px 1fr; gap: 6px 10px; align-items: center; background: #ece9d8; padding: 8px; border: 1px solid #b8b3a0; }
    .legacy-fieldgrid label { font-size: 12px; font-weight: 600; color: #1f2937; text-align: right; }
    .legacy-fieldgrid input, .legacy-fieldgrid select { background: #fffbeb; border: 1px inset #94a3b8; padding: 3px 5px; font-size: 12px; width: 100%; font-family: 'Consolas', 'Courier New', monospace; }
    .legacy-fieldgrid input:focus, .legacy-fieldgrid select:focus { outline: 2px solid #2563eb; background: #fef9c3; }
    .legacy-rail { display: flex; flex-direction: column; gap: 6px; }
    .legacy-btn { background: linear-gradient(to bottom, #f3f4f6, #d1d5db); border: 1px solid #6b7280; padding: 6px 10px; font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: 1px 1px 0 #000; text-align: center; color: #1f2937; }
    .legacy-btn:hover:not(:disabled) { background: linear-gradient(to bottom, #fef9c3, #fde047); }
    .legacy-btn:active:not(:disabled) { box-shadow: inset 1px 1px 0 #000; }
    .legacy-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .legacy-btn-primary { background: linear-gradient(to bottom, #3b82f6, #1d4ed8); color: #fff; border-color: #1e3a8a; }
    .legacy-btn-primary:hover:not(:disabled) { background: linear-gradient(to bottom, #60a5fa, #2563eb); }
    .legacy-btn-danger { background: linear-gradient(to bottom, #fca5a5, #dc2626); color: #fff; border-color: #991b1b; }
    .legacy-list-wrap { padding: 0 10px 10px; }
    .legacy-list { width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Consolas', 'Courier New', monospace; background: #fffbeb; border: 1px solid #6b7280; }
    .legacy-list th { background: #1e3a8a; color: #fff; padding: 4px 6px; text-align: left; font-weight: 600; border-right: 1px solid #1e40af; font-size: 11px; }
    .legacy-list td { padding: 3px 6px; border-bottom: 1px dotted #94a3b8; border-right: 1px dotted #cbd5e1; }
    .legacy-list tr:hover td { background: #fef9c3; }
    .legacy-list tfoot td { background: #ece9d8; font-weight: 700; border-top: 2px solid #1f2937; }
    .legacy-status-bar { background: #1e3a8a; color: #fff; padding: 4px 10px; font-size: 11px; font-family: 'Consolas', monospace; display: flex; justify-content: space-between; }
    .legacy-banner { padding: 6px 10px; font-size: 12px; font-weight: 600; }
    .legacy-banner-success { background: #bbf7d0; color: #064e3b; border-left: 4px solid #16a34a; }
    .legacy-banner-error { background: #fecaca; color: #7f1d1d; border-left: 4px solid #dc2626; }
    .legacy-fieldgrid .field-disabled { background: #d1d5db !important; color: #6b7280; }
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; }
    .modal-card { background: #ece9d8; border: 2px solid #4b5563; box-shadow: 4px 4px 0 #000; min-width: 500px; max-width: 600px; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6" x-data="legacyTimesheet()">

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Timesheets</a>
        <div class="text-xs text-gray-500">Foundation-style batch entry · Save Record after each line</div>
    </div>

    <div class="legacy-frame">
        <div class="legacy-titlebar">
            <div>BAK Construction · Payroll Batch Entry — Timesheet Records</div>
            <div class="ctrls"><span>_</span><span>□</span><span>×</span></div>
        </div>

        <div class="legacy-headerstrip">
            <div>
                <label>Time period beginning:</label>
                <input type="date" x-model="header.period_begin">
            </div>
            <div>
                <label>Time period ending:</label>
                <input type="date" x-model="header.period_end">
            </div>
            <div>
                <label>Payroll for W/E Date:</label>
                <input type="date" x-model="header.we_date">
            </div>
            <div class="ml-auto text-xs">
                Records this batch: <strong x-text="savedRecords.length"></strong>
            </div>
        </div>

        {{-- Status / error banner --}}
        <div x-show="banner.text" x-transition class="legacy-banner" :class="banner.kind === 'error' ? 'legacy-banner-error' : 'legacy-banner-success'">
            <span x-text="banner.text"></span>
        </div>

        <div class="legacy-body">
            {{-- LEFT: data entry fields --}}
            <div class="legacy-fieldgrid">
                <label>Job No.:</label>
                <select x-model="entry.project_id">
                    <option value="">— Select Job —</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_number ?? '—' }} — {{ $project->name }}</option>
                    @endforeach
                </select>

                <label>Work Date:</label>
                <input type="date" x-model="entry.date">

                {{-- 2026-04-29 (Brenda): Employee # and Name split into two
                     side-by-side fields. Type the Employee # and Name +
                     Craft auto-fill from the catalog (matches Foundation's
                     keyed-by-number flow). Or pick from the Name dropdown —
                     ID auto-fills back. Both fields stay in sync. --}}
                <label>Employee #:</label>
                <input type="text" x-model="entry.employee_number" @input="onEmployeeNumberInput()" @blur="onEmployeeNumberInput()" placeholder="Type # or pick name →" list="employeeNumberList" autocomplete="off">
                <datalist id="employeeNumberList">
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->employee_number }}">{{ $emp->last_name }}, {{ $emp->first_name }}</option>
                    @endforeach
                </datalist>

                <label>Name:</label>
                <select x-model="entry.employee_id" @change="onEmployeeChange()">
                    <option value="">— Select Employee —</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}"
                                data-craft-id="{{ $emp->craft_id }}"
                                data-employee-number="{{ $emp->employee_number }}"
                                data-name="{{ $emp->first_name }} {{ $emp->last_name }}">
                            {{ $emp->last_name }}, {{ $emp->first_name }}
                        </option>
                    @endforeach
                </select>

                <label>Shift:</label>
                <select x-model="entry.shift_id">
                    <option value="">— Select Shift —</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>

                <label>Cost Code:</label>
                <select x-model="entry.cost_code_id">
                    <option value="">— None —</option>
                    @foreach ($costCodes as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                    @endforeach
                </select>

                <label>Cost Type:</label>
                <select x-model="entry.cost_type_id">
                    <option value="">— None —</option>
                    @foreach ($costTypes as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                    @endforeach
                </select>

                <label>Craft:</label>
                <select x-model="entry.craft_id">
                    <option value="">— None —</option>
                    @foreach ($crafts as $cr)
                        <option value="{{ $cr->id }}">{{ $cr->code }} — {{ $cr->name }}</option>
                    @endforeach
                </select>

                <label>Work Order #:</label>
                <input type="text" x-model="entry.work_order_number" maxlength="100" placeholder="optional">

                {{-- Earnings Category drives whether OT/PR are enabled.
                     HE = Hourly Earnings → all three buckets allowed
                     HO = Holiday        → flat ST hours only, OT/PR locked to 0
                     VA = Vacation       → flat ST hours only, OT/PR locked to 0 --}}
                <label>Earnings Cat.:</label>
                <select x-model="entry.earnings_category" @change="onEarningsChange()">
                    <option value="HE">HE — Hourly Earnings</option>
                    <option value="HO">HO — Holiday</option>
                    <option value="VA">VA — Vacation</option>
                </select>

                <label>ST Hours:</label>
                <input type="number" step="0.25" min="0" x-model="entry.regular_hours" @keydown.enter.prevent="saveRecord()" @input="recalcTotal()">

                <label>OT Hours:</label>
                <input type="number" step="0.25" min="0" x-model="entry.overtime_hours" :disabled="lockOTPR" :class="lockOTPR ? 'field-disabled' : ''" @keydown.enter.prevent="saveRecord()" @input="recalcTotal()">

                <label>PR Hours:</label>
                <input type="number" step="0.25" min="0" x-model="entry.double_time_hours" :disabled="lockOTPR" :class="lockOTPR ? 'field-disabled' : ''" @keydown.enter.prevent="saveRecord()" @input="recalcTotal()">

                <label>Total Hours:</label>
                <input type="text" :value="totalHours.toFixed(2)" readonly class="field-disabled">

                {{-- 2026-05-01 (Brenda): Per Diem and Gate Log are
                     per-line data that only applies to certain jobs.
                     Slotted right after the hours so the keyboard flow
                     stays linear (ST → OT → PR → Total → Per Diem → Gate
                     Log → Notes). Both default empty / 0 — the clerk
                     just leaves them blank when the job doesn't carry. --}}
                <label>Per Diem $:</label>
                <input type="number" step="0.01" min="0" x-model="entry.per_diem_amount"
                       placeholder="0.00 (leave blank if N/A)"
                       @keydown.enter.prevent="saveRecord()">

                <label>Gate Log Hrs:</label>
                <input type="number" step="0.25" min="0" x-model="entry.gate_log_hours"
                       placeholder="leave blank if N/A"
                       @keydown.enter.prevent="saveRecord()">

                <label>Notes:</label>
                <input type="text" x-model="entry.notes" maxlength="500" placeholder="optional" style="grid-column: span 3;">
            </div>

            {{-- RIGHT: action rail --}}
            <div class="legacy-rail">
                <button type="button" class="legacy-btn" @click="showAddEmployee = true">Add Employee</button>
                <button type="button" class="legacy-btn" @click="editSelectedEmployee()" :disabled="!entry.employee_id">Edit Employee</button>
                <hr class="my-2 border-gray-400">
                <button type="button" class="legacy-btn legacy-btn-primary" @click="saveRecord()" :disabled="saving">
                    <span x-show="!saving">Save Record (F10)</span>
                    <span x-show="saving">Saving…</span>
                </button>
                <button type="button" class="legacy-btn" @click="clearRow()">Clear Row</button>
                <hr class="my-2 border-gray-400">
                <button type="button" class="legacy-btn legacy-btn-danger" @click="exitForm()">Exit</button>
            </div>
        </div>

        {{-- Running list of saved records this session --}}
        <div class="legacy-list-wrap">
            <div class="text-xs font-semibold text-gray-700 mb-1">Records in this batch</div>
            <table class="legacy-list">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Job</th>
                        <th>Emp #</th>
                        <th>Name</th>
                        <th>Cost Code</th>
                        <th>Craft</th>
                        <th>Cat</th>
                        <th class="text-right">ST</th>
                        <th class="text-right">OT</th>
                        <th class="text-right">PR</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Per Diem</th>
                        <th class="text-right">Gate Log</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="savedRecords.length === 0">
                        <tr><td colspan="14" class="text-center text-gray-500 py-3">No records keyed yet — fill out the form above and click Save Record.</td></tr>
                    </template>
                    <template x-for="rec in savedRecords" :key="rec.id">
                        <tr>
                            <td x-text="rec.date"></td>
                            <td x-text="rec.project_number"></td>
                            <td x-text="rec.employee_number"></td>
                            <td x-text="rec.employee_name"></td>
                            <td x-text="rec.cost_code"></td>
                            <td x-text="rec.craft"></td>
                            <td x-text="rec.earnings_category"></td>
                            <td class="text-right" x-text="Number(rec.regular_hours).toFixed(2)"></td>
                            <td class="text-right" x-text="Number(rec.overtime_hours).toFixed(2)"></td>
                            <td class="text-right" x-text="Number(rec.double_time_hours).toFixed(2)"></td>
                            <td class="text-right font-semibold" x-text="Number(rec.total_hours).toFixed(2)"></td>
                            <td class="text-right" x-text="rec.per_diem_amount > 0 ? '$' + Number(rec.per_diem_amount).toFixed(2) : '—'"></td>
                            <td class="text-right" x-text="rec.gate_log_hours > 0 ? Number(rec.gate_log_hours).toFixed(2) : '—'"></td>
                            <td>
                                <button type="button" class="text-red-600 hover:text-red-900 text-xs underline" @click="deleteRecord(rec)">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot x-show="savedRecords.length > 0">
                    <tr>
                        <td colspan="7" class="text-right">Batch totals:</td>
                        <td class="text-right" x-text="totals.st.toFixed(2)"></td>
                        <td class="text-right" x-text="totals.ot.toFixed(2)"></td>
                        <td class="text-right" x-text="totals.pr.toFixed(2)"></td>
                        <td class="text-right" x-text="totals.total.toFixed(2)"></td>
                        <td class="text-right" x-text="totals.per_diem > 0 ? '$' + totals.per_diem.toFixed(2) : '—'"></td>
                        <td class="text-right" x-text="totals.gate_log > 0 ? totals.gate_log.toFixed(2) : '—'"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="legacy-status-bar">
            <span>Tip: Press <strong>Enter</strong> in any hours field to Save Record. Job No., Work Date, Shift, and Earnings Cat. carry over to the next entry.</span>
            <span x-text="`Logged in: {{ auth()->user()->name ?? '—' }}`"></span>
        </div>
    </div>

    {{-- Inline Add Employee modal --}}
    <div x-show="showAddEmployee" x-cloak class="modal-backdrop" @keydown.escape.window="showAddEmployee = false">
        <div class="modal-card">
            <div class="legacy-titlebar">
                <div>Add Employee — Quick Entry</div>
                <div class="ctrls"><span @click="showAddEmployee = false" style="cursor:pointer">×</span></div>
            </div>
            <div class="p-4">
                <div class="legacy-fieldgrid" style="grid-template-columns: 130px 1fr;">
                    <label>Employee #:</label>
                    <input type="text" x-model="newEmp.employee_number" placeholder="auto-generates if blank">

                    <label>First Name:</label>
                    <input type="text" x-model="newEmp.first_name">

                    <label>Last Name:</label>
                    <input type="text" x-model="newEmp.last_name">

                    <label>Craft:</label>
                    <select x-model="newEmp.craft_id">
                        <option value="">— None —</option>
                        @foreach ($crafts as $cr)
                            <option value="{{ $cr->id }}">{{ $cr->code }} — {{ $cr->name }}</option>
                        @endforeach
                    </select>

                    <label>Hourly Rate:</label>
                    <input type="number" step="0.01" min="0" x-model="newEmp.hourly_rate" placeholder="optional">
                </div>

                <div x-show="newEmpError" class="legacy-banner legacy-banner-error mt-3" x-text="newEmpError"></div>

                <div class="flex gap-2 justify-end mt-4">
                    <button type="button" class="legacy-btn" @click="showAddEmployee = false">Cancel</button>
                    <button type="button" class="legacy-btn legacy-btn-primary" @click="saveNewEmployee()" :disabled="newEmpSaving">
                        <span x-show="!newEmpSaving">Save & Use</span>
                        <span x-show="newEmpSaving">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function legacyTimesheet() {
        return {
            header: { period_begin: '', period_end: '', we_date: '' },
            // BUG FIX 2026-04-29: `this.blankEntry()` here threw because
            // `this` is undefined during object-literal construction —
            // crashing the whole Alpine component and silently breaking
            // the Save Record button (Brenda's "page is not saving" bug).
            // Inline the literal here; init() reseats it on mount.
            entry: {
                project_id: '', date: '', employee_id: '', employee_number: '', shift_id: '',
                cost_code_id: '', cost_type_id: '', craft_id: '',
                work_order_number: '', earnings_category: 'HE',
                regular_hours: '', overtime_hours: '', double_time_hours: '',
                // 2026-05-01 (Brenda): per-line per-diem + gate-log fields
                per_diem_amount: '', gate_log_hours: '',
                notes: '',
            },
            savedRecords: [],
            saving: false,
            banner: { text: '', kind: 'success' },
            showAddEmployee: false,
            newEmp: { employee_number: '', first_name: '', last_name: '', craft_id: '', hourly_rate: '' },
            newEmpSaving: false,
            newEmpError: '',
            // Default shift id pulled from the server config (Day Shift) so
            // Brenda doesn't have to pick it on every line. Falls back to
            // empty string if no Day-shift record exists in the DB.
            defaultShiftId: @json($defaultShiftId ?? ''),

            init() {
                // Default the W/E and period to the current Mon–Sun week.
                const t = new Date();
                const dow = (t.getDay() + 6) % 7; // 0 = Mon
                const mon = new Date(t); mon.setDate(t.getDate() - dow);
                const sun = new Date(mon); sun.setDate(mon.getDate() + 6);
                this.header.period_begin = this.fmt(mon);
                this.header.period_end = this.fmt(sun);
                this.header.we_date = this.fmt(sun);
                this.entry.date = this.fmt(t);
                // 2026-04-29 (Brenda): default Shift = Day Shift unless
                // changed. Pre-fills on first load and on every "save and
                // continue" reset.
                if (this.defaultShiftId) {
                    this.entry.shift_id = String(this.defaultShiftId);
                }

                // F10 keyboard shortcut to Save Record (Foundation-style).
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'F10') { e.preventDefault(); this.saveRecord(); }
                });
            },

            blankEntry() {
                return {
                    project_id: '', date: '', employee_id: '', employee_number: '', shift_id: '',
                    cost_code_id: '', cost_type_id: '', craft_id: '',
                    work_order_number: '', earnings_category: 'HE',
                    regular_hours: '', overtime_hours: '', double_time_hours: '',
                    per_diem_amount: '', gate_log_hours: '',
                    notes: '',
                };
            },

            get lockOTPR() { return this.entry.earnings_category !== 'HE'; },

            get totalHours() {
                const r = parseFloat(this.entry.regular_hours) || 0;
                const o = this.lockOTPR ? 0 : (parseFloat(this.entry.overtime_hours) || 0);
                const d = this.lockOTPR ? 0 : (parseFloat(this.entry.double_time_hours) || 0);
                return r + o + d;
            },

            get totals() {
                let st = 0, ot = 0, pr = 0, per_diem = 0, gate_log = 0;
                this.savedRecords.forEach(r => {
                    st += parseFloat(r.regular_hours) || 0;
                    ot += parseFloat(r.overtime_hours) || 0;
                    pr += parseFloat(r.double_time_hours) || 0;
                    per_diem += parseFloat(r.per_diem_amount) || 0;
                    gate_log += parseFloat(r.gate_log_hours)  || 0;
                });
                return { st, ot, pr, total: st + ot + pr, per_diem, gate_log };
            },

            recalcTotal() { /* getter recomputes via Alpine; no-op kept for @input clarity */ },

            // Triggered by the Name <select>. Pulls Employee # + craft from
            // the picked option's data attributes and pushes them into the
            // form so the two side-by-side fields stay in sync.
            onEmployeeChange() {
                if (!this.entry.employee_id) {
                    this.entry.employee_number = '';
                    return;
                }
                const sel = document.querySelector('select[x-model="entry.employee_id"]');
                const opt = sel ? sel.querySelector(`option[value="${this.entry.employee_id}"]`) : null;
                if (opt) {
                    const craftId = opt.dataset.craftId;
                    const empNum  = opt.dataset.employeeNumber;
                    // Always overwrite craft on employee change — Brenda
                    // confirmed each worker's craft should auto-populate
                    // (2026-04-29).
                    if (craftId) this.entry.craft_id = String(craftId);
                    if (empNum)  this.entry.employee_number = empNum;
                }
            },

            // Triggered when the user types in the Employee # input. Looks
            // up the matching option in the Name select and selects it,
            // which fires onEmployeeChange() and fills craft.
            onEmployeeNumberInput() {
                const num = (this.entry.employee_number || '').trim();
                if (!num) {
                    this.entry.employee_id = '';
                    return;
                }
                const sel = document.querySelector('select[x-model="entry.employee_id"]');
                if (!sel) return;
                const match = sel.querySelector(`option[data-employee-number="${num}"]`);
                if (match && match.value !== this.entry.employee_id) {
                    this.entry.employee_id = match.value;
                    // Pull craft from the option directly since x-model
                    // change won't re-fire onEmployeeChange synchronously.
                    if (match.dataset.craftId) this.entry.craft_id = String(match.dataset.craftId);
                }
            },

            onEarningsChange() {
                if (this.lockOTPR) {
                    this.entry.overtime_hours = '';
                    this.entry.double_time_hours = '';
                }
            },

            fmt(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const da = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${da}`;
            },

            flash(kind, text, ms = 2500) {
                this.banner = { kind, text };
                setTimeout(() => { if (this.banner.text === text) this.banner = { text: '', kind: 'success' }; }, ms);
            },

            validate() {
                if (!this.entry.project_id) return 'Job No. is required.';
                if (!this.entry.date) return 'Work Date is required.';
                if (!this.entry.employee_id) return 'Employee # is required.';
                if (!this.entry.shift_id) return 'Shift is required.';
                if (this.totalHours <= 0) return 'Enter at least one hour (ST, OT, or PR).';
                return null;
            },

            async saveRecord() {
                const err = this.validate();
                if (err) { this.flash('error', err, 4000); return; }
                if (this.saving) return;
                this.saving = true;

                const payload = {
                    employee_id: this.entry.employee_id,
                    project_id: this.entry.project_id,
                    cost_code_id: this.entry.cost_code_id || null,
                    cost_type_id: this.entry.cost_type_id || null,
                    crew_id: null,
                    date: this.entry.date,
                    shift_id: this.entry.shift_id,
                    work_order_number: this.entry.work_order_number || null,
                    regular_hours: parseFloat(this.entry.regular_hours) || 0,
                    overtime_hours: this.lockOTPR ? 0 : (parseFloat(this.entry.overtime_hours) || 0),
                    double_time_hours: this.lockOTPR ? 0 : (parseFloat(this.entry.double_time_hours) || 0),
                    // 2026-04-30 (Brenda): force_overtime was hardcoded TRUE
                    // and was rolling every ST hour into OT (TimesheetController
                    // line 1175 — `if ($forceOvertime && $reg > 0) { $ot += $reg; $reg = 0; }`).
                    // Always send FALSE — the clerk's manually-typed ST/OT/PR
                    // buckets are still preserved verbatim because we send
                    // explicit regular/overtime/double_time fields and never
                    // send `hours_worked`, so the splitWeekly() path is never
                    // triggered. The server takes our buckets as-is.
                    force_overtime: false,
                    earnings_category: this.entry.earnings_category,
                    // 2026-05-01 (Brenda): per-line per-diem and gate-log
                    // forwarded so they save on the underlying Timesheet.
                    // Sent as null when blank — server validation accepts that.
                    per_diem_amount: this.entry.per_diem_amount !== '' ? parseFloat(this.entry.per_diem_amount) : null,
                    per_diem:        (this.entry.per_diem_amount !== '' && parseFloat(this.entry.per_diem_amount) > 0),
                    gate_log_hours:  this.entry.gate_log_hours !== '' ? parseFloat(this.entry.gate_log_hours) : null,
                    notes: this.entry.notes || null,
                    status: 'submitted',
                };

                try {
                    const res = await fetch(@json(route('timesheets.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!res.ok) {
                        let msg = 'Save failed (HTTP ' + res.status + ').';
                        try {
                            const j = await res.json();
                            if (j.message) msg = j.message;
                            if (j.errors) msg += ' ' + Object.values(j.errors).flat().join(' ');
                        } catch (_) { /* ignore body parse */ }
                        this.flash('error', msg, 5000);
                        return;
                    }

                    const data = await res.json();
                    const ts = data.timesheet || data;

                    // Pull display labels from the in-page selects so we don't
                    // need a server round-trip.
                    const projOpt = document.querySelector(`select[x-model="entry.project_id"] option[value="${this.entry.project_id}"]`);
                    const empOpt = document.querySelector(`select[x-model="entry.employee_id"] option[value="${this.entry.employee_id}"]`);
                    const ccOpt = this.entry.cost_code_id ? document.querySelector(`select[x-model="entry.cost_code_id"] option[value="${this.entry.cost_code_id}"]`) : null;
                    const crOpt = this.entry.craft_id ? document.querySelector(`select[x-model="entry.craft_id"] option[value="${this.entry.craft_id}"]`) : null;

                    this.savedRecords.push({
                        id: ts.id,
                        date: this.entry.date,
                        project_number: projOpt ? projOpt.textContent.split('—')[0].trim() : '',
                        employee_number: empOpt ? empOpt.textContent.split('—')[0].trim() : '',
                        employee_name: empOpt ? (empOpt.dataset.name || '') : '',
                        cost_code: ccOpt ? ccOpt.textContent.split('—')[0].trim() : '',
                        craft: crOpt ? crOpt.textContent.split('—')[0].trim() : '',
                        earnings_category: this.entry.earnings_category,
                        regular_hours: payload.regular_hours,
                        overtime_hours: payload.overtime_hours,
                        double_time_hours: payload.double_time_hours,
                        total_hours: this.totalHours,
                        per_diem_amount: payload.per_diem_amount || 0,
                        gate_log_hours:  payload.gate_log_hours  || 0,
                    });

                    this.flash('success', `Saved record #${ts.id} for ${empOpt ? empOpt.textContent.split('—')[0].trim() : ''}.`);

                    // Carry-over fields per Brenda's flow (Job, Date, Shift,
                    // Earnings Cat. stay; per-employee fields including #
                    // and Craft clear so the next worker can be keyed in).
                    const keep = {
                        project_id: this.entry.project_id,
                        date: this.entry.date,
                        shift_id: this.entry.shift_id || this.defaultShiftId || '',
                        earnings_category: this.entry.earnings_category,
                        cost_code_id: this.entry.cost_code_id,
                        cost_type_id: this.entry.cost_type_id,
                    };
                    this.entry = { ...this.blankEntry(), ...keep };
                } catch (e) {
                    this.flash('error', 'Network error: ' + (e.message || e), 5000);
                } finally {
                    this.saving = false;
                }
            },

            clearRow() {
                this.entry = { ...this.blankEntry(), date: this.entry.date };
            },

            async deleteRecord(rec) {
                if (!confirm(`Delete record #${rec.id} for ${rec.employee_name}?`)) return;
                try {
                    const res = await fetch(`/timesheets/${rec.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!res.ok) { this.flash('error', 'Delete failed.', 4000); return; }
                    this.savedRecords = this.savedRecords.filter(r => r.id !== rec.id);
                    this.flash('success', `Deleted record #${rec.id}.`);
                } catch (e) {
                    this.flash('error', 'Network error: ' + (e.message || e), 5000);
                }
            },

            editSelectedEmployee() {
                if (!this.entry.employee_id) return;
                window.open(`/employees/${this.entry.employee_id}/edit`, '_blank');
            },

            async saveNewEmployee() {
                this.newEmpError = '';
                if (!this.newEmp.first_name || !this.newEmp.last_name) {
                    this.newEmpError = 'First and last name are required.';
                    return;
                }
                this.newEmpSaving = true;
                try {
                    const fd = new FormData();
                    if (this.newEmp.employee_number) fd.append('employee_number', this.newEmp.employee_number);
                    fd.append('first_name', this.newEmp.first_name);
                    fd.append('last_name', this.newEmp.last_name);
                    if (this.newEmp.craft_id) fd.append('craft_id', this.newEmp.craft_id);
                    if (this.newEmp.hourly_rate) fd.append('hourly_rate', this.newEmp.hourly_rate);
                    fd.append('status', 'active');

                    const res = await fetch(@json(route('employees.store')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });

                    if (!res.ok) {
                        try {
                            const j = await res.json();
                            this.newEmpError = j.message || 'Save failed.';
                            if (j.errors) this.newEmpError += ' ' + Object.values(j.errors).flat().join(' ');
                        } catch { this.newEmpError = 'Save failed (HTTP ' + res.status + ').'; }
                        return;
                    }

                    const data = await res.json();
                    const emp = data.employee || data;

                    // Inject new option into the picker and select it.
                    const sel = document.querySelector('select[x-model="entry.employee_id"]');
                    if (sel && emp.id) {
                        const opt = document.createElement('option');
                        opt.value = emp.id;
                        opt.dataset.craftId = emp.craft_id || '';
                        opt.dataset.name = `${emp.first_name} ${emp.last_name}`;
                        opt.textContent = `${emp.employee_number} — ${emp.last_name}, ${emp.first_name}`;
                        sel.appendChild(opt);
                        this.entry.employee_id = String(emp.id);
                        if (emp.craft_id && !this.entry.craft_id) this.entry.craft_id = String(emp.craft_id);
                    }

                    this.showAddEmployee = false;
                    this.newEmp = { employee_number: '', first_name: '', last_name: '', craft_id: '', hourly_rate: '' };
                    this.flash('success', `Added employee ${emp.employee_number} — ${emp.first_name} ${emp.last_name}.`);
                } catch (e) {
                    this.newEmpError = 'Network error: ' + (e.message || e);
                } finally {
                    this.newEmpSaving = false;
                }
            },

            exitForm() {
                if (this.savedRecords.length > 0) {
                    if (!confirm(`You've keyed ${this.savedRecords.length} record(s) this session. They are already saved to the database. Exit anyway?`)) return;
                }
                window.location.href = @json(route('timesheets.index'));
            },
        };
    }
</script>
@endsection
