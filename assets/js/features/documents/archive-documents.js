/**
 * Archive Documents Search JavaScript
 * Live suggestions and table filtering while typing.
 */

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('archiveSearchForm');
  const input = document.getElementById('archiveSearch');
  const panel = document.getElementById('archiveSuggestions');
  const tbody = document.getElementById('archiveDocumentsBody');

  if (!form || !input || !panel) {
    return;
  }

  const suggestUrl = form.getAttribute('data-suggest-url') || 'archive_documents.php';
  const rows = tbody
    ? Array.from(tbody.querySelectorAll('.archive-doc-row'))
    : [];

  let debounceTimer = null;
  let activeIndex = -1;
  let currentItems = [];
  let requestId = 0;

  function isPlaceholderRow(row) {
    if (row.classList.contains('js-live-search-empty')) {
      return true;
    }

    return row.classList.contains('archive-doc-row') === false;
  }

  function ensureEmptyRow() {
    if (!tbody) {
      return null;
    }

    let emptyRow = tbody.querySelector('tr.js-live-search-empty');

    if (!emptyRow) {
      emptyRow = document.createElement('tr');
      emptyRow.className = 'js-live-search-empty';

      const td = document.createElement('td');
      td.colSpan = 8;
      td.className = 'text-center py-5 text-muted';
      td.textContent = 'No matching archived documents.';
      emptyRow.appendChild(td);
      tbody.appendChild(emptyRow);
    }

    return emptyRow;
  }

  function filterTable(query) {
    if (!tbody) {
      return;
    }

    const q = (query || '').toLowerCase().trim();
    let visible = 0;

    rows.forEach(function (row) {
      const text = (row.textContent || '').replace(/\s+/g, ' ').toLowerCase();
      const match = q === '' || text.indexOf(q) !== -1;
      row.hidden = !match;

      if (match) {
        visible += 1;
      }
    });

    const emptyRow = ensureEmptyRow();
    const hasServerEmpty = Array.from(tbody.querySelectorAll(':scope > tr')).some(
      function (row) {
        return isPlaceholderRow(row) && !row.classList.contains('js-live-search-empty');
      }
    );

    if (emptyRow) {
      emptyRow.hidden = visible > 0 || hasServerEmpty;
    }
  }

  function hidePanel() {
    panel.hidden = true;
    panel.innerHTML = '';
    activeIndex = -1;
    currentItems = [];
    input.setAttribute('aria-expanded', 'false');
  }

  function highlight(index) {
    const buttons = panel.querySelectorAll('.archive-suggest-item');
    buttons.forEach(function (button, buttonIndex) {
      button.classList.toggle('is-active', buttonIndex === index);
    });
  }

  function selectSuggestion(item) {
    input.value = item.tracking_no || item.title || '';
    filterTable(input.value);
    hidePanel();
  }

  function renderSuggestions(items) {
    panel.innerHTML = '';
    currentItems = items;
    activeIndex = items.length ? 0 : -1;

    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'archive-suggest-empty';
      empty.textContent = 'No matching archived documents.';
      panel.appendChild(empty);
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      return;
    }

    items.forEach(function (item, index) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'archive-suggest-item' + (index === 0 ? ' is-active' : '');

      const code = document.createElement('div');
      code.className = 'archive-suggest-code';
      code.textContent = item.tracking_no || '';

      const title = document.createElement('span');
      title.className = 'archive-suggest-title';
      title.textContent = item.title || '';

      button.appendChild(code);
      button.appendChild(title);

      button.addEventListener('mousedown', function (event) {
        event.preventDefault();
        selectSuggestion(item);
      });

      panel.appendChild(button);
    });

    panel.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }

  function fetchSuggestions(query) {
    const thisRequest = ++requestId;
    const params = new URLSearchParams();
    params.set('suggest', '1');
    params.set('q', query);

    fetch(suggestUrl + '?' + params.toString(), {
      headers: { Accept: 'application/json' },
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (thisRequest !== requestId) {
          return;
        }

        const items = Array.isArray(data.suggestions) ? data.suggestions : [];
        renderSuggestions(items);
      })
      .catch(function () {
        if (thisRequest === requestId) {
          hidePanel();
        }
      });
  }

  input.addEventListener('input', function () {
    const query = (input.value || '').trim();
    window.clearTimeout(debounceTimer);
    filterTable(query);

    if (query === '') {
      hidePanel();
      return;
    }

    debounceTimer = window.setTimeout(function () {
      fetchSuggestions(query);
    }, 180);
  });

  input.addEventListener('keydown', function (event) {
    if (panel.hidden || !currentItems.length) {
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      activeIndex = (activeIndex + 1) % currentItems.length;
      highlight(activeIndex);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      activeIndex = (activeIndex - 1 + currentItems.length) % currentItems.length;
      highlight(activeIndex);
    } else if (event.key === 'Enter' && activeIndex >= 0) {
      event.preventDefault();
      selectSuggestion(currentItems[activeIndex]);
    } else if (event.key === 'Escape') {
      hidePanel();
    }
  });

  input.addEventListener('blur', function () {
    window.setTimeout(hidePanel, 150);
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    filterTable(input.value);
    hidePanel();
  });

  if ((input.value || '').trim() !== '') {
    filterTable(input.value);
  }
});
