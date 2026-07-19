/**
 * ============================================================
 * Sociolog – Tabellenansicht & klickbare Karten
 * ============================================================
 */
(function () {
  'use strict';

  function initSociologTable() {
    const tableId = '#sociologTable';

    if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
      return setTimeout(initSociologTable, 300);
    }

    if (!$(tableId).length) {
      return;
    }

    if (!$.fn.dataTable.isDataTable(tableId)) {
      const lang = document.documentElement.lang || 'en';
      const isGerman = lang.startsWith('de');

      $(tableId).DataTable({
        paging: true,
        info: false,
        autoWidth: false,
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
          url:
            'https://cdn.datatables.net/plug-ins/1.13.8/i18n/' +
            (isGerman ? 'de-DE.json' : 'en-GB.json'),
          search: isGerman ? 'Suche:' : 'Search:',
          lengthMenu: isGerman
            ? 'Zeilen anzeigen _MENU_'
            : 'Show _MENU_ entries',
        },
      });
    }
  }

  // Initialisierung
  document.addEventListener('DOMContentLoaded', initSociologTable);
  document.addEventListener('pjax:end', initSociologTable);

})();
