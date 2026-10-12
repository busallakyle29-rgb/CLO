/**
 * Header Initialization JavaScript
 * Handles mobile sidebar toggle and live in-page search (no refresh).
 */

document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('appSidebar');
  const toggle = document.getElementById('sidebarToggle');
  const collapseToggle = document.getElementById('sidebarCollapseToggle');
  const overlay = document.getElementById('mobileSidebarOverlay');

  if (sidebar && collapseToggle) {
    function setCollapsed(collapsed) {
      sidebar.classList.toggle('is-collapsed', collapsed);
      collapseToggle.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
      collapseToggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
    }

    setCollapsed(true);

    sidebar.querySelectorAll('.sidebar-item').forEach(function (item) {
      const label = item.querySelector('span');
      const pageName = label?.textContent.trim();

      if (pageName) {
        item.setAttribute('title', pageName);
        item.setAttribute('aria-label', pageName);
      }
    });

    const sidebarTooltip = document.createElement('div');
    sidebarTooltip.className = 'sidebar-tooltip';
    document.body.appendChild(sidebarTooltip);

    sidebar.querySelectorAll('.sidebar-item').forEach(function (item) {
      function showTooltip() {
        if (!sidebar.classList.contains('is-collapsed') || window.innerWidth <= 991.98) {
          return;
        }

        const pageName = item.getAttribute('title');
        const itemRect = item.getBoundingClientRect();

        if (!pageName) {
          return;
        }

        sidebarTooltip.textContent = pageName;
        sidebarTooltip.style.left = `${itemRect.right + 10}px`;
        sidebarTooltip.style.top = `${itemRect.top + (itemRect.height / 2)}px`;
        sidebarTooltip.style.transform = 'translateY(-50%)';
        sidebarTooltip.classList.add('is-visible');
      }

      function hideTooltip() {
        sidebarTooltip.classList.remove('is-visible');
      }

      item.addEventListener('mouseenter', showTooltip);
      item.addEventListener('mouseleave', hideTooltip);
      item.addEventListener('focus', showTooltip);
      item.addEventListener('blur', hideTooltip);
    });

    collapseToggle.addEventListener('click', function () {
      const collapsed = !sidebar.classList.contains('is-collapsed');
      setCollapsed(collapsed);
    });

  }

  if (sidebar && toggle) {
    function openSidebar() {
      sidebar.classList.add('show-mobile');
      document.body.classList.add('mobile-sidebar-open');

      if (overlay) {
        overlay.classList.add('show');
      }

      toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
      sidebar.classList.remove('show-mobile');
      document.body.classList.remove('mobile-sidebar-open');

      if (overlay) {
        overlay.classList.remove('show');
      }

      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (event) {
      event.preventDefault();

      if (sidebar.classList.contains('show-mobile')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (overlay) {
      overlay.addEventListener('click', function () {
        closeSidebar();
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 991.98) {
        closeSidebar();
      }
    });
  }

  const notificationMenu = document.querySelector('.notification-menu');

  if (notificationMenu) {
    function markNotificationAsRead(item) {
      const auditLogId = item.dataset.notificationReadId;
      if (!auditLogId || !item.classList.contains('is-unread')) {
        return;
      }

      const endpoint = notificationMenu.dataset.notificationReadEndpoint;
      const csrfToken = notificationMenu.dataset.notificationCsrf;
      if (!endpoint || !csrfToken) {
        return;
      }

      item.classList.remove('is-unread');
      item.classList.add('is-read');

      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('audit_log_ids[]', auditLogId);

      fetch(endpoint, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        keepalive: true
      }).catch(function () {
        // The visual state is still useful until the next page refresh.
      });
    }

    notificationMenu.addEventListener('click', function (event) {
      const item = event.target.closest('.notification-item.is-unread[data-notification-read-id]');
      if (item) {
        markNotificationAsRead(item);
      }
    });
  }

  function shouldSkipLiveSearch(input) {
    if (!input) {
      return true;
    }

    if (input.classList.contains('file-history-search')) {
      return true;
    }

    if (input.getAttribute('data-attachment-filter') === 'true') {
      return true;
    }

    if (input.id === 'departmentSearch' || input.id === 'archiveSearch') {
      return true;
    }

    if (input.closest('.modal')) {
      return true;
    }

    return false;
  }

  function formHasExtraFilters(form) {
    if (!form) {
      return false;
    }

    return Boolean(
      form.querySelector(
        'select, input[type="date"], input[name="tracking_no"], input[name="title"]'
      )
    );
  }

  function findTargetTbody(input) {
    const form = input.closest('form');
    const container =
      (form && form.closest('.card, .audit-filter')) ||
      input.closest('.card, .audit-filter');

    if (container) {
      let sibling = container.nextElementSibling;

      while (sibling) {
        const tbody = sibling.querySelector('table tbody');
        if (tbody) {
          return tbody;
        }
        sibling = sibling.nextElementSibling;
      }
    }

    const main = document.querySelector('main') || document.body;
    const tbodies = main.querySelectorAll('table tbody');

    for (let i = 0; i < tbodies.length; i += 1) {
      if (
        input.compareDocumentPosition(tbodies[i]) &
        Node.DOCUMENT_POSITION_FOLLOWING
      ) {
        return tbodies[i];
      }
    }

    return null;
  }

  function isPlaceholderRow(row) {
    if (row.classList.contains('js-live-search-empty')) {
      return true;
    }

    const cells = row.querySelectorAll(':scope > td');

    if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
      return !row.querySelector('button, a.btn, .dropdown');
    }

    return false;
  }

  function filterTbody(tbody, query) {
    const q = (query || '').toLowerCase().trim();
    const rows = Array.from(tbody.querySelectorAll(':scope > tr'));
    const table = tbody.closest('table');
    const headerCells = table
      ? table.querySelectorAll('thead th')
      : [];
    const colCount = headerCells.length || 1;
    let visible = 0;

    rows.forEach(function (row) {
      if (isPlaceholderRow(row)) {
        return;
      }

      const text = (row.textContent || '').replace(/\s+/g, ' ').toLowerCase();
      const match = q === '' || text.indexOf(q) !== -1;
      row.hidden = !match;

      if (match) {
        visible += 1;
      }
    });

    let emptyRow = tbody.querySelector('tr.js-live-search-empty');
    const hasServerEmpty = rows.some(function (row) {
      return (
        isPlaceholderRow(row) &&
        !row.classList.contains('js-live-search-empty')
      );
    });

    if (visible === 0 && !hasServerEmpty) {
      if (!emptyRow) {
        emptyRow = document.createElement('tr');
        emptyRow.className = 'js-live-search-empty';

        const td = document.createElement('td');
        td.colSpan = colCount;
        td.className = 'text-center py-5 text-muted';
        td.textContent = 'No matching records.';
        emptyRow.appendChild(td);
        tbody.appendChild(emptyRow);
      }

      emptyRow.hidden = false;
    } else if (emptyRow) {
      emptyRow.hidden = true;
    }
  }

  function bindLiveTableSearch(searchInput) {
    if (shouldSkipLiveSearch(searchInput)) {
      return;
    }

    if (searchInput.dataset.liveSearchBound === '1') {
      return;
    }

    const tbody = findTargetTbody(searchInput);

    if (!tbody) {
      return;
    }

    searchInput.dataset.liveSearchBound = '1';
    searchInput.setAttribute('autocomplete', 'off');

    const form = searchInput.closest('form');

    function applyFilter() {
      filterTbody(tbody, searchInput.value);
    }

    searchInput.addEventListener('input', applyFilter);

    if (form && !formHasExtraFilters(form)) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        applyFilter();
      });
    }

    if ((searchInput.value || '').trim() !== '') {
      applyFilter();
    }
  }

  document
    .querySelectorAll('input[name="search"], input[type="search"]')
    .forEach(bindLiveTableSearch);
});
