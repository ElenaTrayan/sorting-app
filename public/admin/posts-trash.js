// Страница "Корзина" (resources/views/user/posts/trash.blade.php): вернуть пост, группу (плитка-стопка — все
// посты одной группы, лежащие в корзине) или всё сразу, удалить пост или группу навсегда, очистить корзину.
// Всё, что удаляет навсегда, — только после подтверждения в окне. Сервер — PostController::restoreTrashed/
// restoreTrashedGroup/restoreAllTrashed/purgeTrashed/purgeTrashedGroup/emptyTrash.
(function () {
    var page = document.getElementById('posts-trash');
    if (!page) {
        return;
    }

    var $page = $(page);
    var $modal = $('#modal-posts-trash-confirm');
    var csrfHeaders = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};

    // карточка ушла со страницы; последняя — перерисовываем (пустая корзина или следующая страница)
    function removeItem($item) {
        $item.remove();
        if (!$page.find('.js-posts-trash-item').length) {
            window.location.reload();
        }
    }

    $page.on('click', '.js-posts-trash-restore', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({url: $button.data('url'), type: 'POST', headers: csrfHeaders})
            .done(function (response) {
                toastr.success(response.message || 'Пост восстановлен');
                removeItem($button.closest('.js-posts-trash-item'));
            })
            .fail(function () {
                $button.prop('disabled', false);
                toastr.error('Не удалось восстановить пост');
            });
    });

    // "Вернуть всё" — после него корзина пуста, перерисовываем страницу
    $('.js-posts-trash-restore-all').on('click', function () {
        var $button = $(this).prop('disabled', true);

        $.ajax({url: $button.data('url'), type: 'POST', headers: csrfHeaders})
            .done(function () {
                window.location.reload();
            })
            .fail(function () {
                $button.prop('disabled', false);
                toastr.error('Не удалось восстановить посты');
            });
    });

    // ===== удалить навсегда — после подтверждения =====

    // что подтверждают: {url, $item} — один пост, {url} — всю корзину
    var pending = null;

    function confirmPurge(title, text, target) {
        pending = target;
        $modal.find('.js-posts-trash-confirm-title').text(title);
        $modal.find('.js-posts-trash-confirm-text').text(text);
        $modal.modal('show');
    }

    $page.on('click', '.js-posts-trash-purge', function () {
        // у плитки-стопки (несколько постов одной группы) — число постов в data-count
        var count = $(this).data('count');
        var target = {url: $(this).data('url'), $item: $(this).closest('.js-posts-trash-item')};

        if (count) {
            confirmPurge(
                'Удалить группу навсегда?',
                'Все посты этой группы из корзины (' + count + ') и их файлы будут удалены. Восстановить их после этого нельзя.',
                target
            );
        } else {
            confirmPurge(
                'Удалить пост навсегда?',
                'Пост и его файлы будут удалены. Восстановить его после этого нельзя.',
                target
            );
        }
    });

    $('.js-posts-trash-empty').on('click', function () {
        confirmPurge(
            'Очистить корзину?',
            'Все посты из корзины и их файлы будут удалены навсегда. Восстановить их после этого нельзя.',
            {url: page.dataset.emptyAction}
        );
    });

    $modal.on('click', '.js-posts-trash-confirm', function () {
        if (!pending) {
            return;
        }
        var target = pending;
        var $button = $(this).prop('disabled', true);

        $.ajax({url: target.url, type: 'DELETE', headers: csrfHeaders})
            .done(function (response) {
                if (!response.status) {
                    toastr.error(response.message || 'Не удалось удалить');
                    // корзину могло очистить частично — показываем, что осталось
                    if (!target.$item) {
                        window.location.reload();
                    }
                    return;
                }
                toastr.success(response.message);
                if (target.$item) {
                    removeItem(target.$item);
                } else {
                    window.location.reload();
                }
            })
            .fail(function () {
                toastr.error('Не удалось удалить');
            })
            .always(function () {
                pending = null;
                $button.prop('disabled', false);
                $modal.modal('hide');
            });
    });
})();
