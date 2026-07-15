/**
 * ============================================================
 * Sociolog – Formularlogik (HumHub 1.18+)
 * ------------------------------------------------------------
 * - Initialisiert Modal-Formulare automatisch
 * - Stellt Redirect nach Speichern auf letzte Ansicht sicher
 * - Kompatibel mit HumHub PJAX und Bootstrap 5
 * ============================================================
 */

(function () {
  'use strict';

  /**
   * 🔹 Initialisiert HumHub-Modalformulare, falls nicht automatisch erkannt
   */
  function initModalForms() {
    if (typeof humhub === 'undefined' || !humhub.require) {
      console.warn('[Sociolog] humhub.require nicht verfügbar – warte...');
      setTimeout(initModalForms, 500);
      return;
    }

    const modal = humhub.require('ui.modal');
    const forms = document.querySelectorAll('form[data-ui-widget="form"]');

    forms.forEach(form => {
      if (!form.dataset.uiInit || form.dataset.uiInit !== 'modal.form') {
        form.dataset.uiInit = 'modal.form';
        modal.initForm($(form));
        console.log('[Sociolog] Modal-Form initialisiert:', form);
      }
    });
  }

  /**
   * 🔁 Leitet nach dem Speichern zur letzten Ansicht (cards/table)
   */
  function initRedirectAfterSave() {
    document.addEventListener('humhub:modules:ui:modal:submitted', () => {
      const mode = localStorage.getItem('sociologView') || 'cards';
      const baseUrl = humhub.config.get('baseUrl') || '';
      window.location.href = baseUrl + '/sociolog/entry/index?view=' + mode;
    });

    // Fallback bei klassischem Formularsubmit
    document.addEventListener('submit', event => {
      const form = event.target.closest('form');
      if (!form) return;

      form.addEventListener('ajaxSuccess', () => {
        const mode = localStorage.getItem('sociologView') || 'cards';
        const baseUrl = humhub.config.get('baseUrl') || '';
        window.location.href = baseUrl + '/sociolog/entry/index?view=' + mode;
      });
    });
  }

  /**
   * 🚀 Initialisierung
   */
  document.addEventListener('DOMContentLoaded', function () {
    initModalForms();
    initRedirectAfterSave();
  });

  // Auch nach PJAX-Reloads
  document.addEventListener('pjax:end', initModalForms);
})();
