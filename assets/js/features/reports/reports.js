/**
 * Reports JavaScript
 * Handles print functionality for reports
 */

document.addEventListener('DOMContentLoaded', function () {
  /*
   * Auto submit when changing date is optional.
   * User can select date then click Generate Report.
   */

  const printButtons = document.querySelectorAll('[data-report-print]');

  printButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      window.print();
    });
  });
});
