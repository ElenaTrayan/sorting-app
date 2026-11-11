/*
 * Горячие клавиши кабинета (подключается в layouts/user_layout.blade.php после admin.js).
 *
 *   Ctrl+S, Ctrl+Enter — "Сохранить": в открытом поп-апе — его кнопка сохранения, иначе — кнопка страницы
 *                        с data-hotkey="save" (форма поста, группа, хештег...), иначе — кнопка отправки формы,
 *                        в которой стоит курсор. Работает и из поля текста (TinyMCE)
 *   Alt+N              — новый пост, Alt+Shift+N — новый хештег (адреса — window.cabinetHotkeys в layout)
 *   ?                  — окно со списком клавиш
 *
 * На Mac вместо Ctrl — Cmd. Вставка картинки из буфера (Ctrl+V) на форме поста — в admin.js, рядом с
 * перетаскиванием файлов на страницу (page-files-drop).
 */
(function () {
    'use strict';

    const config = window.cabinetHotkeys || {};

    function isVisible(el) {
        return !!el && !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }

    function isUsable(button) {
        return isVisible(button) && !button.disabled && !button.classList.contains('disabled') && !button.classList.contains('is-loading');
    }

    // верхний открытый поп-ап — у вложенных (обрезка фото поверх формы контакта) z-index больше
    function topModal() {
        const modals = Array.prototype.filter.call(document.querySelectorAll('.modal.show'), isVisible);
        modals.sort(function (a, b) {
            return (parseInt(getComputedStyle(a).zIndex, 10) || 0) - (parseInt(getComputedStyle(b).zIndex, 10) || 0);
        });
        return modals.length ? modals[modals.length - 1] : null;
    }

    function firstUsable(root, selector) {
        return Array.prototype.find.call(root.querySelectorAll(selector), isUsable) || null;
    }

    // кнопка "Сохранить" для текущего состояния страницы; activeEl — где стоит курсор
    function findSaveButton(activeEl) {
        const modal = topModal();
        if (modal) {
            return firstUsable(modal, '[data-hotkey="save"]')
                || firstUsable(modal, '.modal-footer .btn-primary, .modal-footer [class*="--save"], .modal-footer [class*="--primary"], .modal-footer button[type="submit"]');
        }
        const pageButton = Array.prototype.find.call(document.querySelectorAll('[data-hotkey="save"]'), function (button) {
            return isUsable(button) && !button.closest('.modal');
        });
        if (pageButton) {
            return pageButton;
        }
        const form = activeEl && activeEl.closest ? activeEl.closest('form') : null;
        return form ? firstUsable(form, 'button[type="submit"], input[type="submit"]') : null;
    }

    function isSaveCombo(event) {
        const mod = event.ctrlKey || event.metaKey;
        // event.code — физическая клавиша: Ctrl+S работает и в русской раскладке (там key === 'ы')
        return mod && !event.altKey && (event.code === 'KeyS' || event.key === 'Enter');
    }

    // общий обработчик: и для страницы, и для iframe редактора текста
    function onKeyDown(event, activeEl) {
        if (isSaveCombo(event)) {
            const button = findSaveButton(activeEl);
            if (event.code === 'KeyS' || button) {
                // Ctrl+S гасим всегда — иначе браузер предложит сохранить саму страницу
                event.preventDefault();
            }
            if (button && !event.repeat) {
                button.click();
            }
            return;
        }

        if (event.altKey && !event.ctrlKey && !event.metaKey && event.code === 'KeyN') {
            const url = event.shiftKey ? config.newHashtag : config.newPost;
            if (url) {
                event.preventDefault();
                window.location.href = url;
            }
            return;
        }

        // "?" — только вне полей ввода, иначе это просто вопросительный знак в тексте
        if (event.key === '?' && !event.ctrlKey && !event.metaKey && !event.altKey && !isTyping(activeEl)) {
            event.preventDefault();
            showHelp();
        }
    }

    function isTyping(el) {
        if (!el) {
            return false;
        }
        const tag = el.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    document.addEventListener('keydown', function (event) {
        onKeyDown(event, document.activeElement);
    });

    // TinyMCE — отдельный iframe, его клавиши до страницы не доходят
    if (window.tinymce) {
        const bindEditor = function (editor) {
            editor.on('keydown', function (event) {
                onKeyDown(event, editor.getElement());
            });
        };
        try {
            (tinymce.get() || []).forEach(bindEditor);
            tinymce.on('AddEditor', function (e) {
                bindEditor(e.editor);
            });
        } catch (err) {
            // без этого клавиши не работают только внутри поля текста
        }
    }

    // подсказка на кнопках, которые нажимает Ctrl+S
    function markSaveButtons() {
        document.querySelectorAll('[data-hotkey="save"]').forEach(function (button) {
            if (!button.getAttribute('title')) {
                button.setAttribute('title', 'Ctrl+S');
            }
        });
    }
    markSaveButtons();

    let helpModal = null;
    function showHelp() {
        if (!helpModal) {
            const rows = [
                ['Ctrl + S', 'Сохранить (форма или открытое окно)'],
                ['Ctrl + Enter', 'То же, удобно из поля текста'],
                ['Ctrl + V', 'Вставить картинку из буфера в пост (на создании и редактировании поста)'],
                ['Alt + N', 'Новый пост'],
                ['Alt + Shift + N', 'Новый хештег'],
                ['Esc', 'Закрыть окно'],
                ['?', 'Эта подсказка'],
            ];
            helpModal = document.createElement('div');
            helpModal.className = 'modal fade';
            helpModal.id = 'modal-hotkeys-help';
            helpModal.tabIndex = -1;
            helpModal.setAttribute('role', 'dialog');
            helpModal.innerHTML =
                '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">' +
                    '<div class="modal-header"><h5 class="modal-title">Горячие клавиши</h5>' +
                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
                    '<div class="modal-body"><table class="table table-sm mb-0 b-hotkeys-help"><tbody></tbody></table>' +
                    '<div class="text-muted small mt-2">На Mac вместо Ctrl — Cmd.</div></div>' +
                '</div></div>';
            const tbody = helpModal.querySelector('tbody');
            rows.forEach(function (row) {
                const tr = document.createElement('tr');
                const keys = document.createElement('td');
                row[0].split(' + ').forEach(function (key, index) {
                    if (index) {
                        keys.appendChild(document.createTextNode(' + '));
                    }
                    const kbd = document.createElement('kbd');
                    kbd.textContent = key;
                    keys.appendChild(kbd);
                });
                const text = document.createElement('td');
                text.textContent = row[1];
                tr.appendChild(keys);
                tr.appendChild(text);
                tbody.appendChild(tr);
            });
            document.body.appendChild(helpModal);
        }
        if (window.jQuery && jQuery.fn.modal) {
            jQuery(helpModal).modal('show');
        }
    }
})();
