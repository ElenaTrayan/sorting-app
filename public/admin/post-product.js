/*
 * Пин "Товар" (post_products).
 *
 * Форма создания и редактирования поста (parts/product_fields.blade.php): "Заполнить по ссылке" — разбор страницы
 * магазина (PostProductController::linkPreview, тот же, что у вишлистов). Заполняет только пустые поля: название
 * пина, цену и валюту, бренд, производителя, страну, артикул, оценку и число отзывов. Картинку товара скачивает
 * через сервер (posts.product.image) и отдаёт форме как перетащенный файл (page-files-drop, admin.js) — только
 * если у поста ещё нет картинок.
 *
 * Страница поста (parts/product_card.blade.php + product_send_modals.blade.php): кнопки "В вишлист",
 * "В список покупок", "На склад". После успеха кнопка становится ссылкой "уже там".
 */
(function () {
    'use strict';

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    // ===== Форма поста: "Заполнить по ссылке" =====
    const fields = document.querySelector('.js-product-fields');
    if (fields) {
        const urlInput = fields.querySelector('.js-product-shop-url');
        const fillButton = fields.querySelector('.js-product-fill-from-url');

        // поле пустое — ставим значение; заполненное руками не трогаем
        function fillIfEmpty(selector, value) {
            const input = document.querySelector(selector);
            if (!input || value === null || value === undefined || value === '' || String(input.value).trim() !== '') {
                return false;
            }
            input.value = value;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
            return true;
        }

        function postHasImages() {
            return !!document.querySelector('.js-upload-image-section .js-images .image, .js-post-images__list .b-post-images__item');
        }

        function addImageFromUrl(imageUrl) {
            if (!imageUrl || postHasImages()) {
                return Promise.resolve(false);
            }
            const proxyUrl = fields.getAttribute('data-image-action') + '?url=' + encodeURIComponent(imageUrl);
            return fetch(proxyUrl, {credentials: 'same-origin'}).then(function (response) {
                if (!response.ok) {
                    throw new Error('image ' + response.status);
                }
                return response.blob();
            }).then(function (blob) {
                if (!/^image\//.test(blob.type)) {
                    throw new Error('not an image');
                }
                let name = decodeURIComponent((imageUrl.split(/[?#]/)[0].split('/').pop() || '')) || 'product';
                if (!/\.[a-z0-9]{2,5}$/i.test(name)) {
                    name += '.' + (blob.type.split('/')[1] || 'jpg').replace('jpeg', 'jpg');
                }
                document.dispatchEvent(new CustomEvent('page-files-drop', {detail: {files: [new File([blob], name, {type: blob.type})]}}));
                return true;
            }).catch(function () {
                return false;
            });
        }

        if (fillButton && urlInput) {
            fillButton.addEventListener('click', function () {
                let url = urlInput.value.trim();
                if (!url) {
                    toastr.warning('Вставьте ссылку на товар');
                    urlInput.focus();
                    return;
                }
                if (!/^https?:\/\//i.test(url)) {
                    url = 'https://' + url;
                    urlInput.value = url;
                }

                // на форме создания эту кнопку нажимает "Спарсить информацию" вверху формы (admin.js) — показываем
                // загрузку и на ней, своя кнопка там скрыта
                const topParseButton = document.getElementById('get-parsed-post-info');
                fillButton.classList.add('is-loading');
                if (topParseButton) {
                    topParseButton.classList.add('is-loading');
                }
                $.ajax({
                    type: 'post',
                    url: fields.getAttribute('data-preview-action'),
                    headers: {'X-CSRF-TOKEN': csrf()},
                    data: {url: url},
                    success: function (response) {
                        if (!response || !response.status) {
                            toastr.warning((response && response.message) || 'Не удалось найти данные о товаре');
                            return;
                        }
                        const filled = [];
                        if (fillIfEmpty('#title', response.title)) filled.push('название');
                        if (fillIfEmpty('#product-price', response.price)) {
                            filled.push('цена');
                            const currency = fields.querySelector('[name="product-currency"]');
                            if (currency && response.currency && currency.querySelector('option[value="' + response.currency + '"]')) {
                                currency.value = response.currency;
                            }
                        }
                        if (fillIfEmpty('#product-brand', response.brand)) filled.push('бренд');
                        if (fillIfEmpty('#product-manufacturer', response.manufacturer)) filled.push('производитель');
                        if (fillIfEmpty('#product-country', response.country)) filled.push('страна');
                        if (fillIfEmpty('#product-article', response.article)) filled.push('артикул');
                        if (fillIfEmpty('#product-reviews-rating', response.rating)) filled.push('оценка покупателей');
                        if (fillIfEmpty('#product-reviews-count', response.rating_count)) filled.push('число отзывов');
                        // ссылка на отзывы — та же страница, если их там посчитали
                        if (response.rating !== null && response.rating !== undefined) {
                            fillIfEmpty('#product-reviews-url', url);
                        }

                        addImageFromUrl(response.image_url).then(function (imageAdded) {
                            if (imageAdded) {
                                filled.push('картинка');
                            }
                            if (filled.length) {
                                toastr.success('Заполнено: ' + filled.join(', '));
                            } else {
                                toastr.info('Новых данных нет — нужные поля уже заполнены или магазин их не отдаёт');
                            }
                        });
                    },
                    error: function () {
                        toastr.error('Не удалось открыть ссылку');
                    },
                    complete: function () {
                        fillButton.classList.remove('is-loading');
                        if (topParseButton) {
                            topParseButton.classList.remove('is-loading');
                        }
                    }
                });
            });
        }
    }

    // ===== Страница поста: в вишлист / список покупок / на склад =====
    const card = document.querySelector('.js-post-product-card');
    if (!card) {
        return;
    }

    const wishlistModal = $('#modal-product-to-wishlist');
    const stockModal = $('#modal-product-to-stock');
    let optionsPromise = null;

    function loadOptions() {
        if (!optionsPromise) {
            optionsPromise = $.getJSON(wishlistModal.attr('data-options-action')).fail(function () {
                optionsPromise = null;
            });
        }
        return optionsPromise;
    }

    // кнопка -> ссылка "уже там" (как после перезагрузки страницы)
    function markDone(button, url, label) {
        const link = document.createElement('a');
        link.href = url;
        link.className = 'b-post-show__btn b-post-show__btn--done';
        link.innerHTML = '<i class="fas fa-check"></i> ';
        link.appendChild(document.createTextNode(label));
        button.replaceWith(link);
    }

    function send(button, data, doneLabel, modal) {
        button.disabled = true;
        $.ajax({
            type: 'post',
            url: button.getAttribute('data-action'),
            headers: {'X-CSRF-TOKEN': csrf()},
            data: data || {},
            success: function (response) {
                if (!response || response.status !== true) {
                    toastr.error((response && response.message) || 'Не получилось');
                    button.disabled = false;
                    return;
                }
                toastr.success(response.message);
                if (modal) {
                    modal.modal('hide');
                }
                markDone(button, response.url, doneLabel);
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                toastr.error(errors ? Object.values(errors)[0][0] : 'Не получилось');
                button.disabled = false;
            }
        });
    }

    let wishlistButton = null;
    card.addEventListener('click', function (event) {
        const toWishlist = event.target.closest('.js-product-to-wishlist');
        const toShopping = event.target.closest('.js-product-to-shopping-list');
        const toStock = event.target.closest('.js-product-to-stock');

        if (toShopping) {
            send(toShopping, {}, 'В списке покупок');
        }

        if (toWishlist) {
            wishlistButton = toWishlist;
            loadOptions().then(function (options) {
                const select = wishlistModal.find('.js-product-wishlist-select').empty();
                (options.wishlists || []).forEach(function (wishlist) {
                    select.append($('<option>', {value: wishlist.id, text: wishlist.title}));
                });
                const hasWishlists = (options.wishlists || []).length > 0;
                wishlistModal.find('.js-product-wishlist-select-group').prop('hidden', !hasWishlists);
                wishlistModal.find('.js-product-wishlist-new-group').prop('hidden', hasWishlists);
                wishlistModal.modal('show');
            });
        }

        if (toStock) {
            loadOptions().then(function (options) {
                const select = stockModal.find('.js-product-stock-location').empty();
                (options.locations || []).forEach(function (location) {
                    select.append($('<option>', {value: location.id, text: location.name}));
                });
                if (options.suggested_location_id) {
                    select.val(String(options.suggested_location_id));
                }
                stockModal.data('button', toStock).modal('show');
            });
        }
    });

    wishlistModal.on('click', '.js-product-wishlist-save', function () {
        if (!wishlistButton) {
            return;
        }
        const select = wishlistModal.find('.js-product-wishlist-select');
        const data = wishlistModal.find('.js-product-wishlist-select-group').prop('hidden')
            ? {wishlist_title: wishlistModal.find('.js-product-wishlist-new').val()}
            : {wishlist_id: select.val()};
        send(wishlistButton, data, 'В вишлисте', wishlistModal);
    });

    stockModal.on('click', '.js-product-stock-save', function () {
        const button = stockModal.data('button');
        if (!button) {
            return;
        }
        send(button, {
            location_id: stockModal.find('.js-product-stock-location').val(),
            quantity: stockModal.find('.js-product-stock-quantity').val(),
        }, 'На складе', stockModal);
    });
})();
