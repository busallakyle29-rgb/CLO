document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.audit-details-modal').forEach(function (modal) {
    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  });
});
