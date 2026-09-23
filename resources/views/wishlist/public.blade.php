<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $wishlist->title }}</title>

    <link rel="stylesheet" href="/admin/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/admin/fonts/source-sans-pro/source-sans-pro.css">
    <link rel="stylesheet" href="/wishlist-public.css?v={{ filemtime(public_path('wishlist-public.css')) }}">
</head>
<body>

<div class="wp-page" id="wishlist-public-page"
     data-uuid="{{ $wishlist->uuid }}"
     data-reserve-action-base="{{ url('/wishlist/' . $wishlist->uuid . '/items') }}">

    <div class="wp-head">
        <h1 class="wp-head__title">{{ $wishlist->title }}</h1>
        @if ($wishlist->description)
            <p class="wp-head__description">{{ $wishlist->description }}</p>
        @endif

        @php
            $occasion = collect($occasions)->firstWhere('slug', $wishlist->occasion);
        @endphp
        @if ($occasion || $wishlist->occasion)
            <div class="wp-head__occasion">
                @if ($occasion)
                    <i class="fas {{ $occasion['icon'] }} mr-1"></i>{{ $occasion['title'] }}
                @else
                    {{ $wishlist->occasion }}
                @endif
            </div>
        @endif
    </div>

    <div class="wp-filterbar">
        <label class="wp-filterbar__toggle">
            <input type="checkbox" class="js-wp-filter-reserved">
            Показать зарезервированные
        </label>
        <span class="wp-filterbar__count js-wp-count">Показано {{ $wishlist->items->where('is_reserved', false)->count() }} из {{ $wishlist->items->count() }} желаний</span>
    </div>

    <div class="wp-items js-wp-items">
        @include('user.wishlists.parts.item_cards', ['items' => $wishlist->items, 'isOwner' => false])
    </div>

    @if ($wishlist->items->isEmpty())
        <div class="wp-empty">
            <i class="fas fa-gift wp-empty__icon"></i>
            <p>В этом вишлисте пока нет желаний</p>
        </div>
    @endif
</div>

<div class="modal-reserve-name" id="modal-reserve-name" style="display: none;">
    <div class="modal-reserve-name__backdrop js-reserve-name-cancel"></div>
    <div class="modal-reserve-name__box">
        <h3>Забронировать подарок</h3>
        <p class="text-muted">Необязательно: оставь своё имя, чтобы владелец списка знал, кто бронирует (по желанию)</p>
        <input type="text" id="reserve-name-input" class="form-control" placeholder="Твоё имя (необязательно)">
        <div class="modal-reserve-name__actions">
            <button type="button" class="btn-primary js-reserve-name-confirm">Забронировать</button>
            <button type="button" class="btn-secondary js-reserve-name-cancel">Отмена</button>
        </div>
    </div>
</div>

<div class="modal-reserve-name" id="modal-item-detail" style="display: none;">
    <div class="modal-reserve-name__backdrop js-item-detail-cancel"></div>
    <div class="modal-reserve-name__box wp-item-detail__box">
        <button type="button" class="wp-item-detail__close js-item-detail-cancel">&times;</button>
        <div class="b-item-detail">
            <div class="b-item-detail__thumb js-item-detail-thumb"></div>
            <div class="b-item-detail__info">
                <div class="b-item-detail__title js-item-detail-title"></div>
                <div class="b-item-detail__price js-item-detail-price"></div>
                <p class="b-item-detail__description js-item-detail-description"></p>
            </div>
        </div>
        <a href="#" target="_blank" rel="noopener" class="wp-item-detail__store-link js-item-detail-open-store" style="display: none;">Открыть в магазине →</a>
    </div>
</div>

<div class="modal-reserve-name" id="modal-share-wishlist" style="display: none;">
    <div class="modal-reserve-name__backdrop js-share-cancel"></div>
    <div class="modal-reserve-name__box wp-share__box">
        <button type="button" class="wp-item-detail__close js-share-cancel">&times;</button>
        <h3>Поделиться вишлистом</h3>
        <label>Скопируй ссылку, чтобы поделиться</label>
        <div class="b-share-link">
            <input type="text" class="form-control js-share-link-input" readonly>
            <button type="button" class="b-share-link__btn js-share-link-copy" title="Скопировать">
                <i class="far fa-copy"></i>
            </button>
            <button type="button" class="b-share-link__btn js-share-link-native" title="Поделиться">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <div class="b-share-divider"><span>ИЛИ</span></div>

        <div class="b-share-socials">
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--telegram js-share-social" data-network="telegram" title="Telegram">
                <i class="fab fa-telegram-plane"></i>
            </a>
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--viber js-share-social" data-network="viber" title="Viber">
                <i class="fab fa-viber"></i>
            </a>
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--whatsapp js-share-social" data-network="whatsapp" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--pinterest js-share-social" data-network="pinterest" title="Pinterest">
                <i class="fab fa-pinterest-p"></i>
            </a>
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--facebook js-share-social" data-network="facebook" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" target="_blank" rel="noopener" class="b-share-socials__item b-share-socials__item--twitter js-share-social" data-network="twitter" title="Twitter">
                <i class="fab fa-twitter"></i>
            </a>
        </div>
    </div>
</div>

<script src="/admin/plugins/jquery/jquery.min.js"></script>
<script src="/wishlist-public.js?v={{ filemtime(public_path('wishlist-public.js')) }}"></script>
</body>
</html>
