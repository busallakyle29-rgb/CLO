document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('editRoleAccessModal');
    const hiddenRoleInput = document.getElementById('edit_role_name');
    const pageCheckboxes = Array.from(
        document.querySelectorAll('input[name="pages[]"]')
    );
    const accessMapElement = document.getElementById('role-access-map');

    if (!modal || !hiddenRoleInput || !accessMapElement) {
        return;
    }

    let accessMap = {};
    try {
        accessMap = JSON.parse(accessMapElement.textContent || '{}');
    } catch (error) {
        console.error('Unable to load role page access map.', error);
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        const roleName = trigger.getAttribute('data-role-name') || '';
        hiddenRoleInput.value = roleName;

        pageCheckboxes.forEach(function (checkbox) {
            checkbox.checked = (accessMap[roleName] || []).includes(
                checkbox.value
            );
        });
    });
});
