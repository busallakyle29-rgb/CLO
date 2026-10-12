/**
 * Document Tracking JavaScript
 * Live search suggestions and attachment file filtering
 */

document.addEventListener('DOMContentLoaded', function () {
  const attachmentFilter = document.querySelector('[data-attachment-filter="true"]');

  if (attachmentFilter) {
    const list = attachmentFilter.closest('.card-body')?.querySelector('.attachment-file-list');
    const noResults = attachmentFilter.closest('.card-body')?.querySelector('.attachment-file-no-results');

    if (list) {
      attachmentFilter.addEventListener('input', function () {
        const query = (attachmentFilter.value || '').toLowerCase().trim();
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
    }
  }

  const suggestForm = document.querySelector('[data-suggest-url]');

  if (!suggestForm) {
    return;
  }

  const suggestUrl = suggestForm.getAttribute('data-suggest-url') || 'document_tracking.php';
  const inputs = suggestForm.querySelectorAll('[data-tracking-suggest]');

  inputs.forEach(function (input) {
    const wrap = input.closest('.tracking-suggest-wrap');
    const panel = wrap ? wrap.querySelector('.tracking-suggest-list') : null;
    const field = input.getAttribute('data-tracking-suggest') || 'title';

    if (!panel) {
      return;
    }

    let debounceTimer = null;
    let activeIndex = -1;
    let currentItems = [];
    let requestId = 0;

    function hidePanel() {
      panel.hidden = true;
      panel.innerHTML = '';
      activeIndex = -1;
      currentItems = [];
      input.setAttribute('aria-expanded', 'false');
    }

    function highlight(index) {
      const buttons = panel.querySelectorAll('.tracking-suggest-item');
      buttons.forEach(function (button, buttonIndex) {
        button.classList.toggle('is-active', buttonIndex === index);
      });
    }

    function openDocument(trackingNo) {
      const params = new URLSearchParams();
      params.set('tracking_no', trackingNo);
      window.location.href = suggestUrl + '?' + params.toString();
    }

    function renderSuggestions(items) {
      panel.innerHTML = '';
      currentItems = items;
      activeIndex = items.length ? 0 : -1;

      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'tracking-suggest-empty';
        empty.textContent = 'No matching documents.';
        panel.appendChild(empty);
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        return;
      }

      items.forEach(function (item, index) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tracking-suggest-item' + (index === 0 ? ' is-active' : '');

        const code = document.createElement('div');
        code.className = 'tracking-suggest-code';
        code.textContent = item.tracking_no || '';

        const title = document.createElement('span');
        title.className = 'tracking-suggest-title';
        title.textContent = item.title || '';

        button.appendChild(code);
        button.appendChild(title);

        button.addEventListener('mousedown', function (event) {
          event.preventDefault();
          openDocument(item.tracking_no);
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
      params.set('field', field);
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
        openDocument(currentItems[activeIndex].tracking_no);
      } else if (event.key === 'Escape') {
        hidePanel();
      }
    });

    input.addEventListener('blur', function () {
      window.setTimeout(hidePanel, 150);
    });
  });
});
