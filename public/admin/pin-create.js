// Страница "Добавить пин" (/cabinet/posts/create) — по макету Claude Design
// (артефакт 2fgoNE9x9nHsAR4vY3siPk). Здесь только то, что реально новое в этой
// вёрстке: карточки выбора типа (замена dropdown-меню из admin.js), сворачивание
// секции парсинга, кнопка "Выбрать файл" в дропзоне, живой предпросмотр и
// чек-лист готовности. Вся остальная функциональность (загрузка, парсинг,
// хештеги, OCR, видео, отправка формы) — по-прежнему в admin.js, эти id/классы
// не менялись.
(function () {
    var root = document.querySelector('.b-pin-create');
    if (!root) {
        return;
    }

    var TYPE_LABELS = {
        text_picture: 'Текст + Изображение',
        picture: 'Изображение',
        text: 'Текст',
        film: 'Фильм / Сериал',
        recipe: 'Рецепт',
        clothes: 'Одежда',
        news: 'Новость'
    };

    // ===== Тип пина: карточки вместо старого dropdown-меню =====
    // Тип уходит с формой скрытым pin_type: фильм/рецепт/одежду сервер сохраняет в posts.type как есть,
    // остальные типы определяет по содержимому (Post::booted); для "film" ещё и поля фильма (post_films).
    // Здесь он переключает видимость доп. полей (сейчас это только блок фильма, #tab-film) и подписывает предпросмотр.
    var typeButtons = root.querySelectorAll('.js-pin-types .b-pin-type');
    var tabBlocks = root.querySelectorAll('.b-parsing-type-blocks > div');
    var previewTypeEl = root.querySelector('.js-pin-preview-type');
    var pinTypeInput = document.getElementById('pin-type');

    function selectType(type) {
        // уходит с формой: для "film" PostController сохраняет поля фильма в post_films
        if (pinTypeInput) {
            pinTypeInput.value = type;
        }
        typeButtons.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-type') === type);
        });
        tabBlocks.forEach(function (block) {
            block.style.display = (block.id === 'tab-' + type) ? '' : 'none';
        });
        if (previewTypeEl) {
            previewTypeEl.textContent = TYPE_LABELS[type] || '';
        }
    }

    typeButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectType(btn.getAttribute('data-type'));
        });
    });

    var initialTypeBtn = root.querySelector('.js-pin-types .b-pin-type.is-active') || typeButtons[0];
    if (initialTypeBtn) {
        selectType(initialTypeBtn.getAttribute('data-type'));
    }

    // ===== "Спарсить информацию" — сворачиваемая секция =====
    var parseSection = root.querySelector('.js-pin-parse');
    var parseToggle = root.querySelector('.js-pin-parse-toggle');
    if (parseSection && parseToggle) {
        parseToggle.addEventListener('click', function (event) {
            event.preventDefault();
            parseSection.classList.toggle('is-open');
        });
    }

    // ===== Дропзона: кнопка "Выбрать файл" =====
    // сам upload() по выбору файла — в admin.js (uploadImageSection.on('change', ...)),
    // здесь только открываем системный проводник по клику на кнопку
    root.addEventListener('click', function (event) {
        var btn = event.target.closest('.js-pin-choose-file');
        if (!btn) {
            return;
        }
        var section = btn.closest('.js-upload-image-section');
        var input = section ? section.querySelector('input[type="file"]') : null;
        if (input) {
            input.click();
        }
    });

    // ===== Живой предпросмотр + чек-лист готовности + шаги =====
    var titleInput = document.getElementById('title');
    var imagesContainer = root.querySelector('.js-images');
    var tagsContainer = document.getElementById('b-selected-tags-2');

    var previewImageEl = root.querySelector('.js-pin-preview-image');
    var previewTitleEl = root.querySelector('.js-pin-preview-title');
    var previewTextEl = root.querySelector('.js-pin-preview-text');
    var previewTagsEl = root.querySelector('.js-pin-preview-tags');
    var previewEmptyEl = root.querySelector('.js-pin-preview-empty');

    var checkContentEl = root.querySelector('.js-check-content');
    var checkTitleEl = root.querySelector('.js-check-title');
    var stepEls = root.querySelectorAll('.js-pin-steps .b-pin-create__step');

    function currentBodyText() {
        if (window.tinymce) {
            var editor = tinymce.get('editor');
            if (editor) {
                return editor.getContent({format: 'text'}).trim();
            }
        }
        var textarea = document.getElementById('editor');
        return textarea ? textarea.value.trim() : '';
    }

    function setCheck(el, done, doneText, pendingText) {
        if (!el) {
            return;
        }
        el.classList.toggle('is-done', done);
        var dot = el.querySelector('.b-pin-checklist__dot');
        if (dot) {
            dot.textContent = done ? '✓' : '·';
        }
        // структура пункта: [.b-pin-checklist__dot][текстовый узел] — текст всегда последний узел
        el.lastChild.textContent = done ? doneText : pendingText;
    }

    function updateSteps(hasContent, hasTitle) {
        stepEls.forEach(function (stepEl) {
            var n = stepEl.getAttribute('data-step');
            var done = n === '1' || (n === '2' && hasContent) || (n === '3' && hasTitle);
            stepEl.classList.toggle('is-done', done);
        });
    }

    function updatePreview() {
        var title = titleInput ? titleInput.value.trim() : '';
        var bodyText = currentBodyText();
        var firstImage = imagesContainer ? imagesContainer.querySelector('.image') : null;
        var hasImage = !!firstImage;
        var tagEls = tagsContainer ? tagsContainer.querySelectorAll('.tag') : [];

        if (previewTitleEl) {
            previewTitleEl.style.display = title ? '' : 'none';
            previewTitleEl.textContent = title;
        }
        if (previewTextEl) {
            previewTextEl.style.display = bodyText ? '' : 'none';
            previewTextEl.textContent = bodyText;
        }
        if (previewImageEl) {
            var swatch = firstImage ? firstImage.querySelector('span') : null;
            if (hasImage && swatch) {
                previewImageEl.style.display = '';
                previewImageEl.style.backgroundImage = swatch.style.backgroundImage || '';
            } else {
                previewImageEl.style.display = 'none';
                previewImageEl.style.backgroundImage = '';
            }
        }
        if (previewTagsEl) {
            previewTagsEl.innerHTML = '';
            tagEls.forEach(function (tagEl) {
                var name = tagEl.getAttribute('data-name');
                if (!name) {
                    return;
                }
                var span = document.createElement('span');
                span.className = 'b-pin-preview__tag';
                span.textContent = '#' + name;
                previewTagsEl.appendChild(span);
            });
        }

        var anything = !!(title || bodyText || hasImage || tagEls.length);
        if (previewEmptyEl) {
            previewEmptyEl.style.display = anything ? 'none' : '';
        }

        var hasContent = hasImage || !!bodyText;
        setCheck(checkContentEl, hasContent, 'Контент добавлен', 'Контент не добавлен');
        setCheck(checkTitleEl, !!title, 'Название заполнено', 'Название пустое');
        updateSteps(hasContent, !!title);
    }

    if (titleInput) {
        titleInput.addEventListener('input', updatePreview);
    }
    if (imagesContainer && window.MutationObserver) {
        new MutationObserver(updatePreview).observe(imagesContainer, {childList: true});
    }
    if (tagsContainer && window.MutationObserver) {
        new MutationObserver(updatePreview).observe(tagsContainer, {childList: true});
    }
    if (window.tinymce) {
        tinymce.on('AddEditor', function (e) {
            if (e.editor.id === 'editor') {
                e.editor.on('input change keyup undo redo', updatePreview);
            }
        });
    }

    updatePreview();
})();
