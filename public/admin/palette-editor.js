// Блок "Палитра" на странице редактирования поста (resources/views/user/posts/edit.blade.php, #palette):
// ручная правка палитры обложки — убрать цвет, пометить/снять "фон", сделать цвет главным (первым),
// добавить цвет пипеткой прямо с картинки. Каждое действие сразу сохраняется (PostController::updatePalette:
// сервер пересчитывает доли цветов по картинке) — отдельной кнопки "Сохранить" нет, как и у картинок поста.
// "Пересчитать автоматически" — вернуть палитру, посчитанную сервером (resetPalette).
//
// initPaletteEditor(root, onChange) — редактор в блоке root (.js-palette-editor с data-атрибутами, разметка —
// parts/palette_editor.blade.php); onChange(palette) — после каждого сохранения. Блок на странице редактирования
// запускается сам (в конце файла), окно палитры на странице группы — из post-group.js.
function initPaletteEditor(root, onChange) {

    var list = root.querySelector('.js-palette-list');
    var emptyNote = root.querySelector('.js-palette-empty');
    var actions = root.querySelector('.js-palette-actions');
    var editedBadge = root.querySelector('.js-palette-edited');
    var resetBtn = root.querySelector('.js-palette-reset');
    var pickToggle = root.querySelector('.js-palette-pick-toggle');
    var picker = root.querySelector('.js-palette-picker');
    var canvas = root.querySelector('.js-palette-canvas');
    var loupe = root.querySelector('.js-palette-loupe');

    var state = {
        palette: [],
        edited: root.getAttribute('data-edited') === '1',
        coverUrl: root.getAttribute('data-cover-url') || null,
        busy: false
    };
    try {
        state.palette = JSON.parse(root.getAttribute('data-palette') || '[]') || [];
    } catch (e) {
        state.palette = [];
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function notify(type, message) {
        if (window.toastr) {
            toastr[type](message);
        }
    }

    function request(method, url, body) {
        var options = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        };
        if (body !== undefined) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok || !data.status) {
                    throw new Error(data.message || 'Не удалось сохранить палитру');
                }
                return data;
            });
        });
    }

    function setBusy(busy) {
        state.busy = busy;
        root.classList.toggle('is-busy', busy);
    }

    function applyServerState(data) {
        state.palette = data.palette || [];
        state.edited = !!data.edited;
        if (data.hasOwnProperty('cover_url')) {
            var coverChanged = data.cover_url !== state.coverUrl;
            state.coverUrl = data.cover_url;
            if (coverChanged && !picker.hidden) {
                loadCanvas();
            }
        }
        render();
        if (onChange) {
            onChange(state.palette);
        }
    }

    // ===== Отрисовка =====
    function render() {
        var hasCover = !!state.coverUrl;
        emptyNote.hidden = hasCover;
        actions.hidden = !hasCover;
        if (!hasCover) {
            picker.hidden = true;
        }
        editedBadge.hidden = !state.edited;
        resetBtn.hidden = !state.edited;

        list.innerHTML = '';
        var mainIndex = state.palette.findIndex(function (color) {
            return !color.background;
        });

        state.palette.forEach(function (color, index) {
            var item = document.createElement('li');
            item.className = 'b-palette-editor__item' + (color.background ? ' is-background' : '');

            var swatch = document.createElement('span');
            swatch.className = 'b-palette-editor__swatch';
            swatch.style.background = color.hex;

            var hex = document.createElement('span');
            hex.className = 'b-palette-editor__hex';
            hex.textContent = color.hex;

            var share = document.createElement('span');
            share.className = 'b-palette-editor__share';
            share.textContent = Math.round((color.share || 0) * 100) + '%';
            share.title = 'Какую часть картинки занимает цвет';

            item.appendChild(swatch);
            item.appendChild(hex);
            item.appendChild(share);

            if (index === mainIndex) {
                var mainLabel = document.createElement('span');
                mainLabel.className = 'b-palette-editor__main-label';
                mainLabel.textContent = 'главный';
                item.appendChild(mainLabel);
            } else {
                item.appendChild(button('js-palette-make-main', 'far fa-star', 'Сделать главным', index));
            }

            var bgBtn = button('js-palette-toggle-bg', 'fas fa-layer-group', color.background ? 'Это фон — нажмите, чтобы снять' : 'Пометить как фон', index, 'фон');
            bgBtn.setAttribute('aria-pressed', color.background ? 'true' : 'false');
            bgBtn.classList.toggle('is-active', !!color.background);
            item.appendChild(bgBtn);

            var removeBtn = button('js-palette-remove', 'fas fa-times', 'Убрать цвет из палитры', index);
            removeBtn.disabled = state.palette.length <= 1;
            item.appendChild(removeBtn);

            list.appendChild(item);
        });
    }

    function button(jsClass, icon, title, index, text) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'b-palette-editor__btn ' + jsClass;
        btn.title = title;
        btn.setAttribute('aria-label', title);
        btn.setAttribute('data-index', index);
        var i = document.createElement('i');
        i.className = icon;
        i.setAttribute('aria-hidden', 'true');
        btn.appendChild(i);
        if (text) {
            btn.appendChild(document.createTextNode(' ' + text));
        }
        return btn;
    }

    // ===== Сохранение =====
    function save(colors, successMessage) {
        if (state.busy) {
            return;
        }
        setBusy(true);
        request('POST', root.getAttribute('data-update-action'), {
            colors: colors.map(function (color) {
                return {hex: color.hex, background: !!color.background};
            })
        })
            .then(function (data) {
                applyServerState(data);
                notify('success', successMessage || 'Палитра сохранена');
            })
            .catch(function (error) {
                notify('error', error.message);
            })
            .then(function () {
                setBusy(false);
            });
    }

    list.addEventListener('click', function (event) {
        var btn = event.target.closest('button[data-index]');
        if (!btn || btn.disabled || state.busy) {
            return;
        }
        var index = parseInt(btn.getAttribute('data-index'), 10);
        var colors = state.palette.map(function (color) {
            return {hex: color.hex, background: !!color.background};
        });

        if (btn.classList.contains('js-palette-make-main')) {
            // главный — первый и точно не фон
            var main = colors.splice(index, 1)[0];
            main.background = false;
            colors.unshift(main);
            save(colors, 'Главный цвет — ' + main.hex);
        } else if (btn.classList.contains('js-palette-toggle-bg')) {
            colors[index].background = !colors[index].background;
            save(colors, colors[index].background ? colors[index].hex + ' — фон' : colors[index].hex + ' — уже не фон');
        } else if (btn.classList.contains('js-palette-remove')) {
            var removed = colors.splice(index, 1)[0];
            save(colors, 'Цвет ' + removed.hex + ' убран');
        }
    });

    resetBtn.addEventListener('click', function () {
        if (state.busy) {
            return;
        }
        setBusy(true);
        request('POST', root.getAttribute('data-reset-action'), {})
            .then(function (data) {
                applyServerState(data);
                notify('success', 'Палитра пересчитана автоматически');
            })
            .catch(function (error) {
                notify('error', error.message);
            })
            .then(function () {
                setBusy(false);
            });
    });

    // ===== Пипетка =====
    var canvasContext = canvas.getContext ? canvas.getContext('2d', {willReadFrequently: true}) : null;
    var canvasReady = false;

    function loadCanvas() {
        canvasReady = false;
        if (!state.coverUrl || !canvasContext) {
            return;
        }
        var image = new Image();
        image.onload = function () {
            // по ширине блока, но не больше 420px и не больше самой картинки
            var maxWidth = Math.min(420, root.clientWidth || 420, image.naturalWidth);
            var scale = maxWidth / image.naturalWidth;
            canvas.width = Math.round(image.naturalWidth * scale);
            canvas.height = Math.round(image.naturalHeight * scale);
            canvasContext.drawImage(image, 0, 0, canvas.width, canvas.height);
            canvasReady = true;
        };
        image.onerror = function () {
            notify('error', 'Не удалось загрузить картинку для пипетки');
        };
        image.src = state.coverUrl;
    }

    // средний цвет квадрата 5×5 вокруг точки — один пиксель бывает шумом/краем буквы
    function colorAt(event) {
        var rect = canvas.getBoundingClientRect();
        var x = Math.floor((event.clientX - rect.left) * canvas.width / rect.width);
        var y = Math.floor((event.clientY - rect.top) * canvas.height / rect.height);
        var size = 5;
        var left = Math.max(0, Math.min(canvas.width - size, x - 2));
        var top = Math.max(0, Math.min(canvas.height - size, y - 2));
        var data = canvasContext.getImageData(left, top, size, size).data;
        var sum = [0, 0, 0];
        var count = 0;
        for (var i = 0; i < data.length; i += 4) {
            if (data[i + 3] < 128) {
                continue; // прозрачное
            }
            sum[0] += data[i];
            sum[1] += data[i + 1];
            sum[2] += data[i + 2];
            count++;
        }
        if (!count) {
            return null;
        }
        return '#' + sum.map(function (channel) {
            return ('0' + Math.round(channel / count).toString(16)).slice(-2);
        }).join('').toUpperCase();
    }

    pickToggle.addEventListener('click', function () {
        picker.hidden = !picker.hidden;
        pickToggle.classList.toggle('active', !picker.hidden);
        if (!picker.hidden && !canvasReady) {
            loadCanvas();
        }
    });

    canvas.addEventListener('mousemove', function (event) {
        if (!canvasReady) {
            return;
        }
        var hex = colorAt(event);
        if (!hex) {
            loupe.hidden = true;
            return;
        }
        var rect = canvas.getBoundingClientRect();
        loupe.hidden = false;
        loupe.style.background = hex;
        // подпись — тёмная на светлом цвете, светлая на тёмном
        var r = parseInt(hex.substr(1, 2), 16), g = parseInt(hex.substr(3, 2), 16), b = parseInt(hex.substr(5, 2), 16);
        loupe.style.color = (r * 0.299 + g * 0.587 + b * 0.114) > 150 ? '#1f2430' : '#fff';
        loupe.style.left = (event.clientX - rect.left + 14) + 'px';
        loupe.style.top = (event.clientY - rect.top + 14) + 'px';
        loupe.textContent = hex;
    });

    canvas.addEventListener('mouseleave', function () {
        loupe.hidden = true;
    });

    canvas.addEventListener('click', function (event) {
        if (!canvasReady || state.busy) {
            return;
        }
        var hex = colorAt(event);
        if (!hex) {
            return;
        }
        if (state.palette.some(function (color) { return color.hex.toUpperCase() === hex; })) {
            notify('info', 'Цвет ' + hex + ' уже есть в палитре');
            return;
        }
        if (state.palette.length >= 8) {
            notify('warning', 'В палитре уже 8 цветов — уберите лишний');
            return;
        }
        // новый цвет — перед фоном (фон всегда в конце)
        var colors = state.palette.map(function (color) {
            return {hex: color.hex, background: !!color.background};
        });
        var firstBackground = colors.findIndex(function (color) { return color.background; });
        colors.splice(firstBackground === -1 ? colors.length : firstBackground, 0, {hex: hex, background: false});
        save(colors, 'Цвет ' + hex + ' добавлен');
    });

    // ===== Картинки поменяли в панели "Изображения поста" =====
    // обложка могла смениться — сервер тогда уже пересчитал палитру; перечитываем.
    // data-version меняется, когда файл картинки перезаписан на месте (обрезка, поворот — admin.js refreshItemImage)
    var imagesList = document.querySelector('#post-images .js-post-images__list');
    if (imagesList && window.MutationObserver) {
        var refreshTimer = null;
        new MutationObserver(function () {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(function () {
                request('GET', root.getAttribute('data-show-action'))
                    .then(applyServerState)
                    .catch(function () {
                        // не критично: палитра обновится при перезагрузке страницы
                    });
            }, 500);
        }).observe(imagesList, {childList: true, subtree: true, attributes: true, attributeFilter: ['data-name', 'data-path', 'data-version']});
    }

    render();
}

(function () {
    var root = document.querySelector('.js-palette-editor');
    if (root) {
        initPaletteEditor(root);
    }
})();
