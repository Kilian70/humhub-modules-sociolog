// ============================================================
// Sociolog – Tabelle drucken (PJAX-sicher)
// ============================================================
(function () {
  'use strict';

  // Ereignisdelegation funktioniert sowohl beim ersten Seitenaufruf als auch
  // nach einer PJAX-Navigation. Eine Bindung an DOMContentLoaded ist hier
  // unzuverlässig, weil HumHub Asset-Dateien am Seitenende laden kann.
  document.addEventListener('click', function (event) {
    const button = event.target.closest('#printTable');
    if (!button) {
      return;
    }

    event.preventDefault();
    window.print();
  });
})();
