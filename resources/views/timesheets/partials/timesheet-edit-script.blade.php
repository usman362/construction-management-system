<script>
const WEEK_HOURS_URL = @json(route('timesheets.week-hours'));
const WEEKLY_OT_THRESHOLD = 40;

// Cached week-so-far for the employee+date currently loaded in the modal,
// minus the timesheet being edited. Lets the preview re-render without
// another round-trip when the user toggles Force OT or types a new total.
let editWeekSoFar = 0;
let editingTimesheetId = null;

function editUpdateSplitPreview() {
    const f = document.getElementById('editForm');
    const hw = parseFloat(f.querySelector('[name="hours_worked"]').value) || 0;
    const forceOT = f.querySelector('#edit_force_overtime').checked;
    let reg = 0, ot = 0;
    if (hw > 0) {
        if (forceOT) {
            ot = hw;
        } else {
            const cap = Math.max(0, WEEKLY_OT_THRESHOLD - editWeekSoFar);
            reg = Math.min(hw, cap);
            ot  = Math.max(0, hw - reg);
        }
        document.getElementById('edit_split_preview').textContent = reg.toFixed(2) + ' / ' + ot.toFixed(2);
    } else {
        document.getElementById('edit_split_preview').textContent = '(manual)';
    }
}

async function editFetchWeekHours() {
    const f = document.getElementById('editForm');
    const empId = f.querySelector('[name="employee_id"]').value;
    const date  = f.querySelector('[name="date"]').value;
    const sfEl  = document.getElementById('edit_week_so_far');
    if (!empId || !date) { sfEl.textContent = '—'; return; }
    try {
        const res = await fetch(`${WEEK_HOURS_URL}?employee_id=${empId}&date=${encodeURIComponent(date)}&exclude_id=${editingTimesheetId || ''}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        editWeekSoFar = parseFloat(data.week_hours_before || 0);
        sfEl.textContent = editWeekSoFar.toFixed(2) + ' hrs';
        sfEl.className = 'font-semibold ' + (editWeekSoFar >= 40 ? 'text-amber-600' : 'text-gray-900');
        editUpdateSplitPreview();
    } catch (e) { /* ignore */ }
}

function editTimesheet(id, dt) {
    $.get(window.BASE_URL+'/timesheets/' + id + '/edit', function(d) {
        var f = document.getElementById('editForm');
        function setSelect(name, val) {
            var el = f.querySelector('[name="' + name + '"]');
            if (el) el.value = val != null && val !== '' ? String(val) : '';
        }
        f.querySelector('#edit_id').value = d.id;
        editingTimesheetId = d.id;
        f.querySelector('[name="date"]').value = d.date ? String(d.date).split('T')[0].split(' ')[0] : '';
        setSelect('employee_id', d.employee_id);
        setSelect('project_id', d.project_id);
        setSelect('cost_code_id', d.cost_code_id);
        setSelect('crew_id', d.crew_id);
        setSelect('shift_id', d.shift_id);
        f.querySelector('[name="regular_hours"]').value = d.regular_hours;
        f.querySelector('[name="overtime_hours"]').value = d.overtime_hours ?? '';
        f.querySelector('[name="double_time_hours"]').value = d.double_time_hours ?? '';
        // Keep hours_worked blank by default so the modal preserves whatever
        // split is already stored unless the user explicitly re-splits.
        f.querySelector('[name="hours_worked"]').value = '';
        f.querySelector('#edit_force_overtime').checked = !!d.force_overtime;
        // Honor the stored is_billable flag (falls back to billable_amount>0 for legacy rows)
        var billable = (typeof d.is_billable !== 'undefined' && d.is_billable !== null)
            ? !!d.is_billable
            : parseFloat(d.billable_amount || 0) > 0;
        f.querySelector('[name="is_billable"]').checked = billable;

        // Per diem state comes from the row's allocation (first/only one)
        var alloc = Array.isArray(d.cost_allocations) && d.cost_allocations.length
            ? d.cost_allocations[0]
            : null;
        var pd = alloc ? parseFloat(alloc.per_diem_amount || 0) : 0;
        f.querySelector('#edit_per_diem').checked = pd > 0;
        f.querySelector('#edit_per_diem_amount').value = pd > 0 ? pd : '';

        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', window.BASE_URL+'/timesheets/' + d.id, 'PUT', dt, 'editModal');
        };
        openModal('editModal');

        editFetchWeekHours();
    });
}

// Wire up the live-preview recalculations once the partial is on the page.
document.addEventListener('DOMContentLoaded', function() {
    var f = document.getElementById('editForm');
    if (!f) return;
    f.querySelector('[name="hours_worked"]').addEventListener('input', editUpdateSplitPreview);
    f.querySelector('[name="force_overtime"]')?.addEventListener('change', editUpdateSplitPreview);
    f.querySelector('[name="employee_id"]').addEventListener('change', editFetchWeekHours);
    f.querySelector('[name="date"]').addEventListener('change', editFetchWeekHours);
});
</script>
