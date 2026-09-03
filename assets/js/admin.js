/**
 * PowerTools — shared admin behaviour
 *
 * Replaces the inline `onchange` / `onclick` attributes the tool pages used to
 * carry. Templates opt in through data attributes:
 *
 *   .ptools-js-autosubmit              submit the form when a control changes
 *   [data-ptools-reveal="<id>"]        show that element when this radio is checked
 *   [data-ptools-modal-open="<id>"]    open a modal
 *   [data-ptools-modal-close]          close the closest modal
 *   [data-ptools-confirm="message"]    confirm before submitting a form
 */
(function () {
    'use strict';

    /**
     * Submit a form as soon as one of its controls changes.
     */
    function initAutoSubmit() {
        document.addEventListener('change', function (event) {
            var form = event.target.closest('.ptools-js-autosubmit');

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
        var triggers = document.querySelectorAll('[data-ptools-reveal]');

        if (!triggers.length) {
            return;
        }

        function syncAll() {
            triggers.forEach(function (trigger) {
                var target = document.getElementById(trigger.getAttribute('data-ptools-reveal'));

                if (target) {
                    target.hidden = !trigger.checked;
                }
            });
        }

        syncAll();
        document.addEventListener('change', syncAll);
    }

    /**
     * Modals: open, close on button / backdrop / Escape, and move focus into
     * the dialog so keyboard users land in the right place.
     */
    function initModals() {
        function open(modal) {
            modal.classList.add('is-open');

            var focusable = modal.querySelector('input:not([type="hidden"]):not([disabled]), button, select, textarea, a[href]');
            if (focusable) {
                focusable.focus();
            }
        }

        function close(modal) {
            modal.classList.remove('is-open');
        }

        document.addEventListener('click', function (event) {
            var opener = event.target.closest('[data-ptools-modal-open]');
            if (opener) {
                event.preventDefault();
                var modal = document.getElementById(opener.getAttribute('data-ptools-modal-open'));
                if (modal) {
                    open(modal);
                }
                return;
            }

            var closer = event.target.closest('[data-ptools-modal-close]');
            if (closer) {
                event.preventDefault();
                close(closer.closest('.ptools-modal'));
                return;
            }

            // A click on the backdrop itself (not the dialog) closes the modal.
            if (event.target.classList.contains('ptools-modal')) {
                close(event.target);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.ptools-modal.is-open').forEach(close);
        });
    }

    /**
     * Confirm destructive submissions.
     */
    function initConfirm() {
        document.addEventListener('submit', function (event) {
            var message = event.target.getAttribute('data-ptools-confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    function init() {
        initAutoSubmit();
        initReveal();
        initModals();
        initConfirm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
