/**
 * A La Carte — shared admin behaviour
 *
 * Replaces the inline `onchange` / `onclick` attributes the tool pages used to
 * carry. Templates opt in through data attributes:
 *
 *   .alacarte-js-autosubmit              submit the form when a control changes
 *   [data-alacarte-reveal="<id>"]        show that element when this radio is checked
 *   [data-alacarte-confirm="message"]    confirm before submitting a form
 */
(function () {
    'use strict';

    /**
     * Submit a form as soon as one of its controls changes.
     */
    function initAutoSubmit() {
        document.addEventListener('change', function (event) {
            var form = event.target.closest('.alacarte-js-autosubmit');

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
        var triggers = document.querySelectorAll('[data-alacarte-reveal]');

        if (!triggers.length) {
            return;
        }

        function syncAll() {
            triggers.forEach(function (trigger) {
                var target = document.getElementById(trigger.getAttribute('data-alacarte-reveal'));

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
            var message = event.target.getAttribute('data-alacarte-confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    function init() {
        initAutoSubmit();
        initReveal();
        initConfirm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
