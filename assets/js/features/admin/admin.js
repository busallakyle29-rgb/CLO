/**
 * Admin User Management JavaScript
 * Handles user edit, delete, and status toggle modal population
 */

document.querySelectorAll('.btn-edit-user').forEach(function (button) {
  button.addEventListener('click', function () {
    document.getElementById('edit_user_id').value = button.dataset.userId || '';
    document.getElementById('edit_employee_no').value = button.dataset.employeeNo || '';
    document.getElementById('edit_username').value = button.dataset.username || '';
    document.getElementById('edit_email').value = button.dataset.email || '';
    document.getElementById('edit_first_name').value = button.dataset.firstName || '';
    document.getElementById('edit_last_name').value = button.dataset.lastName || '';
    const roleSelect = document.getElementById('edit_role_id');
    const lockedRole = document.getElementById('edit_role_id_locked');
    const roleLockNote = document.getElementById('edit_role_lock_note');
    const isCurrentUser = button.dataset.isCurrentUser === '1';
    roleSelect.value = button.dataset.roleId || '';
    roleSelect.disabled = isCurrentUser;
    lockedRole.disabled = !isCurrentUser;
    lockedRole.value = isCurrentUser ? (button.dataset.roleId || '') : '';
    roleLockNote.classList.toggle('d-none', !isCurrentUser);
    document.getElementById('edit_department_id').value = button.dataset.departmentId || '';
    document.getElementById('edit_is_active').value = button.dataset.isActive || '1';
  });
});

document.querySelectorAll('.btn-delete-user').forEach(function (button) {
  button.addEventListener('click', function () {
    document.getElementById('delete_user_id').value = button.dataset.userId || '';
    document.getElementById('delete_user_name').textContent = button.dataset.fullName || 'this user';
  });
});

document.querySelectorAll('.btn-toggle-user-status').forEach(function (button) {
  button.addEventListener('click', function () {
    const actionLabel = button.dataset.targetLabel || 'Update';
    document.getElementById('toggle_user_id').value = button.dataset.userId || '';
    document.getElementById('toggle_target_status').value = button.dataset.targetStatus || '0';
    document.getElementById('toggle_user_name').textContent = button.dataset.fullName || 'this user';
    document.getElementById('toggle_action_label').textContent = actionLabel;
    document.getElementById('toggle_submit_btn').textContent = actionLabel;
    document.getElementById('toggle_status_title').textContent = actionLabel + ' User';
  });
});
