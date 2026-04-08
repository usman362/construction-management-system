<script>
function editCrew(id, dt) {
    $.get(window.BASE_URL+'/crews/' + id + '/edit', function(resp) {
        var d = resp.crew;
        if (!d) return;
        var f = document.getElementById('editForm');
        f.querySelector('#edit_id').value = d.id;
        f.querySelector('[name="name"]').value = d.name || '';
        f.querySelector('[name="project_id"]').value = d.project_id != null ? String(d.project_id) : '';
        f.querySelector('[name="foreman_id"]').value = d.foreman_id != null ? String(d.foreman_id) : '';
        f.querySelector('[name="shift_id"]').value = d.shift_id != null ? String(d.shift_id) : '';
        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', window.BASE_URL+'/crews/' + d.id, 'PUT', dt, 'editModal');
        };
        openModal('editModal');
    });
}
</script>
