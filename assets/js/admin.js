/**
 * Seitenmeister — shared admin behaviour
 *
 * Replaces the inline `onchange` / `onclick` attributes the tool pages used to
 * carry. Templates opt in through data attributes:
 *
 *   .almazbro-site-utilities-js-autosubmit              submit the form when a control changes
 *   [data-almazbro-site-utilities-reveal="<id>"]        show that element when this radio is checked
 *   [data-almazbro-site-utilities-confirm="message"]    confirm before submitting a form
 */
(function () {
    'use strict';

    /**
     * Submit a form as soon as one of its controls changes.
     */
    function initAutoSubmit() {
        document.addEventListener('change', function (event) {
            var form = event.target.closest('.almazbro-site-utilities-js-autosubmit');

            if (form) {
                form.submit();
            }
        });
    }

    /**
     * Show or hide the element a radio group points at, so dependent settings
     * only appear for the option they belong to.
     */
    function initReveal() {
        var triggers = document.querySelectorAll('[data-almazbro-site-utilities-reveal]');

        if (!triggers.length) {
            return;
        }

        function syncAll() {
            triggers.forEach(function (trigger) {
                var target = document.getElementById(trigger.getAttribute('data-almazbro-site-utilities-reveal'));

                if (target) {
                    target.hidden = !trigger.checked;
                }
            });
        }

        syncAll();
        document.addEventListener('change', syncAll);
    }

    /**
     * Confirm destructive submissions.
     */
    function initConfirm() {
        document.addEventListener('submit', function (event) {
            var message = event.target.getAttribute('data-almazbro-site-utilities-confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    function initRevisionCleanup() {
        var form = document.querySelector('[data-revision-cleanup]');
        if (!form || !window.fetch || !window.FormData) {
            return;
        }

        // The shared confirmation listener runs first. Without JS the same
        // endpoint redirects to a page with a button for the next batch.
        document.addEventListener('submit', async function (event) {
            if (event.target !== form || event.defaultPrevented) {
                return;
            }
            event.preventDefault();
            var button = form.querySelector('button[type="submit"]');
            if (button.disabled) {
                return;
            }
            button.disabled = true;
            var status = document.getElementById('almazbro-revision-progress');
            var data = new FormData(form);
            data.set('batch_json', '1');
            try {
                while (true) {
                    var response = await fetch(form.getAttribute('action'), { method: 'POST', body: data });
                    var result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error();
                    }
                    var batch = result.data;
                    status.textContent = batch.message;
                    if (!batch.more) {
                        window.location.assign(batch.url);
                        return;
                    }
                    data.set('after', batch.after);
                    data.set('cleaned', batch.cleaned);
                    data.set('skipped', batch.skipped);
                }
            } catch (error) {
                status.textContent = form.getAttribute('data-error');
                button.disabled = false;
            }
        });
    }

    function init() {
        initAutoSubmit();
        initReveal();
        initConfirm();
        initRevisionCleanup();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
