// protected/modules/sociolog/resources/js/sociolog-tooltips.js
(function () {
  'use strict';

  function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    const tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
  }

  document.addEventListener('DOMContentLoaded', initTooltips);
  document.addEventListener('humhub:ready', initTooltips);
  document.addEventListener('pjax:end', initTooltips);
})();
