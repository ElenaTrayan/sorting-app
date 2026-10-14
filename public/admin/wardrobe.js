/*
 * "Мой гардероб" — JS только для страниц раздела (подключается через @push('footer-scripts')
 * на user/wardrobe/*, после my-stock.js). Общие вещи — AJAX-формы data-stock-ajax-form,
 * удаление с подтверждением, меню "...", drawer фильтров — делает my-stock.js; отсюда
 * используются его window.stockAjax / window.stockError.
 */
(function () {
    'use strict';

    if (!document.querySelector('.b-wardrobe')) {
        return; // не на странице раздела "Мой гардероб" — ничего не делаем
    }

    const stockAjax = window.stockAjax;
    const stockError = window.stockError;

    // ---------- блок одного фото в форме (образ, место хранения): превью, выбор файла, "Убрать фото" ----------
    // В форме: .js-wardrobe-photo (превью), input[type=file][name=image], input[name=remove_image],
    // .js-wardrobe-photo-remove. Возвращает {set(url), clear()} — превью без изменения полей.
    // У вещи фото несколько — там галерея (initItemGallery ниже)
    function initPhotoField(form) {
        const photo = form.querySelector('.js-wardrobe-photo');
        const fileInput = form.querySelector('input[type="file"][name="image"]');
        const removeInput = form.querySelector('input[name="remove_image"]');
        const removeBtn = form.querySelector('.js-wardrobe-photo-remove');
        const postIdInput = form.querySelector('input[name="post_id"]');
        // повернуть/отразить: в форме это только CSS, сам файл сервер меняет при сохранении (image_rotate —
        // поворотов на 90° по часовой, image_flip — отражение по горизонтали, оно применяется первым;
        // WardrobeImage::transform — тот же порядок, что у фото вещи в initItemGallery)
        const tools = form.querySelector('.js-wardrobe-photo-tools');
        const rotateInput = form.querySelector('input[name="image_rotate"]');
        const flipInput = form.querySelector('input[name="image_flip"]');
        let rotate = 0;
        let flip = false;

        // картинка — отдельным слоем, чтобы при повороте рамка превью оставалась на месте;
        // фото сохранённой записи сервер рисует фоном самой рамки — переносим его в слой
        const picture = document.createElement('span');
        picture.className = 'b-wardrobe-photo__img';
        picture.style.backgroundImage = photo.style.backgroundImage;
        photo.style.backgroundImage = '';
        photo.appendChild(picture);

        function applyTransform() {
            picture.classList.toggle('is-sideways', rotate % 2 === 1);
            picture.style.transform = 'translate(-50%, -50%) rotate(' + rotate * 90 + 'deg) scaleX(' + (flip ? -1 : 1) + ')';
            if (rotateInput) { rotateInput.value = rotate; }
            if (flipInput) { flipInput.value = flip ? '1' : '0'; }
        }

        function set(url) {
            picture.style.backgroundImage = url ? 'url("' + url + '")' : '';
            photo.classList.toggle('has-image', !!url);
            removeBtn.style.display = url ? '' : 'none';
            if (tools) { tools.style.display = url ? '' : 'none'; }
            // другая картинка (или её убрали) — поворот прежней к ней не относится
            rotate = 0;
            flip = false;
            applyTransform();
        }

        if (tools) {
            tools.querySelector('.js-wardrobe-photo-rotate').addEventListener('click', function () {
                rotate = (rotate + 1) % 4;
                applyTransform();
            });
            // отражение того, что сейчас видно: у уже повёрнутого фото поворот меняет направление
            tools.querySelector('.js-wardrobe-photo-flip').addEventListener('click', function () {
                rotate = (4 - rotate) % 4;
                flip = !flip;
                applyTransform();
            });
        }
        applyTransform();

        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length) {
                return;
            }
            set(URL.createObjectURL(fileInput.files[0]));
            removeInput.value = '0';
            if (postIdInput) { postIdInput.value = ''; }
        });

        removeBtn.addEventListener('click', function () {
            fileInput.value = '';
            removeInput.value = '1';
            if (postIdInput) { postIdInput.value = ''; }
            set(null);
        });

        return {
            set: set,
            clear: function () {
                fileInput.value = '';
                set(null);
            },
        };
    }

    // ---------- галерея фото в форме вещи (parts/item_form_modal.blade.php) ----------
    // Фото по порядку, первое — обложка. На сервер уходят photos_order (JSON: "id:12" — сохранённое фото,
    // "new:0" — файл photos_new[0], "post:5:1" — картинка поста; см. ItemController::photosPlan) и сами
    // новые файлы в photos_new[] — их сюда кладём через DataTransfer, чтобы общий обработчик формы
    // (my-stock.js, new FormData(form)) отправил их как обычные поля.
    // Возвращает {reset(photos), addPost(image), count()}.
    const MAX_ITEM_PHOTOS = 20;

    function initItemGallery(form) {
        const list = form.querySelector('.js-wardrobe-gallery-list');
        const empty = form.querySelector('.js-wardrobe-gallery-empty');
        const hint = form.querySelector('.js-wardrobe-gallery-hint');
        const orderInput = form.querySelector('.js-wardrobe-photos-order');
        const transformInput = form.querySelector('.js-wardrobe-photos-transform');
        const newInput = form.querySelector('.js-wardrobe-photos-new');
        const pickInput = form.querySelector('.js-wardrobe-photos-pick');

        // {kind: 'existing'|'new'|'post', url, id?, file?, postId?, index?, rotate?, flip?}
        // rotate/flip — фото повернули/отразили кнопками: сначала отражение по горизонтали (flip), потом
        // rotate поворотов на 90° по часовой. В форме это только CSS, сами файлы сервер меняет при
        // сохранении вещи (photos_transform, WardrobeImage::transform)
        let photos = [];
        let dragFrom = null;

        function sync() {
            const transfer = new DataTransfer();
            const tokens = photos.map(function (photo) {
                if (photo.kind === 'existing') {
                    return 'id:' + photo.id;
                }
                if (photo.kind === 'post') {
                    return 'post:' + photo.postId + ':' + photo.index;
                }
                transfer.items.add(photo.file);
                return 'new:' + (transfer.files.length - 1);
            });
            newInput.files = transfer.files;
            orderInput.value = JSON.stringify(tokens);
            transformInput.value = JSON.stringify(photos.map(function (photo) {
                return {r: photo.rotate || 0, f: photo.flip ? 1 : 0};
            }));
        }

        function rotate(index) {
            photos[index].rotate = ((photos[index].rotate || 0) + 1) % 4;
            render();
        }

        // отражение того, что сейчас видно: у уже повёрнутого фото поворот меняет направление
        function flip(index) {
            photos[index].rotate = (4 - (photos[index].rotate || 0)) % 4;
            photos[index].flip = !photos[index].flip;
            render();
        }

        function move(from, to) {
            if (from === to || from < 0 || to < 0 || from >= photos.length || to >= photos.length) {
                return;
            }
            photos.splice(to, 0, photos.splice(from, 1)[0]);
            render();
        }

        function remove(index) {
            const photo = photos.splice(index, 1)[0];
            if (photo && photo.kind === 'new') {
                URL.revokeObjectURL(photo.url);
            }
            render();
        }

        function render() {
            list.innerHTML = '';
            photos.forEach(function (photo, index) {
                const tile = document.createElement('div');
                tile.className = 'b-wardrobe-gallery__item' + (index === 0 ? ' is-cover' : '');
                tile.draggable = true;
                tile.setAttribute('data-index', index);

                // картинка — отдельным слоем, чтобы поворот не крутил кнопки плитки
                const picture = document.createElement('span');
                picture.className = 'b-wardrobe-gallery__img' + ((photo.rotate || 0) % 2 === 1 ? ' is-sideways' : '');
                picture.style.backgroundImage = 'url("' + photo.url + '")';
                picture.style.transform = 'translate(-50%, -50%) rotate(' + (photo.rotate || 0) * 90 + 'deg) scaleX(' + (photo.flip ? -1 : 1) + ')';
                tile.appendChild(picture);

                if (index === 0) {
                    const badge = document.createElement('span');
                    badge.className = 'b-wardrobe-gallery__badge';
                    badge.textContent = 'Обложка';
                    tile.appendChild(badge);
                } else {
                    const coverBtn = document.createElement('button');
                    coverBtn.type = 'button';
                    coverBtn.className = 'b-wardrobe-gallery__btn b-wardrobe-gallery__btn--cover';
                    coverBtn.title = 'Сделать обложкой';
                    coverBtn.innerHTML = '<i class="fas fa-star"></i>';
                    coverBtn.addEventListener('click', function () { move(index, 0); });
                    tile.appendChild(coverBtn);
                }

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'b-wardrobe-gallery__btn b-wardrobe-gallery__btn--remove';
                removeBtn.title = 'Убрать фото';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.addEventListener('click', function () { remove(index); });
                tile.appendChild(removeBtn);

                const flipBtn = document.createElement('button');
                flipBtn.type = 'button';
                flipBtn.className = 'b-wardrobe-gallery__btn b-wardrobe-gallery__btn--flip';
                flipBtn.title = 'Отразить по горизонтали';
                flipBtn.innerHTML = '<i class="fas fa-arrows-alt-h"></i>';
                flipBtn.addEventListener('click', function () { flip(index); });
                tile.appendChild(flipBtn);

                const rotateBtn = document.createElement('button');
                rotateBtn.type = 'button';
                rotateBtn.className = 'b-wardrobe-gallery__btn b-wardrobe-gallery__btn--rotate';
                rotateBtn.title = 'Повернуть вправо';
                rotateBtn.innerHTML = '<i class="fas fa-redo"></i>';
                rotateBtn.addEventListener('click', function () { rotate(index); });
                tile.appendChild(rotateBtn);

                tile.addEventListener('dragstart', function (e) {
                    dragFrom = index;
                    tile.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', String(index)); // Firefox без данных не начинает перетаскивание
                });
                tile.addEventListener('dragend', function () {
                    dragFrom = null;
                    tile.classList.remove('is-dragging');
                });
                tile.addEventListener('dragover', function (e) {
                    if (dragFrom !== null) {
                        e.preventDefault();
                        tile.classList.add('is-drop-target');
                    }
                });
                tile.addEventListener('dragleave', function () {
                    tile.classList.remove('is-drop-target');
                });
                tile.addEventListener('drop', function (e) {
                    e.preventDefault();
                    tile.classList.remove('is-drop-target');
                    if (dragFrom !== null) {
                        move(dragFrom, index);
                    }
                });

                list.appendChild(tile);
            });

            empty.hidden = photos.length > 0;
            hint.hidden = photos.length < 2;
            sync();
        }

        function hasRoom() {
            if (photos.length >= MAX_ITEM_PHOTOS) {
                stockError(new Error('У вещи может быть не больше ' + MAX_ITEM_PHOTOS + ' фото.'));
                return false;
            }
            return true;
        }

        pickInput.addEventListener('change', function () {
            Array.prototype.forEach.call(pickInput.files, function (file) {
                if (hasRoom()) {
                    photos.push({kind: 'new', file: file, url: URL.createObjectURL(file)});
                }
            });
            pickInput.value = '';
            render();
        });

        return {
            // photos — [{id, url}] сохранённой вещи (WardrobeItem::toFormArray), [] — новая вещь
            reset: function (savedPhotos) {
                photos.forEach(function (photo) {
                    if (photo.kind === 'new') { URL.revokeObjectURL(photo.url); }
                });
                photos = (savedPhotos || []).map(function (photo) {
                    return {kind: 'existing', id: photo.id, url: photo.url};
                });
                render();
            },
            // image — {post_id, index, thumb_url}: картинка поста (окно "Выбрать из постов", "В гардероб" у пина)
            addPost: function (image) {
                const duplicate = photos.some(function (photo) {
                    return photo.kind === 'post' && String(photo.postId) === String(image.post_id) && String(photo.index) === String(image.index);
                });
                if (!duplicate && hasRoom()) {
                    photos.push({kind: 'post', postId: image.post_id, index: image.index, url: image.thumb_url});
                    render();
                }
            },
            count: function () {
                return photos.length;
            },
        };
    }

    // ---------- "с чем сочетается" в форме вещи (parts/item_form_modal.blade.php) ----------
    // Три раздела (обувь / аксессуары / остальное) — один список id в compatible_ids: в какой раздел попадает
    // вещь, решает её категория (group в data-items, WardrobeItem::compatGroup). У каждого раздела — выбранные
    // вещи плитками и раскрывающийся список остальных вещей этого раздела с поиском.
    // Возвращает {reset(ids, selfId)} — selfId: сама вещь в списки не попадает.
    function initItemCompat(form) {
        const root = form.querySelector('.js-wardrobe-compat');
        const input = form.querySelector('.js-wardrobe-compat-input');
        const items = JSON.parse(root.getAttribute('data-items') || '[]');
        const groups = Array.prototype.slice.call(root.querySelectorAll('.js-wardrobe-compat-group'));
        let selected = [];
        let selfId = null;

        function photoBox(item) {
            const photo = document.createElement('div');
            photo.className = 'b-wardrobe-selected__photo';
            if (item.image_url) {
                photo.style.backgroundImage = 'url("' + item.image_url + '")';
            } else {
                photo.innerHTML = '<i class="fas fa-tshirt"></i>';
            }
            return photo;
        }

        function nameBox(item) {
            const name = document.createElement('div');
            name.className = 'b-wardrobe-selected__name';
            name.textContent = item.name;
            name.title = item.name;
            return name;
        }

        function render() {
            groups.forEach(function (groupEl) {
                const group = groupEl.getAttribute('data-group');
                const list = groupEl.querySelector('.js-wardrobe-compat-list');
                const options = groupEl.querySelector('.js-wardrobe-compat-options');
                const search = groupEl.querySelector('.js-wardrobe-compat-search').value.trim().toLowerCase();
                const inGroup = items.filter(function (item) {
                    return item.group === group && item.id !== selfId;
                });

                list.innerHTML = '';
                options.innerHTML = '';

                inGroup.forEach(function (item) {
                    if (selected.indexOf(item.id) !== -1) {
                        const chip = document.createElement('div');
                        chip.className = 'b-wardrobe-selected__item';
                        chip.appendChild(photoBox(item));
                        chip.appendChild(nameBox(item));
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'b-wardrobe-selected__remove';
                        remove.title = 'Убрать';
                        remove.innerHTML = '<i class="fas fa-times"></i>';
                        remove.addEventListener('click', function () {
                            selected = selected.filter(function (id) { return id !== item.id; });
                            render();
                        });
                        chip.appendChild(remove);
                        list.appendChild(chip);
                    } else if (!search || item.name.toLowerCase().indexOf(search) !== -1) {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'b-wardrobe-selected__item b-wardrobe-compat__option';
                        option.appendChild(photoBox(item));
                        option.appendChild(nameBox(item));
                        option.addEventListener('click', function () {
                            selected.push(item.id);
                            render();
                        });
                        options.appendChild(option);
                    }
                });

                if (!options.children.length) {
                    const empty = document.createElement('div');
                    empty.className = 'b-wardrobe-selected__empty';
                    empty.textContent = inGroup.length
                        ? (search ? 'Ничего не найдено' : 'Все подходящие вещи уже выбраны')
                        : 'В гардеробе пока нет таких вещей';
                    options.appendChild(empty);
                }
            });

            input.value = JSON.stringify(selected);
        }

        groups.forEach(function (groupEl) {
            const picker = groupEl.querySelector('.js-wardrobe-compat-picker');
            const search = groupEl.querySelector('.js-wardrobe-compat-search');
            groupEl.querySelector('.js-wardrobe-compat-add').addEventListener('click', function () {
                picker.hidden = !picker.hidden;
                if (!picker.hidden) { search.focus(); }
            });
            search.addEventListener('input', render);
            // Enter в поиске не должен отправлять форму вещи
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        });

        return {
            reset: function (ids, itemId) {
                selfId = itemId || null;
                selected = (ids || []).filter(function (id) {
                    return items.some(function (item) { return item.id === id; });
                });
                groups.forEach(function (groupEl) {
                    groupEl.querySelector('.js-wardrobe-compat-picker').hidden = true;
                    groupEl.querySelector('.js-wardrobe-compat-search').value = '';
                });
                render();
            },
        };
    }

    // ---------- окно "Выбрать из постов" (parts/post_picker.blade.php) ----------
    // openPostPicker(onPick, mode): onPick({post_id, index, title, thumb_url}) — выбранная картинка.
    // mode: 'images' (по умолчанию) — все картинки всех постов; 'pins' — пины типа "Одежда", плитка на пин
    // ("Импорт из пина" в форме новой вещи)
    const picker = document.querySelector('.js-wardrobe-post-picker');
    let openPostPicker = function () {};

    if (picker) {
        const grid = picker.querySelector('.js-wardrobe-post-picker-grid');
        const status = picker.querySelector('.js-wardrobe-post-picker-status');
        const moreBtn = picker.querySelector('.js-wardrobe-post-picker-more');
        const searchInput = picker.querySelector('.js-wardrobe-post-picker-search');
        const pickerTitle = picker.querySelector('.js-wardrobe-post-picker-title');
        const pickerTexts = {
            images: {
                title: pickerTitle.textContent,
                placeholder: searchInput.getAttribute('placeholder'),
                empty: 'Картинок не нашлось',
            },
            pins: {
                title: 'Импорт из пина «Одежда»',
                placeholder: 'Название пина или хештег',
                empty: 'Пинов с типом «Одежда» не нашлось',
            },
        };
        let mode = 'images';
        let onPick = null;
        let nextPage = null;
        let requestId = 0; // ответ устаревшего поиска не должен затереть новый
        let searchTimer = null;

        function isOpen() {
            return picker.style.display !== 'none';
        }

        function close() {
            picker.style.display = 'none';
            onPick = null;
        }

        function load(page) {
            const currentRequest = ++requestId;
            if (page === 1) {
                grid.innerHTML = '';
            }
            moreBtn.style.display = 'none';
            status.textContent = 'Загружаем…';

            const url = picker.getAttribute('data-url') +
                '?page=' + page + '&search=' + encodeURIComponent(searchInput.value.trim()) +
                (mode === 'pins' ? '&mode=pins' : '');

            stockAjax(url, 'GET')
                .then(function (json) {
                    if (currentRequest !== requestId) {
                        return;
                    }
                    (json.images || []).forEach(function (image) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'b-wardrobe-picker__image';
                        btn.title = image.title || '';
                        btn.style.backgroundImage = 'url("' + image.thumb_url + '")';
                        if (mode === 'pins') {
                            btn.className += ' b-wardrobe-picker__image--pin';
                            if (image.in_wardrobe) {
                                btn.insertAdjacentHTML('beforeend', '<span class="b-wardrobe-badge b-wardrobe-badge--done"><i class="fas fa-check"></i> В гардеробе</span>');
                            }
                            if (image.title) {
                                const caption = document.createElement('span');
                                caption.className = 'b-wardrobe-picker__caption';
                                caption.textContent = image.title;
                                btn.appendChild(caption);
                            }
                        }
                        btn.addEventListener('click', function () {
                            const callback = onPick;
                            close();
                            if (callback) { callback(image); }
                        });
                        grid.appendChild(btn);
                    });
                    nextPage = json.next_page;
                    moreBtn.style.display = nextPage ? '' : 'none';
                    status.textContent = grid.children.length ? '' : pickerTexts[mode].empty;
                })
                .catch(function (err) {
                    if (currentRequest === requestId) {
                        status.textContent = err.message || 'Не удалось загрузить картинки';
                    }
                });
        }

        openPostPicker = function (callback, pickerMode) {
            pickerMode = pickerMode === 'pins' ? 'pins' : 'images';
            if (pickerMode !== mode) {
                searchInput.value = ''; // поиск по картинкам к пинам не относится, и наоборот
            }
            mode = pickerMode;
            pickerTitle.textContent = pickerTexts[mode].title;
            searchInput.setAttribute('placeholder', pickerTexts[mode].placeholder);

            onPick = callback;
            picker.style.display = '';
            load(1);
            searchInput.focus();
        };

        picker.querySelectorAll('.js-wardrobe-post-picker-close').forEach(function (el) {
            el.addEventListener('click', close);
        });
        moreBtn.addEventListener('click', function () {
            if (nextPage) { load(nextPage); }
        });
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { load(1); }, 350);
        });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimer);
                load(1);
            }
        });
        // Esc закрывает только окно выбора, а не модалку формы под ним
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                e.stopPropagation();
                close();
            }
        }, true);
    }

    // ---------- форма вещи (parts/item_form_modal.blade.php) ----------
    const itemModal = document.getElementById('modal-wardrobe-item');
    if (itemModal) {
        const form = itemModal.querySelector('.js-wardrobe-item-form');
        const title = itemModal.querySelector('.js-wardrobe-item-title');
        const submitBtn = itemModal.querySelector('.js-wardrobe-item-submit');
        const repairCheckbox = form.querySelector('.js-wardrobe-needs-repair');
        const repairNote = form.querySelector('.js-wardrobe-repair-note');
        const gallery = initItemGallery(form);
        const compat = initItemCompat(form);

        const importBtn = form.querySelector('.js-wardrobe-import-from-pin');

        function toggleRepairNote() {
            repairNote.style.display = repairCheckbox.checked ? '' : 'none';
        }
        repairCheckbox.addEventListener('change', toggleRepairNote);

        // "+ Новое место…" в списке мест хранения открывает поле для названия
        const storageSelect = form.querySelector('.js-wardrobe-storage-select');
        const storageNew = form.querySelector('.js-wardrobe-storage-new');
        function toggleStorageNew() {
            const isNew = storageSelect.value === 'new';
            storageNew.style.display = isNew ? '' : 'none';
            if (!isNew) { form.elements['storage_name'].value = ''; }
        }
        storageSelect.addEventListener('change', function () {
            toggleStorageNew();
            if (storageSelect.value === 'new') { form.elements['storage_name'].focus(); }
        });

        // бренд, принт и материал — списки с образцами (parts/directory_select.blade.php, print-select.js)
        function setDirectory(kind, id) {
            const el = form.querySelector('.js-print-select[data-select="' + kind + '"]');
            if (window.printSelect && el) {
                window.printSelect.set(el, id);
            }
        }
        function setBrand(id) {
            setDirectory('brand', id);
        }
        function setColor(id) {
            setDirectory('color', id);
        }
        function setPrint(id) {
            setDirectory('print', id);
        }
        function setMaterial(id) {
            setDirectory('material', id);
        }

        // особенности ухода: счётчик отмеченного у раздела; openChecked — раскрыть разделы с отметками, остальные свернуть
        function refreshCare(openChecked) {
            form.querySelectorAll('.js-wardrobe-care-group').forEach(function (group) {
                const checked = group.querySelectorAll('input:checked').length;
                const count = group.querySelector('.js-wardrobe-care-count');
                count.textContent = checked;
                count.hidden = checked === 0;
                if (openChecked) {
                    group.open = checked > 0;
                }
            });
        }
        form.querySelector('.js-wardrobe-care').addEventListener('change', function () {
            refreshCare(false);
        });

        function setChecked(name, values) {
            form.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
                input.checked = values.indexOf(input.value) !== -1;
            });
        }

        // item = null — новая вещь
        function openItemForm(item, updateUrl) {
            form.reset();
            gallery.reset(item ? item.photos : []);
            compat.reset(item ? item.compatible_ids : [], item ? item.id : null);
            setBrand(item ? item.brand_id : null);
            setColor(item ? item.color_id : null);
            setDirectory('size', item ? item.size : null);
            setPrint(item ? item.print_id : null);
            setMaterial(item ? item.material_id : null);

            if (item) {
                ['name', 'category', 'storage_id', 'composition', 'length', 'temp_min', 'temp_max', 'care', 'wear_level',
                    'repair_note', 'price', 'shop_url', 'note'].forEach(function (field) {
                    // не form.elements[field]: form.elements.length — число полей формы, а не поле "Длина"
                    form.querySelector('[name="' + field + '"]').value = item[field] === null || item[field] === undefined ? '' : item[field];
                });
                setChecked('status', [item.status]);
                setChecked('styles[]', item.styles || []);
                setChecked('body_types[]', item.body_types || []);
                setChecked('seasons[]', item.seasons || []);
                setChecked('occasions[]', item.occasions || []);
                setChecked('care_options[]', item.care_options || []);
                repairCheckbox.checked = !!item.needs_repair;
            }
            refreshCare(true);

            form.setAttribute('action', item ? updateUrl : itemModal.getAttribute('data-store-url'));
            form.setAttribute('data-method', item ? 'PUT' : 'POST');
            title.textContent = item ? 'Редактировать вещь' : 'Добавить вещь';
            submitBtn.textContent = item ? 'Сохранить' : 'Добавить';
            toggleRepairNote();
            toggleStorageNew();
            if (importBtn) {
                importBtn.style.display = item ? 'none' : ''; // импорт заполняет форму новой вещи
            }

            $(itemModal).modal('show');
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('.js-wardrobe-item-create')) {
                openItemForm(null);
                return;
            }
            const editBtn = e.target.closest('.js-wardrobe-item-edit');
            if (editBtn) {
                // "Редактировать" в превью вещи — сначала закрываем галерею, иначе форма окажется под ней
                if (window.Fancybox && Fancybox.getInstance()) {
                    Fancybox.close();
                }
                openItemForm(JSON.parse(editBtn.getAttribute('data-wardrobe-item')), editBtn.getAttribute('data-update-url'));
            }
        });

        // image — {post_id, index, title, thumb_url, images?}: из окна "Выбрать из постов" (одна картинка — в галерею)
        // или со ссылки "В гардероб" пина (images — все картинки поста, выбранная первой)
        function applyPostImage(image) {
            (image.images || [image]).forEach(function (postImage) {
                gallery.addPost({post_id: image.post_id, index: postImage.index, thumb_url: postImage.thumb_url});
            });

            // новая вещь с картинкой из поста — скорее всего то, что хочется купить
            if (form.getAttribute('data-method') === 'POST') {
                setChecked('status', ['wishlist']);
                if (!form.elements['name'].value && image.title) {
                    form.elements['name'].value = image.title;
                }
                if (image.clothes) {
                    applyClothesFields(image.clothes);
                }
            }
        }

        // поля пина "Одежда" (PostClothes::toWardrobeForm) — только в пустые поля новой вещи
        function applyClothesFields(clothes) {
            if (clothes.category && form.elements['category'].querySelector('option[value="' + clothes.category + '"]')) {
                form.elements['category'].value = clothes.category;
            }
            if (clothes.brand_id && !form.elements['brand_id'].value) {
                setBrand(clothes.brand_id);
            }
            if (clothes.print_id && !form.elements['print_id'].value) {
                setPrint(clothes.print_id);
            }
            if (clothes.material_id && !form.elements['material_id'].value) {
                setMaterial(clothes.material_id);
            }
            if (clothes.color_id && !form.elements['color_id'].value) {
                setColor(clothes.color_id);
            }
            ['composition', 'shop_url', 'length', 'temp_min', 'temp_max'].forEach(function (field) {
                // не form.elements[field]: form.elements.length — число полей формы, а не поле "Длина"
                const input = form.querySelector('[name="' + field + '"]');
                // температура 0 — тоже значение
                if (clothes[field] !== null && clothes[field] !== undefined && clothes[field] !== '' && !input.value) {
                    input.value = clothes[field];
                }
            });
            if (clothes.seasons && clothes.seasons.length) {
                setChecked('seasons[]', clothes.seasons);
            }
            if (clothes.occasions && clothes.occasions.length) {
                setChecked('occasions[]', clothes.occasions);
            }
            if (clothes.styles && clothes.styles.length) {
                setChecked('styles[]', clothes.styles);
            }
            if (clothes.body_types && clothes.body_types.length) {
                setChecked('body_types[]', clothes.body_types);
            }
        }

        const pickBtn = form.querySelector('.js-wardrobe-pick-from-posts');
        if (pickBtn) {
            pickBtn.addEventListener('click', function () {
                openPostPicker(applyPostImage);
            });
        }

        // "Импорт из пина": выбрать пин "Одежда" в окне постов — форма заполнится так же, как по кнопке
        // "В гардероб" у самого пина (все картинки, название, поля пина; ItemController::postImport)
        if (importBtn) {
            importBtn.addEventListener('click', function () {
                openPostPicker(function (pin) {
                    stockAjax(picker.getAttribute('data-import-url') + '?post=' + encodeURIComponent(pin.post_id), 'GET')
                        .then(function (json) {
                            applyPostImage(json.post);
                        })
                        .catch(stockError);
                }, 'pins');
            });
        }

        // пришли с пина "Одежда" (?from_post=ID, ItemController::fromPostImage) — сразу форма новой вещи
        // с картинкой поста; ссылкой "В гардеробе" (?edit_item=ID) — форма уже добавленной вещи.
        // Параметры убираем из адреса, чтобы обновление страницы не открывало форму снова
        const fromPost = itemModal.getAttribute('data-from-post');
        const editItemId = itemModal.getAttribute('data-edit-item');
        if (fromPost) {
            openItemForm(null);
            applyPostImage(JSON.parse(fromPost));
        } else if (editItemId) {
            const editBtn = Array.prototype.find.call(document.querySelectorAll('.js-wardrobe-item-edit[data-wardrobe-item]'), function (btn) {
                return String(JSON.parse(btn.getAttribute('data-wardrobe-item')).id) === editItemId;
            });
            if (editBtn) {
                openItemForm(JSON.parse(editBtn.getAttribute('data-wardrobe-item')), editBtn.getAttribute('data-update-url'));
            }
        }
        if ((fromPost || editItemId) && window.history.replaceState) {
            const url = new URL(window.location.href);
            ['from_post', 'image', 'edit_item'].forEach(function (param) { url.searchParams.delete(param); });
            window.history.replaceState(null, '', url.toString());
        }
    }

    // ---------- галереи вещей и образов (клик по фото/обложке карточки) — как "Все посты" (posts/index.blade.php) ----------
    // Превью (parts/item_preview.blade.php, outfits/preview.blade.php) грузится ajax'ом; те же mainClass, что у
    // галереи постов, поэтому и вид тот же (admin-panel.css). Bind с делегированием — подхватывает все карточки
    if (window.Fancybox) {
        // ^="wardrobe-compat-" — вещи "с чем сочетается" в превью вещи: превью той вещи поверх открытого
        ['[data-fancybox="wardrobe-gallery"]', '[data-fancybox="wardrobe-outfit-gallery"]', '[data-fancybox^="wardrobe-compat-"]'].forEach(function (selector) {
            Fancybox.bind(selector, {
                type: 'ajax',
                dragToClose: false,
                mainClass: 'fancybox-posts-gallery',
                Thumbs: false,
            });
        });
        // клик по крупному фото в превью — полный размер с зумом и листанием всех фото этой вещи / образа
        Fancybox.bind('[data-fancybox^="wardrobe-item-photo-"], [data-fancybox^="wardrobe-outfit-photo-"]', {
            dragToClose: false,
            mainClass: 'fancybox-post-zoom',
        });
    }

    // миниатюры в превью вещи/образа переключают крупное фото
    document.addEventListener('click', function (e) {
        const thumb = e.target.closest('.js-wardrobe-preview-thumb');
        if (!thumb) {
            return;
        }
        const preview = thumb.closest('.b-wardrobe-preview');
        const index = thumb.getAttribute('data-index');
        preview.querySelectorAll('.js-wardrobe-preview-photo').forEach(function (photo) {
            photo.hidden = photo.getAttribute('data-index') !== index;
        });
        preview.querySelectorAll('.js-wardrobe-preview-thumb').forEach(function (other) {
            other.classList.toggle('is-active', other === thumb);
        });
    });

    // ---------- формы мест хранения (parts/storage_form_modal.blade.php): фото ----------
    document.querySelectorAll('.js-wardrobe-storage-form').forEach(initPhotoField);

    // ---------- конструктор образа (outfits/form.blade.php) ----------
    const outfitForm = document.querySelector('.js-wardrobe-outfit-form');
    if (outfitForm) {
        initOutfitBuilder(outfitForm);
    }

    function initOutfitBuilder(form) {
        const tilesContainer = form.querySelector('.js-wardrobe-tiles');
        const tilesEmpty = form.querySelector('.js-wardrobe-tiles-empty');
        const selectedContainer = form.querySelector('.js-wardrobe-selected');
        const selectedEmpty = form.querySelector('.js-wardrobe-selected-empty');
        const searchInput = form.querySelector('.js-wardrobe-tile-search');
        const statusSelect = form.querySelector('.js-wardrobe-tile-status');
        const categorySelect = form.querySelector('.js-wardrobe-tile-category');
        // id вещей в порядке выбора — в таком порядке они и сохранятся
        let selected = JSON.parse(form.getAttribute('data-selected') || '[]').map(String);

        initPhotoField(form);

        function tileById(id) {
            return tilesContainer.querySelector('.js-wardrobe-tile[data-id="' + id + '"]');
        }

        function renderSelected() {
            selectedContainer.innerHTML = '';
            selected.forEach(function (id) {
                const tile = tileById(id);
                if (!tile) {
                    return;
                }
                const image = tile.getAttribute('data-image');

                const item = document.createElement('div');
                item.className = 'b-wardrobe-selected__item' + (tile.getAttribute('data-status') === 'wishlist' ? ' is-wishlist' : '');
                item.title = tile.getAttribute('data-name');

                const photo = document.createElement('div');
                photo.className = 'b-wardrobe-selected__photo';
                if (image) {
                    photo.style.backgroundImage = 'url("' + image + '")';
                } else {
                    photo.innerHTML = '<i class="fas fa-tshirt"></i>';
                }

                const name = document.createElement('div');
                name.className = 'b-wardrobe-selected__name';
                name.textContent = tile.getAttribute('data-name');

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'b-wardrobe-selected__remove';
                remove.setAttribute('aria-label', 'Убрать из образа');
                remove.innerHTML = '<i class="fas fa-times"></i>';
                remove.addEventListener('click', function () { toggle(id); });

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = id;

                item.appendChild(photo);
                item.appendChild(name);
                item.appendChild(remove);
                item.appendChild(input);
                selectedContainer.appendChild(item);
            });

            selectedEmpty.style.display = selectedContainer.children.length ? 'none' : '';
            tilesContainer.querySelectorAll('.js-wardrobe-tile').forEach(function (tile) {
                tile.classList.toggle('is-selected', selected.indexOf(tile.getAttribute('data-id')) !== -1);
            });
        }

        function toggle(id) {
            id = String(id);
            const index = selected.indexOf(id);
            if (index === -1) {
                selected.push(id);
            } else {
                selected.splice(index, 1);
            }
            renderSelected();
        }

        function applyFilter() {
            const search = searchInput.value.trim().toLowerCase();
            let visible = 0;
            tilesContainer.querySelectorAll('.js-wardrobe-tile').forEach(function (tile) {
                const matches =
                    (!search || tile.getAttribute('data-name').toLowerCase().indexOf(search) !== -1) &&
                    (!statusSelect.value || tile.getAttribute('data-status') === statusSelect.value) &&
                    (!categorySelect.value || tile.getAttribute('data-category') === categorySelect.value);
                tile.style.display = matches ? '' : 'none';
                if (matches) { visible++; }
            });
            tilesEmpty.style.display = visible ? 'none' : '';
        }

        // плитка вещи, созданной прямо из конструктора (та же разметка, что в form.blade.php)
        function addTile(item) {
            const tile = document.createElement('button');
            tile.type = 'button';
            tile.className = 'b-wardrobe-tile js-wardrobe-tile';
            tile.setAttribute('data-id', item.id);
            tile.setAttribute('data-name', item.name);
            tile.setAttribute('data-status', item.status);
            tile.setAttribute('data-category', item.category);
            tile.setAttribute('data-image', item.image_url || '');

            const photo = document.createElement('span');
            photo.className = 'b-wardrobe-tile__photo';
            if (item.image_url) {
                photo.style.backgroundImage = 'url("' + item.image_url + '")';
            } else {
                photo.innerHTML = '<i class="fas fa-tshirt"></i>';
            }
            if (item.status === 'wishlist') {
                photo.insertAdjacentHTML('beforeend', '<span class="b-wardrobe-badge b-wardrobe-badge--wishlist">Купить</span>');
            }
            photo.insertAdjacentHTML('beforeend', '<span class="b-wardrobe-tile__check"><i class="fas fa-check"></i></span>');

            const name = document.createElement('span');
            name.className = 'b-wardrobe-tile__name';
            name.textContent = item.name;

            tile.appendChild(photo);
            tile.appendChild(name);
            tilesContainer.insertBefore(tile, tilesContainer.firstChild);
        }

        tilesContainer.addEventListener('click', function (e) {
            const tile = e.target.closest('.js-wardrobe-tile');
            if (tile) {
                toggle(tile.getAttribute('data-id'));
            }
        });
        searchInput.addEventListener('input', applyFilter);
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); } // поиск по плиткам, а не отправка формы
        });
        statusSelect.addEventListener('change', applyFilter);
        categorySelect.addEventListener('change', applyFilter);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!selected.length) {
                stockError(new Error('Добавьте в образ хотя бы одну вещь.'));
                return;
            }

            const formData = new FormData(form);
            const method = (form.getAttribute('data-method') || 'POST').toUpperCase();
            if (method !== 'POST') {
                formData.append('_method', method);
            }
            const submitBtn = form.querySelector('[type="submit"]');
            submitBtn.disabled = true;

            stockAjax(form.getAttribute('action'), 'POST', formData)
                .then(function (json) {
                    window.location.href = json.redirect;
                })
                .catch(function (err) {
                    submitBtn.disabled = false;
                    stockError(err);
                });
        });

        // "Из постов": картинка поста → вещь "нужно купить" → сразу в образ
        const fromPostsBtn = form.querySelector('.js-wardrobe-add-from-posts');
        const quickModal = document.getElementById('modal-wardrobe-quick-item');
        if (fromPostsBtn && quickModal) {
            const quickForm = quickModal.querySelector('.js-wardrobe-quick-item-form');
            const quickPhoto = quickModal.querySelector('.js-wardrobe-quick-item-photo');
            let pickedImage = null;

            fromPostsBtn.addEventListener('click', function () {
                openPostPicker(function (image) {
                    pickedImage = image;
                    quickForm.reset();
                    quickForm.elements['name'].value = image.title || '';
                    quickPhoto.style.backgroundImage = 'url("' + image.thumb_url + '")';
                    $(quickModal).modal('show');
                });
            });

            quickForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!pickedImage) {
                    return;
                }
                const formData = new FormData(quickForm);
                formData.append('status', 'wishlist');
                formData.append('post_id', pickedImage.post_id);
                formData.append('post_image_index', pickedImage.index);
                const submitBtn = quickForm.querySelector('[type="submit"]');
                submitBtn.disabled = true;

                stockAjax(form.getAttribute('data-item-store-url'), 'POST', formData)
                    .then(function (json) {
                        addTile(json.item);
                        toggle(json.item.id);
                        applyFilter();
                        $(quickModal).modal('hide');
                    })
                    .catch(stockError)
                    .then(function () {
                        submitBtn.disabled = false;
                    });
            });
        }

        renderSelected();
    }

})();
