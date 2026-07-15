/**
 * ============================================================
 * Sociolog – Search Toggle (final)
 * ------------------------------------------------------------
 * - Server-Zustand (aktive Filter) hat Priorität
 * - Nutzerpräferenz wird via localStorage gemerkt
 * - PJAX-sicher
 * ============================================================
 */
(function () {
  'use strict';

  function initSearchToggle() {
    const btnSearch = document.getElementById('toggleSearch');
    const panel = document.getElementById('searchPanel');
    if (!btnSearch || !panel) {
      return;
    }

    // verhindert doppelte Initialisierung
    if (btnSearch.dataset.initialized === '1') {
      return;
    }
    btnSearch.dataset.initialized = '1';

    const labelClosed = btnSearch.querySelector('.label-closed');
    const labelOpen   = btnSearch.querySelector('.label-open');

    let isOpen = !panel.classList.contains('d-none')
      ? true
      : localStorage.getItem('sociologSearchOpen') === '1';

    function renderSearch() {
      if (isOpen) {
        panel.classList.remove('d-none');
        labelClosed?.classList.add('d-none');
        labelOpen?.classList.remove('d-none');
      } else {
        panel.classList.add('d-none');
        labelClosed?.classList.remove('d-none');
        labelOpen?.classList.add('d-none');
      }
    }

    btnSearch.addEventListener('click', function (e) {
      e.preventDefault();
      isOpen = !isOpen;
      localStorage.setItem('sociologSearchOpen', isOpen ? '1' : '0');
      renderSearch();
    });

    renderSearch();
  }

  document.addEventListener('DOMContentLoaded', initSearchToggle);
  document.addEventListener('pjax:end', initSearchToggle);
})();