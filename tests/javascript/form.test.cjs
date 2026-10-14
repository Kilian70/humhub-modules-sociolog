/* Browser regression tests using each official core's jQuery, additions and modal code.
 * Run: NODE_PATH=<playwright location> BROWSER_EXECUTABLE=<Chrome> node form.test.cjs <core root>
 * Client transport and Bootstrap hiding are controlled test doubles. No real requests/data.
 */
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const core = process.argv[2];
if (!core) throw new Error('Pass the version-matched official HumHub core root.');
const jsRoot = fs.existsSync(path.join(core, 'static/js/humhub/humhub.ui.modal.js'))
    ? path.join(core, 'static/js/humhub') : path.join(core, 'protected/humhub/resources/js/humhub');
const moduleJs = path.resolve(__dirname, '../../resources/js/sociolog-form.js');

(async function () {
    const browser = await chromium.launch({headless: true, executablePath: process.env.BROWSER_EXECUTABLE || undefined});
    const results = [];
    try {
        async function setup(formHtml, late = false) {
            const page = await browser.newPage();
            page.errors = [];
            page.on('pageerror', error => page.errors.push(error.message));
            await page.route('**/*', route => route.fulfill({status: 200, contentType: 'text/html', body: '<html><body></body></html>'}));
            await page.goto('http://sociolog.test/subfolder/index.php?r=sociolog%2Fentry%2Fcreate');
            await page.setContent('<div class="modal" id="testModal">' + formHtml + '</div><form id="foreign" data-ui-widget="form"></form>');
            await page.addScriptTag({path: path.join(core, 'protected/vendor/npm-asset/jquery/dist/jquery.js')});
            await page.evaluate(() => {
                const object = {isDefined: value => value !== undefined && value !== null, isFunction: value => typeof value === 'function',
                    inherits: (child, parent) => {child.prototype = Object.create(parent.prototype); child.prototype.constructor = child;}};
                const modules = {'util': {object}, 'util.object': object, 'event': {on() {}}, 'i18n': {}, 'ui.loader': {}, 'ui.widget': {Widget: function () {}}};
                window.modules = modules;
                modules.client = {submit: () => window.rejectTransport ? Promise.reject(new Error('Synthetic network failure')) : Promise.resolve(window.response)};
                window.humhub = {initialized: false, module: (id, factory) => {
                    const module = modules[id] = {log: {error() {}}, export: value => Object.assign(module, value)};
                    factory(module, name => modules[name], jQuery);
                }};
                window.response = {success: true};
                window.resetOnHide = false;
            });
            await page.addScriptTag({path: path.join(jsRoot, 'humhub.ui.additions.js')});
            await page.addScriptTag({path: path.join(jsRoot, 'humhub.ui.modal.js')});
            if (late) await page.evaluate(() => {humhub.initialized = true;});
            await page.addScriptTag({path: moduleJs});
            await page.evaluate(() => {
                modules['sociolog.form'].init();
                if (!humhub.initialized) modules['ui.additions'].applyTo(jQuery('body'));
                const $modal = jQuery('#testModal');
                const modal = {$: $modal, getModalInstance: () => ({hide() {
                    $modal.trigger('hidden.bs.modal');
                    if (window.resetOnHide) $modal.empty();
                }}), setDialog() {}, show() {}};
                modal.close = function () {modules['ui.modal'].Modal.prototype.close.call(modal);};
                modules['ui.modal'].get = () => modal;
                window.submitNative = () => modules['ui.modal'].submit({$trigger: jQuery('#save'), $form: jQuery('#entry-form')});
            });
            return page;
        }
        const url = 'http://sociolog.test/subfolder/index.php?r=sociolog%2Fentry%2Findex&view=table';
        const form = '<form id="entry-form" data-sociolog-return-url="' + url.replace(/&/g, '&amp;') + '"><button id="save">Save</button></form>';
        for (const [name, late, reset] of [['native-success-table', false, false], ['late-loaded-success', true, false], ['synchronous-hide-reset', false, true]]) {
            const page = await setup(form, late);
            await page.evaluate(reset => {window.resetOnHide = reset;}, reset);
            await page.evaluate(() => window.submitNative());
            await page.waitForURL(url);
            assert.deepEqual(page.errors, []); results.push(name); await page.close();
        }
        {
            const cardsUrl = 'http://sociolog.test/subfolder/sociolog/entry/index?view=cards';
            const page = await setup(form.replace(url.replace(/&/g, '&amp;'), cardsUrl));
            await page.evaluate(() => window.submitNative()); await page.waitForURL(cardsUrl);
            assert.deepEqual(page.errors, []); results.push('pretty-url-cards'); await page.close();
        }
        for (const [name, options] of [['validation-error', {success: false}], ['missing-success', {}], ['transport-error', null]]) {
            const page = await setup(form); const original = page.url();
            await page.evaluate(options => {window.response = options; window.rejectTransport = options === null;}, options);
            await page.evaluate(() => window.submitNative()); await page.waitForTimeout(40);
            assert.equal(page.url(), original); assert.deepEqual(page.errors, []);
            results.push(name); await page.close();
        }
        {
            const page = await setup(form); const original = page.url();
            const result = await page.evaluate(() => {
                const $ = jQuery;
                for (let i = 0; i < 3; i++) {modules['sociolog.form'].init(); modules['ui.additions'].applyTo($('body'));}
                $('#foreign').on('submit.test', event => {event.preventDefault(); window.foreignSubmit = true;});
                $('#foreign').trigger('submit');
                $('#testModal').html('<form id="entry-form"><button id="save">Other module</button></form>');
                modules['ui.additions'].applyTo($('#testModal'));
                $('#testModal').trigger('submitted', {success: true});
                return {foreignSubmit: window.foreignSubmit, foreignInit: $('#foreign').attr('data-ui-init'),
                    submittedHandlers: $._data(document, 'events').submitted.filter(item => item.namespace === 'sociologForm').length};
            });
            assert.equal(result.foreignSubmit, true); assert.equal(result.foreignInit, undefined);
            assert.equal(result.submittedHandlers, 1); await page.waitForTimeout(40); assert.equal(page.url(), original);
            assert.deepEqual(page.errors, []); results.push('pjax-idempotence-and-foreign-form-isolation'); await page.close();
        }
        {
            const page = await setup(form); const original = page.url();
            await page.evaluate(() => {
                jQuery('#testModal').trigger('hidden.bs.modal');
                jQuery('#testModal').empty().trigger('show.bs.modal');
                jQuery('#testModal').trigger('submitted', {success: true});
            });
            await page.waitForTimeout(40); assert.equal(page.url(), original);
            assert.deepEqual(page.errors, []); results.push('cancel-then-unrelated-modal'); await page.close();
        }
        {
            const page = await setup(form); const original = page.url();
            await page.addScriptTag({path: path.join(core, 'protected/vendor/yiisoft/yii2/assets/yii.js')});
            await page.addScriptTag({path: path.join(core, 'protected/vendor/yiisoft/yii2/assets/yii.activeForm.js')});
            await page.evaluate(() => {
                jQuery('#entry-form').html('<div class="field-entry-title"><input id="entry-title" name="Entry[title]"><div class="help-block"></div></div>');
                jQuery('#entry-form').yiiActiveForm([{id: 'entry-title', name: 'title', input: '#entry-title', container: '.field-entry-title', error: '.help-block', validationDelay: 0,
                    validate: function (attribute, value, messages) {if (!value) messages.push('Required title');}}], {validationDelay: 0});
                jQuery('#entry-form').on('beforeSubmit.test', function () {window.validSubmit = true; return false;});
                jQuery('#entry-form').trigger('submit');
            });
            await page.waitForFunction(() => document.querySelector('.help-block').textContent === 'Required title');
            assert.deepEqual(page.errors, []);
            assert.equal(await page.locator('.help-block').textContent(), 'Required title');
            assert.equal(await page.evaluate(() => !!window.validSubmit), false);
            await page.evaluate(() => {jQuery('#entry-title').val('Valid synthetic title'); jQuery('#entry-form').trigger('submit');});
            await page.waitForFunction(() => window.validSubmit === true); assert.equal(await page.evaluate(() => !!window.validSubmit), true);
            assert.equal(page.url(), original); assert.deepEqual(page.errors, []);
            results.push('native-activeform-validation-and-normal-submit'); await page.close();
        }
        if (process.env.SOCIOLOG_BROWSER_FORM_SCRIPT) {
            const inline = fs.readFileSync(process.env.SOCIOLOG_BROWSER_FORM_SCRIPT, 'utf8');
            const page = await setup(form);
            await page.evaluate(url => {
                const body = '<button type="button" id="add-protocol">Add</button><div id="protocol-list"></div><div id="protocol-status"></div>';
                jQuery('#entry-form').html(body);
                jQuery('body').append('<form id="other-entry" data-sociolog-return-url="' + url + '">' + body + '</form>');
                jQuery('#foreign').append('<button type="button" class="remove-protocol">Foreign remove</button>');
            }, url);
            // Yii renders POS_READY scripts inside a ready closure, not in global scope.
            await page.addScriptTag({content: '(function () {' + inline + '})();'});
            await page.addScriptTag({content: '(function () {' + inline + '})();'});
            await page.locator('#entry-form #add-protocol').click();
            assert.equal(await page.locator('#entry-form .protocol-row').count(), 1);
            assert.equal(await page.locator('#other-entry .protocol-row').count(), 0);
            await page.locator('#other-entry #add-protocol').click();
            assert.equal(await page.locator('#other-entry .protocol-row').count(), 1);
            await page.locator('#entry-form .remove-protocol').click();
            assert.equal(await page.locator('#entry-form .protocol-row').count(), 0);
            assert.equal(await page.locator('#other-entry .protocol-row').count(), 1);
            await page.locator('#foreign .remove-protocol').click();
            assert.equal(await page.locator('#other-entry .protocol-row').count(), 1);
            assert.deepEqual(page.errors, []); results.push('protocol-pjax-idempotence-and-form-isolation'); await page.close();
        }
        console.log(JSON.stringify({core, browser: await browser.version(), cases: results, passed: results.length}, null, 2));
    } finally {await browser.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
