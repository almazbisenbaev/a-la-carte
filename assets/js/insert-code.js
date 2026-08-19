/**
 * PowerTools — Insert Code editor
 *
 * Two things live here: the CodeMirror instance for the snippet body, and the
 * conditional logic repeater. Everything generic (modal, confirmations,
 * self-submitting forms) lives in powertools-admin.js.
 */
(function () {
    'use strict';

    var RULE_VALUE_FOR_TYPE = {
        user_role: 'user_role',
        post_type: 'post_type'
    };

    /**
     * Start CodeMirror on the snippet textarea and keep the tag hints above
     * and below it in sync with the snippet type.
     */
    function initCodeEditor() {
        var container = document.querySelector('.pt-code-editor');
        var textarea = document.getElementById('pt-code-editor');

        if (!container || !textarea) {
            return;
        }

        var type = container.getAttribute('data-pt-code-type');
        var wrappers = {
            js: { top: '<script type="text/javascript">', bottom: '</' + 'script>', mode: 'javascript' },
            php: { top: '<' + '?php', bottom: '?' + '>', mode: 'application/x-httpd-php' }
        };
        var wrapper = wrappers[type];

        ['top', 'bottom'].forEach(function (position) {
            var tag = container.querySelector('[data-pt-code-tag="' + position + '"]');

            if (!tag) {
                return;
            }

            if (wrapper) {
                tag.textContent = wrapper[position];
                tag.classList.add('is-visible');
            } else {
                tag.classList.remove('is-visible');
            }
        });

        if (!window.wp || !window.wp.codeEditor || !window.powertoolsCodeEditorSettings) {
            return;
        }

        var settings = window.powertoolsCodeEditorSettings;
        settings.codemirror = Object.assign({}, settings.codemirror, {
            lineNumbers: true,
            indentUnit: 4,
            tabSize: 4,
            mode: wrapper ? wrapper.mode : 'text/html'
        });

        var editor = window.wp.codeEditor.initialize(textarea, settings);

        // Mirror changes back into the textarea so a normal form submit posts
        // the current contents.
        editor.codemirror.on('change', function (cm) {
            cm.save();
        });
    }

    /**
     * Show the value control that belongs to the row's selected rule type and
     * disable the others so they are not submitted.
     */
    function syncRuleRow(row) {
        var type = row.querySelector('[data-pt-rule-type]').value;
        var wanted = RULE_VALUE_FOR_TYPE[type] || 'text';

        row.querySelectorAll('[data-pt-rule-value]').forEach(function (field) {
            var isWanted = field.getAttribute('data-pt-rule-value') === wanted;

            field.hidden = !isWanted;
            field.disabled = !isWanted;
        });
    }

    /**
     * Conditional logic repeater: add, remove and keep the field names indexed.
     */
    function initRules() {
        var container = document.getElementById('pt-rules');

        if (!container) {
            return;
        }

        function reindex() {
            container.querySelectorAll('.pt-rule').forEach(function (row, index) {
                row.querySelectorAll('[name^="rules["]').forEach(function (field) {
                    field.name = field.name.replace(/^rules\[\d+\]/, 'rules[' + index + ']');
                });
            });
        }

        document.addEventListener('change', function (event) {
            if (event.target.matches('[data-pt-rule-type]')) {
                syncRuleRow(event.target.closest('.pt-rule'));
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-pt-rule-add]')) {
                var template = container.querySelector('.pt-rule');
                var row = template.cloneNode(true);

                row.querySelectorAll('input, select').forEach(function (field) {
                    if (field.tagName === 'SELECT') {
                        field.selectedIndex = 0;
                    } else {
                        field.value = '';
                    }
                });

                container.appendChild(row);
                syncRuleRow(row);
                reindex();
                return;
            }

            var remove = event.target.closest('[data-pt-rule-remove]');
            if (!remove) {
                return;
            }

            var row = remove.closest('.pt-rule');

            // Keep one row around so there is always something to clone.
            if (container.querySelectorAll('.pt-rule').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (field) {
                    field.value = '';
                });
                row.querySelectorAll('select').forEach(function (field) {
                    field.selectedIndex = 0;
                });
                syncRuleRow(row);
            }

            reindex();
        });
    }

    function init() {
        initCodeEditor();
        initRules();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
