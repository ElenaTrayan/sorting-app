// Режим выбора на "Все посты" (resources/views/user/posts/index.blade.php): кнопка "Выбрать" в панели над лентой
// включает его, клик по карточке отмечает пост (вместо открытия превью), панель внизу — действия с отмеченными.
// Действия:
//  - "Добавить хештеги" — то же окно #modal-add-hashtag, что и у "Добавить тег" в меню карточки (блок хештегов
//    в нём обслуживает admin.js), но хештеги получают все отмеченные посты (PostController::bulkAddHashtags;
//    он только добавляет — прежние хештеги постов остаются); отмеченная стопка — вся группа (общие хештеги);
//  - "Сгруппировать" — новая группа из отмеченных или добавление в группу, посты которой есть среди них
//    (bulkGroup; что можно группировать, проверяет сервер), после — страница группы;
//  - "Сменить категорию" — одна категория всем отмеченным (bulkSetCategory);
//  - "Использовано" — поставить (или снять, если все отмеченные уже использованы) отметку posts.is_used;
//    тот же запрос — у пункта "Отметить использованным" в меню карточки (bulkSetUsed);
//  - "Удалить" — отмеченные посты в корзину, после подтверждения; отмеченная стопка — вся группа (bulkDelete).
(function () {
    var page = document.getElementById('posts-index');
    var bar = document.querySelector('.js-posts-select-bar');
    if (!page || !bar) {
        return;
    }

    var $page = $(page);
    var $bar = $(bar);
    var $toggle = $('.js-posts-select-toggle');
    var $modal = $('#modal-add-hashtag');
    var csrfHeaders = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};

    var selecting = false;
    // отмеченные посты { id: true } — карточки подгружаются бесконечным скроллом, поэтому храним id, а не элементы
    var selected = {};

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

    function selectedIds() {
        return Object.keys(selected);
    }

    function refresh() {
        var count = selectedIds().length;
        $bar.find('.js-posts-select-count').text('Выбрано: ' + count);
        $bar.find('.js-posts-select-action').prop('disabled', count === 0);
        $page.find('.grid-item').each(function () {
            $(this).toggleClass('is-selected', !!selected[$(this).data('id')]);
        });
        $bar.find('.js-posts-select-used span').text(allSelectedUsed() ? 'Снять отметку «Использовано»' : 'Использовано');
    }

    function setSelecting(on) {
        selecting = on;
        if (!on) {
            selected = {};
        }
        $page.toggleClass('is-selecting', on);
        $toggle.toggleClass('b-posts-sort__trigger--active', on);
        bar.hidden = !on;
        refresh();
    }

    $toggle.on('click', function () {
        setSelecting(!selecting);
    });

    $bar.on('click', '.js-posts-select-cancel', function () {
        setSelecting(false);
    });

    $bar.on('click', '.js-posts-select-all', function () {
        $page.find('.grid-item').each(function () {
            selected[$(this).data('id')] = true;
        });
        refresh();
    });

    // в режиме выбора клик по карточке только отмечает её. Перехват — на фазе capture: превью (Fancybox), ссылки
    // и видео на карточке слушают клик позже и до них он дойти не должен
    page.addEventListener('click', function (event) {
        if (!selecting) {
            return;
        }
        var item = event.target.closest('.grid-item');
        if (!item) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        var id = item.getAttribute('data-id');
        if (selected[id]) {
            delete selected[id];
        } else {
            selected[id] = true;
        }
        refresh();
    }, true);

    // бесконечный скролл дорисовал карточки — те, что были отмечены раньше (или после поиска), остаются отмеченными.
    // Следим только за лентой: панель выбора тоже внутри страницы, и её счётчик меняет сам refresh()
    var cards = page.querySelector('.b-cards');
    if (cards) {
        new MutationObserver(function () {
            if (selecting) {
                refresh();
            }
        }).observe(cards, {childList: true, subtree: true});
    }

    // переключили галочку "Свернуть группы" (admin.js) — отметки с постов групп снимаем: у них меняется смысл.
    // Свернули — отмеченные отдельные посты группы пропали бы из ленты, но остались бы в выборе (и удалились бы
    // невидимыми); развернули — отмеченная стопка (вся группа) стала бы отметкой одного первого поста.
    // Отметки обычных постов остаются. Карточки ещё старые: какие из отмеченных — посты групп, видно по data-group-id
    $(document).on('posts:groups-toggle', function () {
        if (!selecting) {
            return;
        }
        var dropped = 0;
        $page.find('.grid-item[data-group-id]').each(function () {
            var id = $(this).data('id');
            if (selected[id]) {
                delete selected[id];
                dropped++;
            }
        });
        if (dropped) {
            refresh();
            toastr.info('Отметки с постов групп сняты — отметьте их заново');
        }
    });

    // ===== добавить хештеги отмеченным постам =====

    function setBulk(on) {
        $modal.data('bulk', on);
        $modal.find('.modal-title').text(on
            ? 'Добавить хештеги: ' + selectedIds().length + ' ' + plural(selectedIds().length, ['пост', 'поста', 'постов'])
            : '');
    }

    $bar.on('click', '.js-posts-select-hashtags', function () {
        // начинаем с пустого списка: это хештеги, которые добавятся ко всем, а не хештеги какого-то одного поста
        $modal.find('#b-selected-tags-2').empty();
        sessionStorage.setItem('hashtags2', '{}');
        setBulk(true);
        $modal.modal('show');
        setTimeout(function () {
            $modal.find('#search-input-2').trigger('focus');
        }, 500);
    });

    $modal.on('hidden.bs.modal', function () {
        setBulk(false);
    });

    // карточке поста — недостающие хештеги; блока хештегов у поста без них ещё нет
    function addHashtagsToCard($item, hashtags) {
        var $tags = $item.find('.b-card__footer .tags');
        if (!$tags.length) {
            $tags = $('<ul class="tags"></ul>');
            var $footer = $('<div class="b-card__footer"></div>').append($tags);
            var $content = $item.find('.b-card__content');
            if ($content.length) {
                $footer.insertAfter($content);
            } else {
                $item.append($footer);
            }
        }
        // отдельная карточка поста группы получает хештеги только себе — это его собственные, другим цветом
        var isGroupPostCard = $item.is('[data-group-id]') && !$item.hasClass('grid-item--stack');
        hashtags.forEach(function (hashtag) {
            if ($tags.find('li[data-id="' + hashtag.id + '"]').length) {
                return;
            }
            var $li = $('<li></li>')
                .attr('data-id', hashtag.id)
                .attr('data-title', hashtag.title)
                .append($('<a rel="tag" href="#"></a>').text('#' + hashtag.title))
                .appendTo($tags);
            if (isGroupPostCard) {
                $li.addClass('tags__own').attr('title', 'Хештег только этого поста группы');
            }
        });
    }

    $modal.on('click', '[data-action=save-request]', function () {
        if (!$modal.data('bulk')) {
            return;
        }

        var hashtags = {};
        $modal.find('#b-selected-tags-2 .tag').each(function () {
            hashtags[this.getAttribute('data-id')] = this.getAttribute('data-name');
        });
        if (!Object.keys(hashtags).length) {
            toastr.warning('Выберите хотя бы один хештег');
            return;
        }

        var $button = $(this).prop('disabled', true);

        $.ajax({
            url: bar.dataset.hashtagsAction,
            type: 'POST',
            headers: csrfHeaders,
            data: {
                ids: selectedIds(),
                // отмеченные стопки — вся группа: хештеги получат все её посты (станут общими); отдельная карточка
                // поста группы — только этот пост (PostController::bulkAddHashtags)
                group_ids: selectedGroups().map(function (group) {
                    return group.id;
                }),
                hashtags: JSON.stringify(hashtags),
            },
        })
            .done(function (response) {
                response.post_ids.forEach(function (id) {
                    var $item = $page.find('.grid-item[data-id="' + id + '"]');
                    if ($item.length) {
                        addHashtagsToCard($item, response.hashtags);
                    }
                });
                // карточки стали выше — сетку надо переложить
                var $grid = $page.find('.grid');
                if ($grid.data('masonry')) {
                    $grid.masonry('layout');
                }

                toastr.success('Хештеги добавлены: ' + response.post_ids.length + ' ' + plural(response.post_ids.length, ['пост', 'поста', 'постов']));
                $modal.modal('hide');
                $modal.find('#b-selected-tags-2').empty();
                sessionStorage.removeItem('hashtags2');
                setSelecting(false);
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Не удалось добавить хештеги');
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    function countLabel() {
        var count = selectedIds().length;
        return count + ' ' + plural(count, ['пост', 'поста', 'постов']);
    }

    function failMessage(xhr, fallback) {
        return (xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }

    // ===== сгруппировать отмеченные посты =====

    var $groupModal = $('#modal-posts-bulk-group');

    $bar.on('click', '.js-posts-select-group', function () {
        $groupModal.find('.js-posts-bulk-group-title').text('Сгруппировать ' + countLabel() + '?');
        $groupModal.modal('show');
    });

    $groupModal.on('click', '.js-posts-bulk-group-confirm', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({url: bar.dataset.groupAction, type: 'POST', headers: csrfHeaders, data: {ids: selectedIds()}})
            .done(function (response) {
                // группу сразу открываем — там её порядок и общие поля
                window.location.href = response.redirect;
            })
            .fail(function (xhr) {
                $button.prop('disabled', false);
                $groupModal.modal('hide');
                // что именно не так с выбором (разные виды постов, пост с текстом и картинкой...) — говорит сервер
                toastr.error(failMessage(xhr, 'Не удалось сгруппировать посты'));
            });
    });

    // ===== сменить категорию отмеченным постам =====

    var $categoryModal = $('#modal-posts-bulk-category');

    $bar.on('click', '.js-posts-select-category', function () {
        $categoryModal.find('.js-posts-bulk-category-title').text('Сменить категорию: ' + countLabel());
        $categoryModal.find('#posts-bulk-category').val('');
        $categoryModal.modal('show');
    });

    $categoryModal.on('click', '.js-posts-bulk-category-confirm', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({
            url: bar.dataset.categoryAction,
            type: 'POST',
            headers: csrfHeaders,
            data: {ids: selectedIds(), category_id: $categoryModal.find('#posts-bulk-category').val()},
        })
            .done(function (response) {
                toastr.success('Категория изменена: ' + response.updated + ' ' + plural(response.updated, ['пост', 'поста', 'постов']));
                $categoryModal.modal('hide');
                setSelecting(false);
            })
            .fail(function (xhr) {
                toastr.error(failMessage(xhr, 'Не удалось сменить категорию'));
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    // ===== отметка "Использовано": панель выбора и пункт меню карточки =====

    function isUsed($item) {
        return $item.children('.ribbon').length > 0;
    }

    // лента "Использовано" на карточке и подпись пункта в её меню (разметка — как в parts/post_items.blade.php)
    function setCardUsed($item, used) {
        $item.children('.ribbon').remove();
        if (used) {
            $item.append('<div class="ribbon ribbon-success float-end"><i class="fas fa-solid fa-check mr-1"></i>Использовано</div>');
        }
        $item.find('.js-post-used-label').text(used ? 'Снять отметку «Использовано»' : 'Отметить использованным');
    }

    function setUsed(ids, used) {
        return $.ajax({url: bar.dataset.usedAction, type: 'POST', headers: csrfHeaders, data: {ids: ids, used: used ? 1 : 0}})
            .done(function (response) {
                // вместе с постами групп: у группы отметка одна на всех
                response.post_ids.forEach(function (id) {
                    var $item = $page.find('.grid-item[data-id="' + id + '"]');
                    if ($item.length) {
                        setCardUsed($item, response.used);
                    }
                });
                // карточки изменили высоту — сетку надо переложить
                var $grid = $page.find('.grid');
                if ($grid.data('masonry')) {
                    $grid.masonry('layout');
                }
            })
            .fail(function (xhr) {
                toastr.error(failMessage(xhr, 'Не удалось изменить отметку'));
            });
    }

    // отмеченные карточки уже все использованы — кнопка панели снимает отметку, иначе ставит
    function allSelectedUsed() {
        var $selected = $page.find('.grid-item.is-selected');
        return $selected.length > 0 && $selected.filter(function () {
            return !isUsed($(this));
        }).length === 0;
    }

    $page.on('click', '[data-action=toggle-used]', function () {
        var $item = $(this).closest('.grid-item');
        var used = !isUsed($item);

        setUsed([$item.data('id')], used).done(function () {
            toastr.success(used ? 'Отмечено: использовано' : 'Отметка «Использовано» снята');
        });
    });

    $bar.on('click', '.js-posts-select-used', function () {
        var $button = $(this).prop('disabled', true);
        var used = !allSelectedUsed();

        setUsed(selectedIds(), used)
            .done(function (response) {
                var count = response.post_ids.length + ' ' + plural(response.post_ids.length, ['пост', 'поста', 'постов']);
                toastr.success(used ? 'Отмечено использованными: ' + count : 'Отметка снята: ' + count);
                setSelecting(false);
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    // ===== удалить отмеченные посты (в корзину) =====

    var $deleteModal = $('#modal-posts-bulk-delete');

    // отмеченные карточки-стопки: стопка — это вся группа, её посты удаляются все
    function selectedGroups() {
        var groups = [];
        $page.find('.grid-item--stack.is-selected').each(function () {
            groups.push({id: $(this).data('groupId'), count: parseInt($(this).find('.b-card__stack-badge').text(), 10) || 1});
        });
        return groups;
    }

    $bar.on('click', '.js-posts-select-delete', function () {
        var groups = selectedGroups();
        // всего постов: отмеченные карточки, у стопок вместо одной карточки — все посты группы
        var total = selectedIds().length - groups.length + groups.reduce(function (sum, group) {
            return sum + group.count;
        }, 0);

        $deleteModal.find('.js-posts-bulk-delete-title').text('Удалить ' + total + ' ' + plural(total, ['пост', 'поста', 'постов']) + '?');
        $deleteModal.find('.js-posts-bulk-delete-groups')
            .prop('hidden', groups.length === 0)
            .text('Среди отмеченных ' + groups.length + ' ' + plural(groups.length, ['группа', 'группы', 'групп'])
                + ' — ' + plural(groups.length, ['она удалится', 'они удалятся', 'они удалятся']) + ' целиком, со всеми постами.');
        $deleteModal.modal('show');
    });

    $deleteModal.on('click', '.js-posts-bulk-delete-confirm', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({
            url: bar.dataset.deleteAction,
            type: 'POST',
            headers: csrfHeaders,
            data: {
                ids: selectedIds(),
                group_ids: selectedGroups().map(function (group) {
                    return group.id;
                }),
            },
        })
            .done(function (response) {
                var $items = $();
                response.post_ids.forEach(function (id) {
                    $items = $items.add($page.find('.grid-item[data-id="' + id + '"]'));
                });
                var $grid = $page.find('.grid');
                if ($grid.data('masonry')) {
                    $grid.masonry('remove', $items).masonry('layout');
                } else {
                    $items.remove();
                }

                $('.js-posts-trash-count').text('Корзина (' + response.trash_count + ')');
                toastr.success('В корзину: ' + response.deleted + ' ' + plural(response.deleted, ['пост', 'поста', 'постов']));
                $deleteModal.modal('hide');
                setSelecting(false);
            })
            .fail(function (xhr) {
                toastr.error(failMessage(xhr, 'Не удалось удалить посты'));
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });
})();
