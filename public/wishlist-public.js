$(function () {
    const page = document.getElementById('wishlist-public-page');
    if (!page) {
        return;
    }

    const reserveActionBase = page.getAttribute('data-reserve-action-base');
    const itemsContainer = document.querySelector('.js-wp-items');
    const countLabel = document.querySelector('.js-wp-count');
    const reservedToggle = document.querySelector('.js-wp-filter-reserved');
    const reserveNameModal = document.getElementById('modal-reserve-name');
    const reserveNameInput = document.getElementById('reserve-name-input');

    let pendingReserveItemId = null;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function updateCount() {
        let allItems = itemsContainer.querySelectorAll('.b-wishlist-item');
        let notReserved = itemsContainer.querySelectorAll('.b-wishlist-item:not(.b-wishlist-item--reserved)');
        countLabel.textContent = 'Показано ' + notReserved.length + ' из ' + allItems.length + ' желаний';
    }

    updateCount();

    if (reservedToggle) {
        reservedToggle.addEventListener('change', function () {
            itemsContainer.classList.toggle('wp-items--show-reserved', reservedToggle.checked);
        });
    }

    function openReserveNameModal(itemId) {
        pendingReserveItemId = itemId;
        reserveNameInput.value = '';
        reserveNameModal.style.display = 'block';
    }

    function closeReserveNameModal() {
        pendingReserveItemId = null;
        reserveNameModal.style.display = 'none';
    }

    document.querySelectorAll('.js-reserve-name-cancel').forEach(function (el) {
        el.addEventListener('click', closeReserveNameModal);
    });

    function sendReserveRequest(itemId, reservedBy) {
        $.ajax({
            type: 'post',
            url: reserveActionBase + '/' + itemId + '/reserve',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                reserved_by: reservedBy || ''
            },
            success: function (response) {
                if (response.status === true) {
                    updateItemCard(response.item);
                }
            },
            error: function () {
                alert('Не удалось изменить бронь. Попробуйте ещё раз.');
            }
        });
    }

    function updateItemCard(item) {
        let card = itemsContainer.querySelector('.b-wishlist-item[data-id="' + item.id + '"]');
        if (!card) {
            return;
        }

        card.classList.toggle('b-wishlist-item--reserved', item.is_reserved);
        card.setAttribute('data-reserved', item.is_reserved ? '1' : '0');

        // "исполнено" (ставит владелец) — более сильный статус, чем "забронировано"
        // (ставит гость); если желание уже отмечено исполненным, бронь на бейдж не влияет
        let thumb = card.querySelector('.b-wishlist-item__thumb');
        let existingBadge = thumb.querySelector('.b-wishlist-item__reserved-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        if (item.is_fulfilled) {
            let badge = document.createElement('div');
            badge.className = 'b-wishlist-item__reserved-badge b-wishlist-item__reserved-badge--fulfilled';
            badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Выполнено';
            thumb.appendChild(badge);
        } else if (item.is_reserved) {
            let badge = document.createElement('div');
            badge.className = 'b-wishlist-item__reserved-badge';
            badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + (item.reserved_by ? 'Забронировал(а) ' + item.reserved_by : 'Забронировано');
            thumb.appendChild(badge);
        }

        let btn = card.querySelector('.js-wishlist-item-reserve');
        if (btn) {
            btn.textContent = item.is_reserved ? 'Отменить бронь' : 'Забронировать';
        }

        updateCount();
    }

    itemsContainer.addEventListener('click', function (event) {
        let btn = event.target.closest('.js-wishlist-item-reserve');
        if (!btn) {
            return;
        }

        let card = btn.closest('.b-wishlist-item');
        let itemId = card.getAttribute('data-id');
        let isReserved = card.getAttribute('data-reserved') === '1';

        if (isReserved) {
            // снять бронь можно сразу, без ввода имени
            sendReserveRequest(itemId, null);
        } else {
            openReserveNameModal(itemId);
        }
    });

    document.querySelector('.js-reserve-name-confirm').addEventListener('click', function () {
        if (!pendingReserveItemId) {
            return;
        }
        let name = reserveNameInput.value.trim();
        sendReserveRequest(pendingReserveItemId, name);
        closeReserveNameModal();
    });

    // --- попап "Поделиться вишлистом" (у желаний нет своих страниц — делимся ссылкой на весь вишлист) ---
    const shareModal = document.getElementById('modal-share-wishlist');
    const shareLinkInput = document.querySelector('.js-share-link-input');
    const shareNativeBtn = document.querySelector('.js-share-link-native');

    function shareText() {
        return 'Мой вишлист: ' + document.querySelector('.wp-head__title').textContent.trim();
    }

    function buildSocialShareUrl(network, url, text) {
        let encodedUrl = encodeURIComponent(url);
        let encodedText = encodeURIComponent(text);

        switch (network) {
            case 'telegram':
                return 'https://t.me/share/url?url=' + encodedUrl + '&text=' + encodedText;
            case 'viber':
                return 'viber://forward?text=' + encodeURIComponent(text + ' ' + url);
            case 'whatsapp':
                return 'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url);
            case 'pinterest':
                return 'https://pinterest.com/pin/create/button/?url=' + encodedUrl + '&description=' + encodedText;
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
            case 'twitter':
                return 'https://twitter.com/intent/tweet?url=' + encodedUrl + '&text=' + encodedText;
            default:
                return url;
        }
    }

    function openShareModal() {
        let url = window.location.href;
        shareLinkInput.value = url;

        document.querySelectorAll('.js-share-social').forEach(function (link) {
            link.setAttribute('href', buildSocialShareUrl(link.getAttribute('data-network'), url, shareText()));
        });

        // нативный "поделиться" доступен не везде (обычно только на мобильных/HTTPS) —
        // если браузер его не поддерживает, просто скрываем кнопку вместо нерабочей
        shareNativeBtn.style.display = navigator.share ? '' : 'none';

        shareModal.style.display = 'block';
    }

    function closeShareModal() {
        shareModal.style.display = 'none';
    }

    document.querySelectorAll('.js-share-cancel').forEach(function (el) {
        el.addEventListener('click', closeShareModal);
    });

    document.querySelector('.js-share-link-copy').addEventListener('click', function () {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(shareLinkInput.value).then(function () {
                shareLinkInput.select();
            }, function () {
                shareLinkInput.select();
            });
        } else {
            shareLinkInput.select();
        }
    });

    shareNativeBtn.addEventListener('click', function () {
        if (navigator.share) {
            navigator.share({title: shareText(), url: shareLinkInput.value}).catch(function () {});
        }
    });

    itemsContainer.addEventListener('click', function (event) {
        if (!event.target.closest('.js-wishlist-item-share')) {
            return;
        }

        openShareModal();
    });

    // --- попап "Подробнее о желании" ---
    const itemDetailModal = document.getElementById('modal-item-detail');

    function closeItemDetailModal() {
        itemDetailModal.style.display = 'none';
    }

    document.querySelectorAll('.js-item-detail-cancel').forEach(function (el) {
        el.addEventListener('click', closeItemDetailModal);
    });

    itemsContainer.addEventListener('click', function (event) {
        if (!event.target.closest('.js-wishlist-item-detail')) {
            return;
        }

        let card = event.target.closest('.b-wishlist-item');

        document.querySelector('.js-item-detail-thumb').style.backgroundImage = card.getAttribute('data-image-url')
            ? 'url(' + card.getAttribute('data-image-url') + ')' : '';
        document.querySelector('.js-item-detail-title').textContent = card.getAttribute('data-title');
        let price = card.getAttribute('data-price');
        document.querySelector('.js-item-detail-price').textContent = price ? Math.round(price) + ' грн' : '';
        document.querySelector('.js-item-detail-description').textContent = card.getAttribute('data-description') || '';

        let storeLink = document.querySelector('.js-item-detail-open-store');
        let url = card.getAttribute('data-url');
        if (url) {
            storeLink.style.display = '';
            storeLink.href = url;
        } else {
            storeLink.style.display = 'none';
        }

        itemDetailModal.style.display = 'block';
    });
});
