// ============================================================
// Sociolog – Tabelle drucken (PJAX-sicher, minimal)
// ============================================================
(function () {
  function bindPrintTable() {
    const btn = document.getElementById('printTable');
    if (!btn) return;

    btn.onclick = function (e) {
      e.preventDefault();
      window.print();
    };
  }

  document.addEventListener('DOMContentLoaded', bindPrintTable);
  document.addEventListener('pjax:end', bindPrintTable);
})();
