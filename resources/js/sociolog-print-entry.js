// ============================================================
// 🔹 Sociolog – Einzel-Eintrag drucken (final & stabil)
// ============================================================
(function () {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action="print-entry"]');
    if (!btn) return;

    const content = document.querySelector('.sociolog-view');
    if (!content) {
      alert('Druckansicht nicht gefunden.');
      return;
    }

    // ✅ Titel IMMER aus der Seite lesen
    let title = 'Logbuch';
    const heading = document.querySelector('h4');
    if (heading && heading.textContent.trim() !== '') {
      title = heading.textContent.trim();
    }

    const style = `
      <style>
        body {
          font-family: system-ui, -apple-system, BlinkMacSystemFont,
                       "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
          margin: 24px;
          color: #000;
        }
        h1, h2, h3, h4, h5, h6 { color: #000; }
        .sociolog-view { box-shadow: none !important; border: none !important; }
        img, svg, i { filter: grayscale(100%); }
        a { color: #000; text-decoration: underline; }
      </style>
    `;

    const printWin = window.open('', '_blank');
    if (!printWin) {
      alert('Popup blockiert – bitte Popups erlauben.');
      return;
    }

    printWin.document.open();
    printWin.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8">
          <title>${title}</title>
          ${style}
        </head>
        <body>
          <h2>${title}</h2>
          ${content.outerHTML}
        </body>
      </html>
    `);
    printWin.document.close();
    printWin.focus();
    printWin.print();
  });
})();
