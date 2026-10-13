/**
 * Document Management - Sidebar and Form Submission
 * Handles mobile sidebar toggle and prevents accidental form double-submit
 */

window.openFileHistoryModal = function (modalId) {
  const template = document.getElementById(modalId);
  let modalElement = template;

  if (template && template.tagName === 'TEMPLATE') {
    modalElement = template.content.firstElementChild;
    if (modalElement) {
      modalElement.id = modalId;
      document.body.appendChild(modalElement);
      template.remove();
    }
  }

  if (!modalElement) {
    const button = document.querySelector(
      '[data-file-history-target="' + modalId + '"]'
    );

    if (button) {
      modalElement = document.createElement('div');
      modalElement.id = modalId;
      modalElement.className = 'modal fade';
      modalElement.tabIndex = -1;
      modalElement.setAttribute('aria-hidden', 'true');
      modalElement.innerHTML = `
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-paperclip text-info"></i>
                File History - ${button.dataset.documentTracking || ''}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-warning mb-0 p-3">
                <i class="bi bi-exclamation-triangle"></i>
                File history could not be loaded. Refresh the page and try again.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                Close
              </button>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modalElement);
    }
  }

  if (
    !modalElement
  ) {
    return true;
  }

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  if (window.bootstrap && window.bootstrap.Modal) {
    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
  } else {
    modalElement.style.display = 'block';
    modalElement.classList.add('show');
    modalElement.removeAttribute('aria-hidden');
  }
  return false;
};

document.addEventListener('DOMContentLoaded', function () {
  let previewModal;

  const receiveDocumentModal = document.getElementById('receiveDocumentModal');
  if (receiveDocumentModal && receiveDocumentModal.parentElement !== document.body) {
    document.body.appendChild(receiveDocumentModal);
  }

  document.querySelectorAll('template[id^="documentFiles"]').forEach(function (template) {
    const modalElement = template.content.firstElementChild;

    if (modalElement) {
      modalElement.id = template.id;
      document.body.appendChild(modalElement);
    }
    template.remove();
  });

  function getPreviewModal() {
    if (previewModal) {
      return previewModal;
    }

    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.id = 'documentPreviewModal';
    modalElement.tabIndex = -1;
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.innerHTML = `
      <div class="modal-dialog document-preview-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content document-preview-modal">
          <div class="modal-header">
            <h5 class="modal-title">Document preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0 document-preview-body"></div>
        </div>
      </div>
    `;
    document.body.appendChild(modalElement);
    previewModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    return previewModal;
  }

  document.addEventListener('click', function (event) {
    const target = event.target;
    const previewLink = target instanceof Element
      ? target.closest('[data-document-preview]')
      : null;

    if (!previewLink || !window.bootstrap || !window.bootstrap.Modal) {
      return;
    }

    event.preventDefault();

    const modal = getPreviewModal();
    const modalElement = document.getElementById('documentPreviewModal');
    const title = modalElement.querySelector('.modal-title');
    const body = modalElement.querySelector('.document-preview-body');
    const url = previewLink.dataset.previewUrl || '';
    const name = previewLink.dataset.previewName || 'Document';
    const mime = (previewLink.dataset.previewMime || '').toLowerCase();

    title.textContent = name;
    body.replaceChildren();

    if (
      mime === 'application/pdf'
      || /\.pdf(?:$|\?)/i.test(url)
      || /\.docx?(?:$|\?)/i.test(name)
    ) {
      const frame = document.createElement('iframe');
      frame.src = mime === 'application/pdf' || /\.pdf(?:$|\?)/i.test(url)
        ? url
        : url + (url.includes('?') ? '&' : '?') + 'preview=1';
      frame.title = `Preview of ${name}`;
      frame.className = 'document-preview-frame';
      body.appendChild(frame);
    } else {
      const message = document.createElement('div');
      message.className = 'p-4 text-center text-muted';
      message.innerHTML = `
        <i class="bi bi-file-earmark-word fs-1 d-block mb-3"></i>
        <p class="mb-3">This file type cannot be previewed in the browser.</p>
      `;

      const download = document.createElement('a');
      download.href = url + (url.includes('?') ? '&' : '?') + 'download=1';
      download.className = 'btn btn-primary';
      download.textContent = 'Download file';
      message.appendChild(download);
      body.appendChild(message);
    }

    modal.show();
  });

  document.addEventListener('click', function (event) {
    const target = event.target;
    const fileHistoryButton = target instanceof Element
      ? target.closest('[data-file-history-target]')
      : null;

    if (!fileHistoryButton || !window.bootstrap || !window.bootstrap.Modal) {
      return;
    }

    const modalId = fileHistoryButton.dataset.fileHistoryTarget || '';
    const modalElement = modalId ? document.getElementById(modalId) : null;

    if (!modalElement) {
      return;
    }

    if (modalElement.parentElement !== document.body) {
      document.body.appendChild(modalElement);
    }

    event.preventDefault();
    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
  });

  const originDepartment = receiveDocumentModal?.querySelector('select[name="origin_department_id"]');
  const originDepartmentOther = receiveDocumentModal?.querySelector('input[name="origin_department_other"]');
  const currentDepartment = receiveDocumentModal?.querySelector('select[name="current_department_id"]');
  const assignedStaff = receiveDocumentModal?.querySelector('select[name="current_assignee_user_id"]');

  if (originDepartment && originDepartmentOther) {
    function toggleOtherOrigin() {
      const isOther = originDepartment.value === 'other';
      originDepartmentOther.classList.toggle('d-none', !isOther);
      originDepartmentOther.required = isOther;

      if (!isOther) {
        originDepartmentOther.value = '';
      }
    }

    originDepartment.addEventListener('change', toggleOtherOrigin);
    toggleOtherOrigin();
  }

  if (currentDepartment && assignedStaff) {
    function filterAssignedStaff() {
      const departmentId = currentDepartment.value;

      assignedStaff.querySelectorAll('option[data-department-id]').forEach(function (option) {
        const matches = departmentId !== '' && option.dataset.departmentId === departmentId;
        option.hidden = !matches;

        if (!matches && option.selected) {
          assignedStaff.value = '';
        }
      });
    }

    currentDepartment.addEventListener('change', filterAssignedStaff);
    filterAssignedStaff();
  }

  /* Prevent accidental double-submit */
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const submitButton = form.querySelector('button[type="submit"]');

      if (submitButton && submitButton.name !== 'archive_document') {
        setTimeout(function () {
          submitButton.disabled = true;
        }, 50);
      }
    });
  });
});
