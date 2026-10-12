/**
 * Department Management JavaScript
 * Handles edit/delete modals and table search/filter.
 */

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.btn-edit-department').forEach(function (button) {
    button.addEventListener('click', function () {
      var idField = document.getElementById('edit_department_id');
      var codeField = document.getElementById('edit_department_code');
      var nameField = document.getElementById('edit_department_name');
      var descField = document.getElementById('edit_description');
      var statusField = document.getElementById('edit_is_active');

      if (idField) idField.value = button.getAttribute('data-department-id') || '';
      if (codeField) codeField.value = button.getAttribute('data-department-code') || '';
      if (nameField) nameField.value = button.getAttribute('data-department-name') || '';
      if (descField) descField.value = button.getAttribute('data-description') || '';
      if (statusField) statusField.value = button.getAttribute('data-is-active') || '1';
    });
  });

  document.querySelectorAll('.btn-delete-department').forEach(function (button) {
    button.addEventListener('click', function () {
      var idField = document.getElementById('delete_department_id');
      var nameLabel = document.getElementById('delete_department_name');

      if (idField) idField.value = button.getAttribute('data-department-id') || '';
      if (nameLabel) nameLabel.textContent = button.getAttribute('data-department-name') || 'this department';
    });
  });

  var searchInput = document.getElementById('departmentSearch');
  var statusFilter = document.getElementById('statusFilter');
  var departmentTableRows = document.querySelectorAll('.department-table-row');

  function filterDepartments() {
    var searchTerm = ((searchInput && searchInput.value) || '').toLowerCase().trim();
    var statusValue = (statusFilter && statusFilter.value) || '';

    departmentTableRows.forEach(function (row) {
      var cells = row.querySelectorAll('td');
      var deptCode = ((cells[0] && cells[0].textContent) || '').toLowerCase();
      var deptName = ((cells[1] && cells[1].textContent) || '').toLowerCase();
      var deptDesc = ((cells[2] && cells[2].textContent) || '').toLowerCase();
      var headName = ((cells[3] && cells[3].textContent) || '').toLowerCase();
      var isActive = row.getAttribute('data-active') === '1' ? '1' : '0';

      var matchesSearch = searchTerm === '' ||
        deptName.indexOf(searchTerm) !== -1 ||
        deptCode.indexOf(searchTerm) !== -1 ||
        deptDesc.indexOf(searchTerm) !== -1 ||
        headName.indexOf(searchTerm) !== -1;

      var matchesStatus = statusValue === '' || isActive === statusValue;

      row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterDepartments);
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', filterDepartments);
  }
});
