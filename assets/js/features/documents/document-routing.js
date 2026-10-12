/**
 * Document Routing JavaScript
 * Handles dynamic user selection based on destination department
 * Note: departmentUsers data must be passed via window or data attributes
 */

document.addEventListener('DOMContentLoaded', function () {
  const dataElement = document.getElementById('department-users-data');
  let departmentUsers = {};

  if (dataElement) {
    try {
      departmentUsers = JSON.parse(dataElement.textContent || '{}');
    } catch (error) {
      console.error('Unable to load department users.', error);
      return;
    }
  }

  const destinationSelects = document.querySelectorAll('.destination-department');

  destinationSelects.forEach(function (destinationSelect) {
    const documentId = destinationSelect.dataset.document;
    const userSelect = document.getElementById('routedUser' + documentId);
    const externalGroup = document.getElementById('externalRecipientGroup' + documentId);
    const externalInput = document.getElementById('externalRecipient' + documentId);

    if (!userSelect || !externalGroup || !externalInput) {
      return;
    }

    destinationSelect.addEventListener('change', function () {
      const departmentId = this.value;
      const users = departmentUsers[departmentId] || [];

      userSelect.innerHTML = '<option value="">Unassigned</option>';
      externalInput.value = '';
      externalGroup.hidden = !departmentId || users.length > 0;
      externalInput.disabled = !departmentId || users.length > 0;
      externalInput.required = Boolean(departmentId && users.length === 0);
      userSelect.disabled = Boolean(departmentId && users.length === 0);

      if (!departmentId || users.length === 0) {
        return;
      }

      users.forEach(function (user) {
        const option = document.createElement('option');
        const firstName = (user.first_name || '').trim();
        const lastName = (user.last_name || '').trim();
        const username = (user.username || '').trim();
        const fullName = [firstName, lastName].filter(Boolean).join(' ') || username || 'User';

        option.value = user.user_id;
        option.textContent = fullName;
        userSelect.appendChild(option);
      });
    });
  });
});
