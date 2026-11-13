// "В вишлист" в быстром просмотре поста-товара на "Все посты" (parts/post_preview.blade.php, внутри Fancybox).
// Первый клик раскрывает под кнопкой выбор вишлиста (или поле для нового, если вишлистов нет), "Добавить" —
// тот же запрос, что у кнопки на странице поста (PostProductController::toWishlist). Выбор — прямо в превью,
// а не в модалке: Fancybox держит фокус у себя, и поля модалки поверх него не работали бы.
(function () {
    var csrfHeaders = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};

    function failMessage(xhr, fallback) {
        var errors = xhr.responseJSON && xhr.responseJSON.errors;
        if (errors) {
            return Object.values(errors)[0][0];
        }
        return (xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }

    $(document).on('click', '.js-preview-to-wishlist', function () {
        var $button = $(this);
        var $picker = $button.closest('.b-post-preview__product').find('.js-preview-wishlist');
        if (!$picker.prop('hidden')) {
            $picker.prop('hidden', true);
            return;
        }

        $button.prop('disabled', true);
        $.getJSON($button.attr('data-wishlists-action'))
            .done(function (response) {
                var wishlists = response.wishlists || [];
                var $select = $picker.find('.js-preview-wishlist-select').empty();
                wishlists.forEach(function (wishlist) {
                    $select.append($('<option>', {value: wishlist.id, text: wishlist.title}));
                });
                $select.prop('hidden', wishlists.length === 0);
                $picker.find('.js-preview-wishlist-new').prop('hidden', wishlists.length > 0);
                $picker.prop('hidden', false);
            })
            .fail(function (xhr) {
                toastr.error(failMessage(xhr, 'Не удалось загрузить вишлисты'));
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.js-preview-wishlist-save', function () {
        var $save = $(this);
        var $product = $save.closest('.b-post-preview__product');
        var $picker = $save.closest('.js-preview-wishlist');
        var $button = $product.find('.js-preview-to-wishlist');
        var $select = $picker.find('.js-preview-wishlist-select');

        var data = $select.prop('hidden')
            ? {wishlist_title: $picker.find('.js-preview-wishlist-new').val()}
            : {wishlist_id: $select.val()};

        $save.prop('disabled', true);
        $.ajax({url: $button.attr('data-action'), type: 'POST', headers: csrfHeaders, data: data})
            .done(function (response) {
                if (!response || response.status !== true) {
                    toastr.error((response && response.message) || 'Не получилось');
                    $save.prop('disabled', false);
                    return;
                }
                toastr.success(response.message);
                // кнопка -> ссылка "уже там", как после перезагрузки
                var $link = $('<a class="b-post-show__btn b-post-show__btn--done"><i class="fas fa-check"></i> В вишлисте</a>')
                    .attr('href', response.url);
                $button.replaceWith($link);
                $picker.remove();
            })
            .fail(function (xhr) {
                toastr.error(failMessage(xhr, 'Не удалось добавить в вишлист'));
                $save.prop('disabled', false);
            });
    });
})();
