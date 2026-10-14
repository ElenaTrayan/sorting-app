// Страница группы постов (resources/views/user/posts/group.blade.php): общие название, категория и хештеги,
// порядок постов перетаскиванием, просмотр картинок в Fancybox, у группы текстов — редактор текста каждого поста;
// добавление постов в группу (свои посты из окна, новые картинки, новый текст) и удаление из неё.
// Сервер — PostController::updateGroup/reorderGroup/groupCandidates/attachToGroup/uploadToGroup/addTextToGroup/
// detachFromGroup/dissolveGroup. Блок хештегов обслуживает admin.js — отсюда только читаем выбранные теги.
(function () {
    var group = document.getElementById('post-group');
    if (!group) {
        return;
    }

    var $group = $(group);
    var $form = $('#post-group-form');
    var $list = $group.find('.js-post-group-list');
    var $errors = $group.find('.js-post-group-errors');
    var csrfHeaders = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
    var isText = group.dataset.kind === 'text';
    // группа текстов в режиме просмотра: тексты только читают, из действий — одно перетаскивание карточек
    var editing = group.dataset.editing === '1';

    // ===== несохранённые изменения =====

    // правки общих полей и текстов уходят на сервер только по кнопке "Сохранить"
    var dirty = false;

    function markDirty() {
        dirty = true;
    }

    $form.on('input change', 'input, select, textarea', markDirty);
    // хештеги добавляет и убирает admin.js — следим за самим списком выбранных
    if (editing) {
        new MutationObserver(markDirty).observe(document.getElementById('b-selected-tags-2'), {childList: true});
    }

    window.addEventListener('beforeunload', function (e) {
        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ===== тексты группы: число знаков и копирование =====

    // HTML текста поста — простым текстом: абзацы и пункты списка с новой строки
    function htmlToText(html) {
        var box = document.createElement('div');
        box.innerHTML = String(html || '').replace(/<br\b[^>]*>|<\/(p|div|li|h[1-6]|blockquote|tr|pre)\s*>/gi, '\n');
        return box.textContent;
    }

    function cleanText(text) {
        return String(text || '').replace(/\r/g, '').replace(/ /g, ' ').replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    // текст поста, как он сейчас на странице: при редактировании — из редактора, вместе с несохранённой правкой
    function itemText(item) {
        var textarea = item.querySelector('.js-post-group-text');
        var editor = textarea && typeof tinymce !== 'undefined' ? tinymce.get(textarea.id) : null;
        if (editor && editor.initialized) {
            return cleanText(editor.getContent({format: 'text'}));
        }
        if (textarea) {
            return cleanText(htmlToText(textarea.value));
        }
        var prose = item.querySelector('.b-post-show__prose');
        return prose ? cleanText(htmlToText(prose.innerHTML)) : '';
    }

    function charsLabel(count) {
        var mod100 = count % 100;
        var mod10 = count % 10;
        var word = (mod100 >= 11 && mod100 <= 19) ? 'знаков' : mod10 === 1 ? 'знак' : (mod10 >= 2 && mod10 <= 4) ? 'знака' : 'знаков';
        return count.toLocaleString('ru-RU') + ' ' + word;
    }

    // счётчики знаков (с пробелами): у каждого текста и у группы целиком
    function updateChars() {
        if (!isText) {
            return;
        }
        var total = 0;
        $list.children('.js-post-group-item').each(function () {
            var count = itemText(this).length;
            total += count;
            $(this).find('.js-post-group-chars').text(charsLabel(count));
        });
        $group.find('.js-post-group-chars-total').text('всего ' + charsLabel(total));
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        // без https буфер обмена доступен только по-старому — через выделенное поле
        return new Promise(function (resolve, reject) {
            var field = document.createElement('textarea');
            field.value = text;
            field.style.position = 'fixed';
            field.style.opacity = '0';
            document.body.appendChild(field);
            field.select();
            var copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (e) {
                copied = false;
            }
            document.body.removeChild(field);
            copied ? resolve() : reject();
        });
    }

    $('.js-post-group-copy').on('click', function () {
        // тексты постов по порядку на странице, пустые пропускаем, между постами — пустая строка
        var texts = $list.children('.js-post-group-item').map(function () {
            return itemText(this) || null;
        }).get();

        if (!texts.length) {
            toastr.warning('В группе пока нет текста');
            return;
        }

        copyToClipboard(texts.join('\n\n')).then(function () {
            toastr.success('Текст группы скопирован');
        }, function () {
            toastr.error('Не удалось скопировать текст');
        });
    });

    // ===== редакторы текстов (группа текстов) =====

    function initEditors(selector) {
        if (!isText || typeof tinymce === 'undefined') {
            return;
        }
        tinymce.init({
            selector: selector,
            plugins: 'autolink lists link',
            toolbar: 'undo redo | bold italic | bullist numlist | link',
            menubar: false,
            statusbar: false,
            height: 220,
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: function (editor) {
                editor.on('input change undo redo', markDirty);
                editor.on('init input change undo redo keyup SetContent', updateChars);
            },
        });
    }

    initEditors('textarea.js-post-group-text');
    updateChars();

    // ===== сохранение =====

    function selectedHashtags() {
        var hashtags = {};
        $('#b-selected-tags-2 .tag').each(function () {
            hashtags[this.getAttribute('data-id')] = this.getAttribute('data-name');
        });
        return JSON.stringify(hashtags);
    }

    function save() {
        // TinyMCE переносит текст в свой textarea только по triggerSave()
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        var data = $form.serializeArray();
        data.push({name: 'hashtags', value: selectedHashtags()});

        var $button = $group.find('.js-post-group-save').prop('disabled', true);

        return $.ajax({url: group.dataset.updateAction, type: 'POST', headers: csrfHeaders, data: data})
            .done(function (response) {
                dirty = false;
                toastr.success(response.message || 'Группа сохранена');
            })
            .fail(function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                toastr.error(errors ? Object.values(errors)[0][0] : 'Не удалось сохранить группу');
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    }

    // действие, после которого страница перезагрузится: несохранённые правки сначала сохраняем
    function afterSave(action, onFail) {
        if (dirty) {
            save().done(action).fail(onFail || $.noop);
        } else {
            action();
        }
    }

    function leaveTo(url) {
        dirty = false;
        if (url) {
            window.location.href = url;
        } else {
            window.location.reload();
        }
    }

    $form.on('submit', function (e) {
        e.preventDefault();
    });
    $group.on('click', '.js-post-group-save', save);

    // ===== отметка "Использовано" — одна на всю группу =====

    $('.js-post-group-used').on('click', function () {
        var $button = $(this).prop('disabled', true);
        var used = !$button.data('used');

        // сервер по одному посту группы ставит отметку всем её постам
        $.ajax({url: $button.data('url'), type: 'POST', headers: csrfHeaders, data: {ids: [$button.data('id')], used: used ? 1 : 0}})
            .done(function () {
                $button.data('used', used ? 1 : 0)
                    .toggleClass('b-post-show__btn--done', used)
                    .find('span').text(used ? 'Использовано' : 'Отметить использованной');
                toastr.success(used ? 'Группа отмечена использованной' : 'Отметка «Использовано» снята');
            })
            .fail(function () {
                toastr.error('Не удалось изменить отметку');
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    // ===== просмотр =====

    if (!isText) {
        Fancybox.bind('[data-fancybox="post-group"]', {
            dragToClose: false,
        });
    }

    // ===== порядок =====

    function plural(n, forms) {
        var mod100 = Math.abs(n) % 100;
        var mod10 = mod100 % 10;
        if (mod100 >= 11 && mod100 <= 19) {
            return forms[2];
        }
        if (mod10 === 1) {
            return forms[0];
        }
        return mod10 >= 2 && mod10 <= 4 ? forms[1] : forms[2];
    }

    function refreshPositions() {
        var $items = $list.children('.js-post-group-item');
        $items.each(function (index) {
            $(this).find('.js-post-group-position').text(index + 1);
        });

        var $count = $group.find('.js-post-group-count');
        $count.text($items.length + ' ' + plural($items.length, $count.data('forms').split(',')));

        // постов стало больше или меньше — общее число знаков тоже другое
        updateChars();
    }

    function saveOrder() {
        var order = $list.children('.js-post-group-item').map(function () {
            return $(this).data('id');
        }).get();

        $.ajax({url: group.dataset.reorderAction, type: 'POST', headers: csrfHeaders, data: {order: order}})
            .done(function (response) {
                if (response && response.status) {
                    toastr.success(response.message || 'Порядок сохранён');
                } else {
                    toastr.error((response && response.message) || 'Не удалось сохранить порядок');
                }
            })
            .fail(function () {
                toastr.error('Не удалось сохранить порядок');
            });
    }

    function editorOf(item) {
        var textarea = item.querySelector('.js-post-group-text');
        return textarea && typeof tinymce !== 'undefined' ? tinymce.get(textarea.id) : null;
    }

    if ($.fn.sortable) {
        $list.sortable({
            // тексты при редактировании — только за ручку (в карточке редактор); картинки и тексты
            // в режиме просмотра — за саму карточку
            handle: isText && editing ? '.js-post-group-handle' : false,
            cancel: 'input, textarea, button, select, option, .b-post-group__note',
            axis: isText ? 'y' : false,
            distance: 6,
            tolerance: 'pointer',
            placeholder: 'b-post-group__item ui-sortable-placeholder ' + (isText ? 'b-post-group__item--text' : 'b-post-group__item--picture'),
            forcePlaceholderSize: true,
            // iframe редактора не переживает перенос по DOM — на время перетаскивания убираем редактор
            start: function (event, ui) {
                var editor = editorOf(ui.item[0]);
                if (editor) {
                    editor.save();
                    editor.remove();
                }
            },
            stop: function (event, ui) {
                var textarea = ui.item[0].querySelector('.js-post-group-text');
                if (textarea) {
                    initEditors('#' + textarea.id);
                }
            },
            update: function () {
                refreshPositions();
                saveOrder();
            },
        });
    }

    // ===== убрать из группы / удалить пост / разгруппировать =====

    function removeItem($button, data, failMessage) {
        var $item = $button.closest('.js-post-group-item');
        $button.prop('disabled', true);

        // сначала сохраняем правки: после ухода поста группа может сменить адрес, и сохранять будет уже некуда
        afterSave(function () {
            $.ajax({url: $button.data('url'), type: 'DELETE', headers: csrfHeaders, data: data})
                .done(function (response) {
                    if (!response.status) {
                        $button.prop('disabled', false);
                        toastr.error(response.message || failMessage);
                        return;
                    }
                    // группа опустела или сменила адрес (ушёл пост, чей id был её ключом)
                    if (response.redirect) {
                        leaveTo(response.redirect);
                        return;
                    }

                    var editor = editorOf($item[0]);
                    if (editor) {
                        editor.remove();
                    }
                    $item.remove();
                    refreshPositions();
                })
                .fail(function () {
                    $button.prop('disabled', false);
                    toastr.error(failMessage);
                });
        }, function () {
            $button.prop('disabled', false);
        });
    }

    $list.on('click', '.js-post-group-detach', function () {
        removeItem($(this), {}, 'Не удалось убрать пост из группы');
    });

    // удаление поста — только после подтверждения в окне
    var $deleteModal = $('#modal-post-group-delete');
    var $deleteButton = null;

    $list.on('click', '.js-post-group-delete', function () {
        $deleteButton = $(this);
        $deleteModal.modal('show');
    });

    $deleteModal.on('click', '.js-post-group-delete-confirm', function () {
        $deleteModal.modal('hide');
        if ($deleteButton) {
            removeItem($deleteButton, {delete: 1}, 'Не удалось удалить пост');
            $deleteButton = null;
        }
    });

    $('.js-post-group-dissolve').on('click', function () {
        if (!window.confirm('Разгруппировать? Посты останутся, но станут отдельными.')) {
            return;
        }
        afterSave(function () {
            $.ajax({url: group.dataset.dissolveAction, type: 'DELETE', headers: csrfHeaders})
                .done(function (response) {
                    leaveTo(response.redirect);
                })
                .fail(function () {
                    toastr.error('Не удалось разгруппировать');
                });
        });
    });

    // ===== поворот картинки и палитра (плитки группы изображений) =====

    $list.on('click', '.js-post-group-rotate', function () {
        var $button = $(this);
        var $item = $button.closest('.js-post-group-item');
        var $buttons = $item.find('.js-post-group-rotate').prop('disabled', true);

        $.ajax({
            url: $button.data('url'),
            type: 'POST',
            headers: csrfHeaders,
            data: {name: $button.data('name'), action: $button.data('action')},
        })
            .done(function (response) {
                if (!response.status) {
                    toastr.error(response.message || 'Не удалось повернуть изображение');
                    return;
                }
                // файлы те же, поменялось содержимое — обходим кеш браузера
                var version = '?v=' + Date.now();
                var $img = $item.find('.b-post-group__media img');
                var $link = $item.find('.b-post-group__media');
                $img.attr('src', $img.attr('src').split('?')[0] + version);
                $link.attr('href', $link.attr('href').split('?')[0] + version);
                toastr.success(response.message);
            })
            .fail(function () {
                toastr.error('Не удалось повернуть изображение');
            })
            .always(function () {
                $buttons.prop('disabled', false);
            });
    });

    function renderPaletteStrip($item, palette) {
        var $strip = $item.find('.js-post-group-palette-strip').empty();
        (palette || []).forEach(function (color) {
            $('<span></span>').css({flex: String(color.share), background: color.hex}).appendTo($strip);
        });
    }

    $list.on('click', '.js-post-group-palette', function () {
        var $button = $(this).prop('disabled', true);
        var $item = $button.closest('.js-post-group-item');

        // свежие палитра и обложка поста — на плитке их могли уже поменять (поворот, прошлое открытие окна)
        $.get($button.data('showAction'))
            .done(function (data) {
                var root = document.getElementById('post-group-palette-template').content.firstElementChild.cloneNode(true);
                root.setAttribute('data-show-action', $button.data('showAction'));
                root.setAttribute('data-update-action', $button.data('updateAction'));
                root.setAttribute('data-reset-action', $button.data('resetAction'));
                root.setAttribute('data-cover-url', data.cover_url ? data.cover_url + '?v=' + Date.now() : '');
                root.setAttribute('data-edited', data.edited ? '1' : '0');
                root.setAttribute('data-palette', JSON.stringify(data.palette || []));

                $('.js-post-group-palette-body').empty().append(root);
                initPaletteEditor(root, function (palette) {
                    renderPaletteStrip($item, palette);
                });
                $('#modal-post-group-palette').modal('show');
            })
            .fail(function () {
                toastr.error('Не удалось загрузить палитру');
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    // ===== новые посты: картинки и текст =====

    function appendItem(html) {
        var $item = $(html).appendTo($list);
        refreshPositions();
        if ($.fn.sortable) {
            $list.sortable('refresh');
        }
        return $item;
    }

    // картинки грузим по одной: каждая — отдельный пост, ошибка одной (например, дубликат) не мешает остальным
    function uploadFiles(files, index, failed) {
        var $label = $('.js-post-group-upload-label');

        if (index >= files.length) {
            $label.removeClass('is-loading').find('span').text('Загрузить изображения');
            $errors.toggle(failed.length > 0).html(failed.map(function (message) {
                return $('<div></div>').text(message).prop('outerHTML');
            }).join(''));
            return;
        }

        $label.addClass('is-loading').find('span').text('Загрузка ' + (index + 1) + ' из ' + files.length + '…');

        var formData = new FormData();
        formData.append('image', files[index]);

        var fileName = files[index].name;
        var next = function () {
            uploadFiles(files, index + 1, failed);
        };
        // картинка сохранена новым постом; если ею заменили старую, а её пост опустел и ушёл в корзину — плитку долой
        var added = function (response) {
            appendItem(response.html);
            if (response.replaced && response.replaced.trashed) {
                $list.children('.js-post-group-item[data-id="' + response.replaced.post_id + '"]').remove();
                refreshPositions();
            }
        };

        $.ajax({url: group.dataset.uploadAction, type: 'POST', headers: csrfHeaders, data: formData, processData: false, contentType: false})
            .done(function (response) {
                if (response.status) {
                    added(response);
                    next();
                    return;
                }
                if (!response.needs_choice) {
                    failed.push(fileName + ': ' + (response.message || 'не удалось загрузить'));
                    next();
                    return;
                }

                // похожая картинка уже есть — очередь ждёт, пока в окне не решат, что с ней делать
                askAboutDuplicate(response).then(function (decision) {
                    if (!decision) {
                        failed.push(fileName + ': не добавлена — похожая картинка уже есть в посте #' + response.duplicate.post_id);
                        next();
                        return;
                    }

                    $.ajax({
                        url: group.dataset.uploadAction,
                        type: 'POST',
                        headers: csrfHeaders,
                        data: {
                            staged_image: response.staged_image,
                            decision: decision,
                            duplicate_post_id: response.duplicate.post_id,
                            duplicate_image_name: response.duplicate.image_name,
                        },
                    })
                        .done(function (result) {
                            if (result.status) {
                                added(result);
                            } else {
                                failed.push(fileName + ': ' + (result.message || 'не удалось добавить'));
                            }
                        })
                        .fail(function () {
                            failed.push(fileName + ': не удалось добавить');
                        })
                        .always(next);
                });
            })
            .fail(function () {
                failed.push(fileName + ': не удалось загрузить');
                next();
            });
    }

    // Окно сравнения: загружаемая картинка похожа на уже существующую. Возвращает Promise с решением:
    // 'replace' (старую убрать из её поста, новую добавить), 'add' (это разные картинки) или null (не добавлять)
    function askAboutDuplicate(response) {
        return new Promise(function (resolve) {
            var $duplicateModal = $('#modal-post-group-duplicate');
            var decision = null;

            $duplicateModal.find('.js-post-group-duplicate-message').text(response.message || '');
            $duplicateModal.find('.js-post-group-duplicate-post').text(response.duplicate.post_id);
            $duplicateModal.find('.js-post-group-duplicate-new').attr('src', response.new_image.thumbnail_url);
            $duplicateModal.find('.js-post-group-duplicate-new-size').text(response.new_image.width + '×' + response.new_image.height);
            $duplicateModal.find('.js-post-group-duplicate-old').attr('src', response.duplicate.thumbnail_url);
            $duplicateModal.find('.js-post-group-duplicate-old-size').text(response.duplicate.width + '×' + response.duplicate.height);

            // новая крупнее — предлагаем заменить; "добавить как другую" доступно всегда
            $duplicateModal.find('.js-post-group-duplicate-replace').toggle(response.mode === 'replace');
            $duplicateModal.find('.js-post-group-duplicate-add')
                .toggleClass('btn-primary', response.mode !== 'replace')
                .toggleClass('btn-outline-primary', response.mode === 'replace');

            $duplicateModal.off('.duplicate');
            $duplicateModal.on('click.duplicate', '.js-post-group-duplicate-replace', function () {
                decision = 'replace';
                $duplicateModal.modal('hide');
            });
            $duplicateModal.on('click.duplicate', '.js-post-group-duplicate-add', function () {
                decision = 'add';
                $duplicateModal.modal('hide');
            });
            $duplicateModal.on('click.duplicate', '.js-post-group-duplicate-skip', function () {
                $duplicateModal.modal('hide');
            });
            $duplicateModal.one('hidden.bs.modal', function () {
                $duplicateModal.off('.duplicate');
                resolve(decision);
            });

            $duplicateModal.modal({backdrop: 'static', keyboard: false, show: true});
        });
    }

    $('.js-post-group-upload').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        this.value = '';
        if (files.length) {
            uploadFiles(files, 0, []);
        }
    });

    $('.js-post-group-add-text').on('click', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({url: group.dataset.addTextAction, type: 'POST', headers: csrfHeaders})
            .done(function (response) {
                var $item = appendItem(response.html);
                initEditors('#' + $item.find('.js-post-group-text').attr('id'));
                $item[0].scrollIntoView({behavior: 'smooth', block: 'center'});
            })
            .fail(function () {
                toastr.error('Не удалось добавить текст');
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    // ===== окно "Добавить посты" =====

    var $modal = $('#modal-post-group-add');
    var $search = $modal.find('.js-post-group-search');
    var $candidates = $modal.find('.js-post-group-candidates');
    var $none = $modal.find('.js-post-group-none');
    var $more = $modal.find('.js-post-group-more');
    var $submit = $modal.find('.js-post-group-submit');

    // выбранные посты { id: true } — живут, пока открыто окно, и переживают новый поиск
    var selected = {};
    var page = 1;
    var request = null;
    var searchTimer = null;

    function updateSubmit() {
        var count = Object.keys(selected).length;
        $submit.prop('disabled', count === 0).text(count ? 'Добавить (' + count + ')' : 'Добавить');
    }

    function candidateTile(item) {
        var $tile = $('<button type="button" class="b-film-materials__candidate js-post-group-candidate"></button>')
            .attr('data-id', item.id)
            .attr('title', item.title || '')
            .toggleClass('is-selected', !!selected[item.id]);

        if (item.video) {
            $tile.append($('<video preload="metadata" muted></video>').attr('src', item.video));
            $tile.append('<span class="b-film-materials__play"><i class="fas fa-play"></i></span>');
        } else if (item.cover) {
            $tile.append($('<img alt="" loading="lazy">').attr('src', item.cover));
        } else {
            $tile.append($('<span class="b-film-materials__thumb-text"></span>').text(item.title || 'Без названия'));
        }
        $tile.append('<span class="b-film-materials__candidate-check"><i class="fas fa-check"></i></span>');

        return $tile;
    }

    function loadCandidates(reset) {
        if (reset) {
            page = 1;
        }
        if (request) {
            request.abort();
        }
        request = $.get(group.dataset.candidatesUrl, {q: $search.val(), page: page})
            .done(function (data) {
                if (reset) {
                    $candidates.empty();
                }
                data.items.forEach(function (item) {
                    $candidates.append(candidateTile(item));
                });
                $more.toggle(!!data.has_more);
                $none.toggle($candidates.children().length === 0);
            })
            .fail(function (xhr, status) {
                if (status !== 'abort') {
                    toastr.error('Не удалось загрузить посты');
                }
            });
    }

    $('.js-post-group-add').on('click', function () {
        selected = {};
        updateSubmit();
        $search.val('');
        $candidates.empty();
        loadCandidates(true);
        $modal.modal('show');
    });

    $search.on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadCandidates(true);
        }, 300);
    });

    $more.on('click', function () {
        page++;
        loadCandidates(false);
    });

    $candidates.on('click', '.js-post-group-candidate', function () {
        var id = $(this).data('id');
        if (selected[id]) {
            delete selected[id];
        } else {
            selected[id] = true;
        }
        $(this).toggleClass('is-selected', !!selected[id]);
        updateSubmit();
    });

    $submit.on('click', function () {
        $submit.prop('disabled', true);
        // хештеги добавленных постов сливаются с хештегами группы на сервере — проще перерисовать страницу
        afterSave(function () {
            $.ajax({url: group.dataset.attachUrl, type: 'POST', headers: csrfHeaders, data: {ids: Object.keys(selected)}})
                .done(function () {
                    leaveTo(null);
                })
                .fail(function () {
                    updateSubmit();
                    toastr.error('Не удалось добавить посты');
                });
        }, updateSubmit);
    });
})();
