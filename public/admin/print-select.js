/*
 * Список принтов одежды с образцами и поиском (wardrobe/parts/print_select.blade.php) — поле "Принт"
 * в форме вещи "Моего гардероба" и в полях пина "Одежда". Подключается самим partial'ом.
 * Варианты отрисованы сервером; здесь — открытие, поиск, выбор и "Свой принт…".
 * Снаружи: window.printSelect.set(root, id) — выставить значение (wardrobe.js при открытии формы вещи).
 */
(function () {
    'use strict';

    const instances = new WeakMap();

    function init(root) {
        const valueInput = root.querySelector('.js-print-select-value');
        const toggle = root.querySelector('.js-print-select-toggle');
        const dropdown = root.querySelector('.js-print-select-dropdown');
        const search = root.querySelector('.js-print-select-search');
        const list = root.querySelector('.js-print-select-list');
        const empty = root.querySelector('.js-print-select-empty');
        const newInput = root.querySelector('.js-print-select-new');
        const label = root.querySelector('.js-print-select-label');
        const swatch = root.querySelector('.js-print-select-swatch');
        const options = Array.prototype.slice.call(root.querySelectorAll('.js-print-select-option'));
        let imagesLoaded = false;

        // только у списка цветов: палитра "оттенок своего цвета"
        const newColor = root.querySelector('.js-print-select-new-color');
        const newColorInput = newColor ? newColor.querySelector('input') : null;

        // образец: картинка (принты, материалы, логотипы) или сам цвет (data-style у цветов)
        function applySwatch(el, option) {
            if (!el) {
                return; // список без образцов (размеры)
            }
            const image = option.getAttribute('data-image');
            el.className = 'b-print-swatch b-print-swatch--' + option.getAttribute('data-swatch') + (image ? ' has-image' : '');
            el.style.cssText = option.getAttribute('data-style') || '';
            el.style.backgroundImage = image ? 'url("' + image + '")' : el.style.backgroundImage;
            if (newColorInput && option.getAttribute('data-id') === 'new' && el === swatch) {
                el.style.background = newColorInput.value;
            }
        }

        // образцы-картинки подставляем при первом открытии, а не при загрузке страницы
        function loadImages() {
            if (imagesLoaded) {
                return;
            }
            imagesLoaded = true;
            options.forEach(function (option) {
                if (option.hasAttribute('data-image')) {
                    applySwatch(option.querySelector('.b-print-swatch'), option);
                }
            });
        }

        function filter() {
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            options.forEach(function (option) {
                const id = option.getAttribute('data-id');
                if (id === '' || id === 'new') {
                    return; // "Не указан" и "Свой принт…" видны всегда
                }
                const matches = !query || option.getAttribute('data-search').indexOf(query) !== -1;
                option.hidden = !matches;
                if (matches) { visible++; }
            });
            root.querySelectorAll('.js-print-select-group').forEach(function (group) {
                group.hidden = !group.querySelector('.js-print-select-option:not([hidden])');
            });
            empty.hidden = visible > 0;
        }

        function open() {
            loadImages();
            root.classList.add('is-open');
            dropdown.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            search.value = '';
            filter();
            const selected = list.querySelector('.is-selected');
            if (selected) {
                list.scrollTop = Math.max(0, selected.offsetTop - list.offsetTop - 60);
            }
            search.focus();
        }

        function close() {
            root.classList.remove('is-open');
            dropdown.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        // id: '' — не указан, 'new' — свой принт, иначе id из справочника (неизвестный id — как "не указан")
        function set(id) {
            id = id === null || id === undefined ? '' : String(id);
            let option = options.filter(function (candidate) {
                return candidate.getAttribute('data-id') === id;
            })[0];
            let custom = '';
            if (!option && id !== '' && root.hasAttribute('data-free')) {
                // значение-текст, которого нет в списке (размер, вписанный вручную) — это "Свой…" с этим текстом
                custom = id;
                id = 'new';
                option = options.filter(function (candidate) {
                    return candidate.getAttribute('data-id') === 'new';
                })[0];
            }
            if (!option) {
                id = '';
                option = options[0];
            }

            valueInput.value = id;
            options.forEach(function (candidate) {
                candidate.classList.toggle('is-selected', candidate === option);
            });
            label.textContent = option.getAttribute('data-name');
            applySwatch(swatch, option);
            root.classList.toggle('is-empty', id === '');

            newInput.hidden = id !== 'new';
            if (id !== 'new') {
                newInput.value = '';
            } else if (custom) {
                newInput.value = custom;
            }
            if (newColor) {
                newColor.hidden = id !== 'new';
                newColorInput.disabled = id !== 'new';
            }
        }

        if (newColorInput) {
            newColorInput.addEventListener('input', function () {
                if (valueInput.value === 'new') {
                    swatch.style.background = newColorInput.value;
                }
            });
        }

        toggle.addEventListener('click', function () {
            if (dropdown.hidden) { open(); } else { close(); }
        });
        search.addEventListener('input', filter);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // не отправлять форму, в которой стоит список
                const first = options.filter(function (option) {
                    const id = option.getAttribute('data-id');
                    return id !== '' && id !== 'new' && !option.hidden;
                })[0];
                if (first && search.value.trim()) {
                    first.click();
                }
            }
        });
        list.addEventListener('click', function (e) {
            const option = e.target.closest('.js-print-select-option');
            if (!option) {
                return;
            }
            const id = option.getAttribute('data-id');
            const typed = search.value.trim();
            set(id);
            close();
            if (id === 'new') {
                // искали принт и не нашли — то, что набрали, скорее всего и есть название своего
                if (typed && !newInput.value) { newInput.value = typed; }
                newInput.focus();
            } else {
                toggle.focus();
            }
        });
        newInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); }
        });
        // Esc закрывает только список, а не модалку, в которой он стоит
        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !dropdown.hidden) {
                e.stopPropagation();
                close();
                toggle.focus();
            }
        });
        document.addEventListener('click', function (e) {
            if (!dropdown.hidden && !root.contains(e.target)) {
                close();
            }
        });

        set(valueInput.value);
        instances.set(root, {set: set});
    }

    document.querySelectorAll('.js-print-select').forEach(init);

    window.printSelect = {
        set: function (root, id) {
            const instance = root && instances.get(root);
            if (instance) {
                instance.set(id);
            }
        },
    };
})();
