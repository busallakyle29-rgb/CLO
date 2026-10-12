/**
 * Document Management - Form and Sidebar JavaScript
 * Handles document search form and mobile sidebar toggle
 */

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.file-history-search').forEach(function (historyInput) {
    historyInput.addEventListener('input', function () {
      const listId = historyInput.dataset.listId;
      const list = listId ? document.getElementById(listId) : null;
      const noResults = historyInput.closest('.modal-body')?.querySelector('.file-history-no-results');

      if (!list) return;

      const query = (historyInput.value || '').toLowerCase().trim();
      const items = list.querySelectorAll('.file-history-item');
      let visibleCount = 0;

      items.forEach(function (item) {
        const fileName = (item.dataset.fileName || '').toLowerCase();
        const matches = query === '' || fileName.includes(query);
        item.style.display = matches ? '' : 'none';
        if (matches) visibleCount += 1;
      });

      if (noResults) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
      }
    });
  });
});
