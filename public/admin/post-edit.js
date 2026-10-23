// Страница "Редактировать пост" (/cabinet/posts/{id}/edit): переключатель типа поста.
// Тип уходит с формой скрытым post_type (auto | film | recipe) — см. PostController::update().
// Поля фильма видны только у типа "Фильм / Сериал"; сама отправка формы — в admin.js (#submit-edit-form)

// Обложка в правой колонке — по пропорциям самой картинки (пределы высоты — в post-edit.css).
// После обрезки/поворота admin.js (refreshItemImage) меняет её фон — подгоняем пропорции заново
(function () {
    var cover = document.querySelector('.b-post-edit__cover');
    if (!cover) {
        return;
    }
    var fitted = null;

    function fit() {
        var background = cover.style.backgroundImage || '';
        var match = /url\(["']?(.*?)["']?\)/.exec(background);
        if (!match || background === fitted) {
            return;
        }
        fitted = background;
        var img = new Image();
        img.onload = function () {
            if (img.naturalWidth && img.naturalHeight && cover.style.backgroundImage === background) {
                cover.style.aspectRatio = img.naturalWidth + ' / ' + img.naturalHeight;
            }
        };
        img.src = match[1];
    }

    fit();
    if (window.MutationObserver) {
        new MutationObserver(fit).observe(cover, {attributes: true, attributeFilter: ['style']});
    }
})();

(function () {
    var typeInput = document.getElementById('post-type');
    if (!typeInput) {
        return;
    }

    var typeButtons = document.querySelectorAll('.js-post-types .b-pin-type');
    var typeLabel = document.querySelector('.js-post-type-label');
    var autoDesc = document.querySelector('.js-post-types [data-type="auto"] .b-pin-type__desc');

    // доп. поля типа: видны только у своего типа; уйти с типа — при сохранении сервер удалит эти данные
    var typeSections = {
        film: {
            section: document.querySelector('.js-post-film-section'),
            confirm: 'Данные фильма (жанры, рейтинг, описание и т.д.) будут удалены при сохранении поста. Продолжить?',
        },
        clothes: {
            section: document.querySelector('.js-post-clothes-section'),
            confirm: 'Данные вещи (бренд, тип, цвет, материал и т.д.) будут удалены при сохранении поста. Продолжить?',
        },
    };

    function selectType(type) {
        typeInput.value = type;
        typeButtons.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-type') === type);
        });
        Object.keys(typeSections).forEach(function (key) {
            if (typeSections[key].section) {
                typeSections[key].section.hidden = type !== key;
            }
        });
    }

    typeButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.getAttribute('data-type');
            if (type === typeInput.value) {
                return;
            }
            // спрашиваем, только когда уходим с исходного типа (дальше по другим типам — уже без вопроса)
            var initial = typeSections[typeInput.getAttribute('data-initial')];
            if (initial && typeInput.value === typeInput.getAttribute('data-initial') && !confirm(initial.confirm)) {
                return;
            }
            selectType(type);
        });
    });

    // пост сохранён — обновляем тип в сайдбаре и подпись "сейчас «…»" у типа по содержимому
    document.addEventListener('post:updated', function (event) {
        var result = event.detail || {};
        if (!result.type) {
            return;
        }
        if (typeLabel && result.type_label) {
            typeLabel.textContent = result.type_label;
        }
        // у выбранных вручную (фильм, рецепт, одежда) тип по содержимому не считается
        if (autoDesc && ['film', 'recipe', 'clothes'].indexOf(result.type) === -1) {
            autoDesc.textContent = 'По содержимому — сейчас «' + result.type_label + '»';
        }
        typeInput.setAttribute('data-initial', typeInput.value);
    });
})();
