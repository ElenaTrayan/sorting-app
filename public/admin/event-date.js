// Поле "Дата" пина (resources/views/user/posts/parts/event_date_field.blade.php) — на создании, редактировании
// и странице группы. Рядом с полем показывает, как понята дата ("→ 12 марта 2024"); разбор тот же, что на сервере
// (Post::parseEventDate()), сервер всё равно проверяет сам.
// Автоподстановка (window.EventDateField.suggest): дату предлагают загрузка фото (EXIF, дата файла — admin.js)
// и разбор ссылки. Введённую вручную дату не трогаем; из нескольких подсказок побеждает более надёжный источник
// (SOURCE_PRIORITY), при равных — первая
(function () {
    var MONTH_STEMS = ['янв', 'фев', 'мар', 'апр', 'ма', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    var MONTHS_NOMINATIVE = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
    var MONTHS_GENITIVE = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    // откуда подставлена дата: подпись под полем и надёжность (дата файла у скачанной картинки — день скачивания)
    var SOURCES = {
        exif: {priority: 3, text: 'Подставлено из фото — дата съёмки (EXIF)'},
        link: {priority: 2, text: 'Подставлено по ссылке — дата публикации'},
        file: {priority: 1, text: 'Подставлено по дате файла — у скачанной картинки это может быть день скачивания, проверьте'},
    };

    function isValid(year, month, day) {
        var date = new Date(year, month - 1, day);
        return year >= 1000 && year <= 2999 && date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
    }

    function make(year, month, day, precision) {
        return isValid(year, month, day) ? {year: year, month: month, day: day, precision: precision} : null;
    }

    function monthByWord(word) {
        for (var i = 0; i < MONTH_STEMS.length; i++) {
            if (word.indexOf(MONTH_STEMS[i]) === 0) {
                return i + 1;
            }
        }
        return null;
    }

    // тот же разбор, что Post::parseEventDate()
    function parse(input) {
        var text = String(input || '').toLowerCase().replace(/\s+/g, ' ').trim().replace(/\s*(г\.?|года?)$/, '').trim();
        var m;
        if (!text) {
            return null;
        }
        if ((m = text.match(/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/))) {
            return make(+m[3], +m[2], +m[1], 'day');
        }
        if ((m = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) {
            return make(+m[1], +m[2], +m[3], 'day');
        }
        if ((m = text.match(/^(\d{1,2})[.\/-](\d{4})$/))) {
            return make(+m[2], +m[1], 1, 'month');
        }
        if ((m = text.match(/^(\d{4})-(\d{1,2})$/))) {
            return make(+m[1], +m[2], 1, 'month');
        }
        if ((m = text.match(/^(\d{4})$/))) {
            return make(+m[1], 1, 1, 'year');
        }
        if ((m = text.match(/^(\d{1,2}) ([a-zа-яё]+) (\d{4})$/)) && monthByWord(m[2])) {
            return make(+m[3], monthByWord(m[2]), +m[1], 'day');
        }
        if ((m = text.match(/^([a-zа-яё]+) (\d{4})$/)) && monthByWord(m[1])) {
            return make(+m[2], monthByWord(m[1]), 1, 'month');
        }
        return null;
    }

    function label(date) {
        if (date.precision === 'year') {
            return String(date.year);
        }
        if (date.precision === 'month') {
            return MONTHS_NOMINATIVE[date.month - 1] + ' ' + date.year;
        }
        return date.day + ' ' + MONTHS_GENITIVE[date.month - 1] + ' ' + date.year;
    }

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function fieldOf(input) {
        return input.closest('.js-event-date-field');
    }

    function refreshLabel(input) {
        var field = fieldOf(input);
        var labelEl = field.querySelector('.js-event-date-label');
        var value = input.value.trim();
        var date = parse(value);

        labelEl.textContent = !value ? '' : (date ? '→ ' + label(date) : 'не похоже на дату');
        labelEl.classList.toggle('is-error', !!value && !date);
    }

    function setSource(input, source) {
        var sourceEl = fieldOf(input).querySelector('.js-event-date-source');
        sourceEl.hidden = !source;
        sourceEl.textContent = source ? SOURCES[source].text : '';
        sourceEl.classList.toggle('is-warning', source === 'file');
        input.dataset.source = source || '';
    }

    // дату ввели руками — дальше автоподстановка её не трогает; стёрли — снова можно подставлять
    document.addEventListener('input', function (e) {
        var input = e.target.closest && e.target.closest('.js-event-date');
        if (!input) {
            return;
        }
        input.dataset.manual = input.value.trim() ? '1' : '';
        setSource(input, null);
        refreshLabel(input);
    });

    function noneCheckbox() {
        return document.querySelector('.js-event-date-none');
    }

    function isNone() {
        var checkbox = noneCheckbox();
        return !!checkbox && checkbox.checked;
    }

    // "Без даты" (форма создания): поле пустое и выключено, подсказки не подставляются. Что было в поле —
    // запоминаем и возвращаем, если галочку снимут
    function applyNone(input) {
        var none = isNone();
        if (none && !input.disabled) {
            input.dataset.savedValue = input.value;
            input.dataset.savedSource = input.dataset.source || '';
            input.dataset.savedManual = input.dataset.manual || '';
            input.value = '';
            setSource(input, null);
        } else if (!none && input.disabled) {
            input.value = input.dataset.savedValue || '';
            input.dataset.manual = input.dataset.savedManual || '';
            setSource(input, input.dataset.savedSource || null);
        }
        input.disabled = none;
        fieldOf(input).classList.toggle('is-none', none);
        refreshLabel(input);
    }

    document.addEventListener('change', function (e) {
        if (!e.target.classList || !e.target.classList.contains('js-event-date-none')) {
            return;
        }
        var input = fieldOf(e.target).querySelector('.js-event-date');
        if (input) {
            applyNone(input);
        }
    });

    // форму создания очистили после сохранения поста — поле снова пустое и готово к подстановке
    document.addEventListener('reset', function (e) {
        var input = e.target.querySelector && e.target.querySelector('.js-event-date');
        if (!input) {
            return;
        }
        setTimeout(function () {
            input.disabled = false;
            fieldOf(input).classList.remove('is-none');
            input.dataset.manual = '';
            input.dataset.savedValue = '';
            input.dataset.savedSource = '';
            input.dataset.savedManual = '';
            setSource(input, null);
            refreshLabel(input);
        }, 0);
    }, true);

    document.querySelectorAll('.js-event-date').forEach(refreshLabel);

    window.EventDateField = {
        parse: parse,

        // дату ввели руками (а не подставили из фото / ссылки)
        isManual: function () {
            var input = document.querySelector('.js-event-date');
            return !!input && input.dataset.manual === '1';
        },

        // отмечено "Без даты" — посты создаются без даты
        isNone: isNone,

        /**
         * Предложить дату пина. Ставится, только если поле не заполняли руками и в нём нет даты из более
         * надёжного источника
         *
         * @param {string|Date} value 'YYYY-MM-DD' или Date
         * @param {string} source 'exif' | 'link' | 'file'
         */
        suggest: function (value, source) {
            var input = document.querySelector('.js-event-date');
            if (!input || !SOURCES[source]) {
                return;
            }

            var date = value instanceof Date ? value : null;
            var m = !date && String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                date = new Date(+m[1], +m[2] - 1, +m[3]);
            }
            if (!date || isNaN(date.getTime())) {
                return;
            }
            var formatted = pad(date.getDate()) + '.' + pad(date.getMonth() + 1) + '.' + date.getFullYear();

            // "Без даты": подсказку не ставим, а запоминаем — появится в поле, если галочку снимут
            var none = isNone();
            var currentValue = none ? input.dataset.savedValue : input.value.trim();
            var manual = none ? input.dataset.savedManual : input.dataset.manual;
            var current = SOURCES[none ? input.dataset.savedSource : input.dataset.source];
            if (manual === '1' || (currentValue &&(!current || current.priority >= SOURCES[source].priority))) {
                return;
            }

            if (none) {
                input.dataset.savedValue = formatted;
                input.dataset.savedSource = source;
                return;
            }
            input.value = formatted;
            setSource(input, source);
            refreshLabel(input);
        },
    };
})();
