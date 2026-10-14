/** Native ActiveForm validation; scoped return after successful modal submission. */
humhub.module('sociolog.form', function (module, require, $) {
    'use strict';

    const additions = require('ui.additions');
    const stateKey = 'sociologFormReturn';

    function rememberForms($forms) {
        $forms.each(function () {
            const $modal = $(this).closest('.modal');
            if (!$modal.length) {
                return;
            }
            const url = $(this).attr('data-sociolog-return-url');
            $modal.removeData(stateKey);
            if (url) {
                $modal.data(stateKey, {url: url});
            }
        });
    }

    function init() {
        // Native additions run on initial load, late assets, modal content and PJAX.
        additions.register('sociolog.form', 'form', rememberForms);
        $(document).off('.sociologForm', '.modal')
            .on('show.bs.modal.sociologForm', '.modal', function () {
                $(this).removeData(stateKey);
                rememberForms($(this).find('form'));
            })
            .on('hidden.bs.modal.sociologForm', '.modal', function () {
                const $modal = $(this);
                const state = $modal.data(stateKey);
                // HumHub may reset its dialog before triggering "submitted".
                // Retain the state for that synchronous completion only.
                setTimeout(function () {
                    if ($modal.data(stateKey) === state) {
                        $modal.removeData(stateKey);
                    }
                }, 0);
            })
            .on('submitted.sociologForm', '.modal', function (event, response) {
                const $modal = $(this);
                const state = $modal.data(stateKey);
                if (!state || !response || response.success !== true) {
                    return;
                }
                $modal.removeData(stateKey);
                window.location.assign(state.url);
            });
    }

    module.export({init: init});
});
