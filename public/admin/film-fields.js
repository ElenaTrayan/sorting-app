// Поля фильма/сериала (resources/views/user/posts/parts/film_fields.blade.php) — страницы
// создания и редактирования поста: чипы "Вайб"/"Достоинства"/"Недостатки", поле "Сезонов"
// и хештеги (выбранный чип и данные из полей сразу попадают в выбранные хештеги формы).
// Выбранные чипы хранятся JSON-массивом в скрытом инпуте film-vibes/film-pros/film-cons — он уходит
// на сервер вместе с формой (serializeArray в admin.js) и сохраняется в post_films.
(function () {
    document.querySelectorAll('.js-film-fields').forEach(function (root) {

        // ===== "Сезонов" — только для сериалов =====
        // #film-kind заполняет и парсер (admin.js) — он шлёт нативный change, чтобы сработало и тут
        var kindSelect = root.querySelector('#film-kind');
        var seasonsField = root.querySelector('.js-film-seasons-field');

        function updateSeasons() {
            if (kindSelect && seasonsField) {
                seasonsField.style.display = /сериал/i.test(kindSelect.value) ? '' : 'none';
            }
        }

        if (kindSelect) {
            kindSelect.addEventListener('change', updateSeasons);
        }
        updateSeasons();

        // ===== Чипы =====
        // значение — data-value (у вайбов в тексте чипа ещё эмодзи, в данные она попасть не должна)
        function chipValue(chip) {
            return (chip.getAttribute('data-value') || chip.textContent).trim();
        }

        function syncChipsValue(chips) {
            var valueInput = chips.parentNode.querySelector('.js-film-chips-value');
            if (!valueInput) {
                return;
            }
            var selected = Array.prototype.map.call(chips.querySelectorAll('.b-pin-chip.is-active'), chipValue);
            valueInput.value = JSON.stringify(selected);
        }

        function setChipActive(chip, active) {
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
            syncChipsValue(chip.closest('.js-film-chips'));
        }

        // ===== Вайб -> хештег поста =====
        // Выбранные хештеги формы — #b-selected-tags-2 + localStorage "hashtags2" ({id: title}), их ведёт
        // admin.js (там же они уходят на сервер при сохранении). Разметку тега и хранилище повторяем
        // здесь — функции admin.js спрятаны в его замыкании.
        var SELECTED_TAGS_SELECTOR = '#b-selected-tags-2';
        var TAGS_STORAGE_KEY = 'hashtags2';

        function normalizeTitle(title) {
            return String(title).trim().toLowerCase().replace(/ё/g, 'е');
        }

        function readStoredTags() {
            try {
                var stored = JSON.parse(localStorage.getItem(TAGS_STORAGE_KEY));
                return stored && typeof stored === 'object' ? stored : {};
            } catch (e) {
                return {};
            }
        }

        function writeStoredTags(tags) {
            try {
                localStorage.setItem(TAGS_STORAGE_KEY, JSON.stringify(tags));
            } catch (e) {
                // без localStorage хештег не уйдёт с формой — admin.js берёт выбранные именно оттуда
            }
        }

        function findSelectedTag(predicate) {
            var container = document.querySelector(SELECTED_TAGS_SELECTOR);
            if (!container) {
                return null;
            }
            return Array.prototype.find.call(container.querySelectorAll('.tag'), predicate) || null;
        }

        function findSelectedTagByTitle(title) {
            var key = normalizeTitle(title);
            return findSelectedTag(function (tag) {
                return normalizeTitle(tag.getAttribute('data-name') || '') === key;
            });
        }

        function addSelectedTag(id, title) {
            var container = document.querySelector(SELECTED_TAGS_SELECTOR);
            if (!container) {
                return;
            }
            if (!findSelectedTag(function (tag) { return tag.getAttribute('data-id') === String(id); })) {
                // та же разметка, что у addHashtagToSelectedHashtags() в admin.js (крестик — его обработчик)
                var tag = document.createElement('span');
                tag.className = 'tag';
                tag.setAttribute('data-id', id);
                tag.setAttribute('data-name', title);
                tag.textContent = '#' + title;
                var close = document.createElement('span');
                close.className = 'icon font-icon fas close';
                tag.appendChild(close);
                container.appendChild(tag);
            }
            var stored = readStoredTags();
            stored[id] = title;
            writeStoredTags(stored);
        }

        function removeSelectedTag(tag) {
            var stored = readStoredTags();
            delete stored[tag.getAttribute('data-id')];
            writeStoredTags(stored);
            tag.remove();
        }

        function showError(message) {
            if (window.toastr) {
                toastr.error(message);
            }
        }

        function postForm(url, body) {
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            });
        }

        // выбрали вайб — хештег (существующий или новый) сразу в выбранные; сняли — убираем его,
        // но только если это мы его добавили: выбранный вручную до этого хештег не трогаем
        function syncVibeHashtag(chip, active) {
            var chips = chip.closest('.js-film-chips');
            var action = chips ? chips.getAttribute('data-hashtag-action') : null;
            if (!action) {
                return;
            }
            var title = chipValue(chip);

            if (!active) {
                if (chip.getAttribute('data-hashtag-preexisting') !== '1') {
                    var ownId = chip.getAttribute('data-hashtag-id');
                    // без id — вайб был выбран ещё при загрузке (страница редактирования): ищем его хештег по названию
                    var ownTag = ownId
                        ? findSelectedTag(function (tag) { return tag.getAttribute('data-id') === ownId; })
                        : findSelectedTagByTitle(title);
                    if (ownTag) {
                        removeSelectedTag(ownTag);
                    }
                }
                chip.removeAttribute('data-hashtag-id');
                chip.removeAttribute('data-hashtag-preexisting');
                return;
            }

            if (findSelectedTagByTitle(title)) {
                chip.setAttribute('data-hashtag-preexisting', '1');
                return;
            }

            var body = new FormData();
            body.append('title', title);

            postForm(action, body)
                .then(function (data) {
                    if (!data || !data.info) {
                        throw new Error('empty response');
                    }
                    // пока ждали ответ, вайб успели снять — хештег уже не нужен
                    if (!chip.classList.contains('is-active')) {
                        return;
                    }
                    // тот же хештег мог уже быть выбран под немного другим названием (ё/е, регистр)
                    if (findSelectedTag(function (tag) { return tag.getAttribute('data-id') === String(data.info.id); })) {
                        chip.setAttribute('data-hashtag-preexisting', '1');
                        return;
                    }
                    addSelectedTag(data.info.id, data.info.title);
                    chip.setAttribute('data-hashtag-id', String(data.info.id));
                })
                .catch(function () {
                    showError('Не удалось добавить хештег «' + title + '»');
                });
        }

        // ===== Поля фильма -> хештеги =====
        // После парсинга (admin.js шлёт событие film-fields:parsed) и при правке этих полей сервер
        // (HashtagController::forFilm) по ним подбирает хештеги — тип, статус, жанры, год, страны,
        // режиссёры, главные актёры, — и они сразу попадают в выбранные. Поменяли поле — хештеги,
        // добавленные отсюда раньше и больше не подходящие, убираются. Хештег, который пользователь
        // убрал крестиком, заново не добавляем; выбранные им самим — не трогаем.
        var FILM_TAG_FIELDS = ['film-kind', 'film-status', 'film-genres', 'film-year', 'film-country', 'film-director', 'film-actors'];
        var fieldsHashtagsAction = root.getAttribute('data-hashtags-action');
        var fieldTags = {};      // id -> название: добавленные отсюда
        var dismissedTags = {};  // id: убранные пользователем
        var fieldsRequestNo = 0; // ответы на устаревшие запросы (поля успели поменять ещё раз) пропускаем
        var fieldsTimer = null;

        function isTagSelected(id) {
            return !!findSelectedTag(function (tag) { return tag.getAttribute('data-id') === String(id); });
        }

        function syncFieldHashtags() {
            clearTimeout(fieldsTimer);
            if (!fieldsHashtagsAction) {
                return;
            }

            var body = new FormData();
            FILM_TAG_FIELDS.forEach(function (name) {
                var field = root.querySelector('[name="' + name + '"]');
                body.append(name, field ? field.value : '');
            });

            var requestNo = ++fieldsRequestNo;
            postForm(fieldsHashtagsAction, body)
                .then(function (data) {
                    if (requestNo !== fieldsRequestNo || !data || !Array.isArray(data.hashtags)) {
                        return;
                    }

                    var current = {};
                    data.hashtags.forEach(function (hashtag) {
                        current[String(hashtag.id)] = hashtag.title;
                    });

                    Object.keys(fieldTags).forEach(function (id) {
                        var tag = findSelectedTag(function (el) { return el.getAttribute('data-id') === id; });
                        if (!tag) {
                            // пропал из выбранных — значит, убрали крестиком
                            dismissedTags[id] = true;
                        } else if (!current.hasOwnProperty(id)) {
                            removeSelectedTag(tag);
                        } else {
                            return;
                        }
                        delete fieldTags[id];
                    });

                    Object.keys(current).forEach(function (id) {
                        if (fieldTags[id] || dismissedTags[id] || isTagSelected(id)) {
                            return;
                        }
                        addSelectedTag(id, current[id]);
                        fieldTags[id] = current[id];
                    });
                })
                .catch(function () {
                    showError('Не удалось добавить хештеги из полей фильма');
                });
        }

        root.addEventListener('change', function (event) {
            if (FILM_TAG_FIELDS.indexOf(event.target.name) !== -1) {
                clearTimeout(fieldsTimer);
                fieldsTimer = setTimeout(syncFieldHashtags, 400);
            }
        });

        document.addEventListener('film-fields:parsed', syncFieldHashtags);

        function addCustomChip(field) {
            var input = field.querySelector('.js-film-chip-input');
            var chips = field.querySelector('.js-film-chips');
            var text = input ? input.value.trim() : '';
            if (!text || !chips) {
                return;
            }

            // такой вариант уже есть (в любой группе) — просто отмечаем его, дубль не создаём
            var existing = Array.prototype.find.call(chips.querySelectorAll('.b-pin-chip'), function (chip) {
                return chipValue(chip).toLowerCase() === text.toLowerCase();
            });

            if (existing) {
                if (!existing.classList.contains('is-active')) {
                    setChipActive(existing, true);
                    syncVibeHashtag(existing, true);
                }
            } else {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'b-pin-chip';
                chip.setAttribute('data-value', text);
                chip.textContent = text;
                // вайбы — в группу "Свои" (показываем её, если была пустой), достоинства/недостатки — в общий ряд
                var customRow = chips.querySelector('.js-film-chips-custom') || chips;
                var customGroup = chips.querySelector('.js-film-chips-custom-group');
                if (customGroup) {
                    customGroup.style.display = '';
                }
                customRow.appendChild(chip);
                setChipActive(chip, true);
                syncVibeHashtag(chip, true);
            }

            input.value = '';
            input.focus();
        }

        root.addEventListener('click', function (event) {
            var chip = event.target.closest('.js-film-chips .b-pin-chip');
            if (chip) {
                var active = !chip.classList.contains('is-active');
                setChipActive(chip, active);
                syncVibeHashtag(chip, active);
                return;
            }

            var addBtn = event.target.closest('.js-film-chip-add');
            if (addBtn) {
                addCustomChip(addBtn.closest('.b-pin-field'));
            }
        });

        // Enter в поле "своё достоинство" — добавить чип, а не отправить всю форму
        root.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target.classList.contains('js-film-chip-input')) {
                event.preventDefault();
                addCustomChip(event.target.closest('.b-pin-field'));
            }
        });

        // после успешного создания поста admin.js сбрасывает форму (creationForm.trigger('reset')) —
        // обычные инпуты очищает браузер, а чипы и "Сезонов" — здесь
        var form = root.closest('form');
        if (form) {
            form.addEventListener('reset', function () {
                // выбранные хештеги admin.js очищает сам (resetHashtagsFromForm) — тут только чипы
                root.querySelectorAll('.js-film-chips .b-pin-chip.is-active').forEach(function (chip) {
                    setChipActive(chip, false);
                    chip.removeAttribute('data-hashtag-id');
                    chip.removeAttribute('data-hashtag-preexisting');
                });
                clearTimeout(fieldsTimer);
                fieldsRequestNo++;
                fieldTags = {};
                dismissedTags = {};
                // reset срабатывает до того, как браузер вернёт значения полей
                setTimeout(updateSeasons, 0);
            });
        }
    });
})();
