<script>
function editTimesheet(id, dt) {
    $.get('/timesheets/' + id + '/edit', function(d) {
        var f = document.getElementById('editForm');
        function setSelect(name, val) {
            var el = f.querySelector('[name="' + name + '"]');
            if (el) el.value = val != null && val !== '' ? String(val) : '';
        }
        f.querySelector('#edit_id').value = d.id;
        f.querySelector('[name="date"]').value = d.date ? String(d.date).split('T')[0].split(' ')[0] : '';
        setSelect('employee_id', d.employee_id);
        setSelect('project_id', d.project_id);
        setSelect('crew_id', d.crew_id);
        setSelect('shift_id', d.shift_id);
        f.querySelector('[name="regular_hours"]').value = d.regular_hours;
        f.querySelector('[name="overtime_hours"]').value = d.overtime_hours ?? '';
        f.querySelector('[name="double_time_hours"]').value = d.double_time_hours ?? '';
        f.querySelector('[name="is_billable"]').checked = parseFloat(d.billable_amount || 0) > 0;
        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', '/timesheets/' + d.id, 'PUT', dt, 'editModal');
        };
        openModal('editModal');
    });
}
</script>
