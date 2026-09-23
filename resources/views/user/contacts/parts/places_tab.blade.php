{{--
    Вкладка "Места". $contact передаётся из show.blade.php.

    Целиком подставные данные (см. Contact::getPlacesAttribute()) — под места, которые
    посещает контакт, нет отдельной таблицы в БД. Категории "Места отдыха"/"Рабочие места"
    частично переиспользуют Contact::demo (те же места, что и на вкладках "Обзор"/"Работа
    и образование"). Карта — декоративная (нет координат мест, только город/страна текстом),
    поэтому не интерактивна. Категории слева, сортировка и "Показать ещё" — настоящие,
    работают на JS. Добавления мест нет — не с чем: нет таблицы.
--}}
@php
    $places = $contact->places;
    $allItems = collect($places['categories'])->flatMap(function ($category) {
        return $category['items'];
    })->sortByDesc('last_visit_ts')->values();
    $visibleLimit = 6;
@endphp

<div class="b-quotes-toolbar">
    <div>
        <h3 class="b-quotes-toolbar__title"><i class="fas fa-map-marker-alt mr-2"></i>Места</h3>
        <div class="b-quotes-toolbar__count">Места, связанные с этим контактом — часто посещаемые, любимые и рабочие</div>
    </div>
    <div class="b-quotes-toolbar__right">
        <div class="b-wishlist-toolbar__dropdown js-places-sort-dropdown">
            <button type="button" class="b-wishlist-toolbar__btn js-places-sort-toggle">
                Сортировка: <span class="js-places-sort-label">по дате посещения</span> <i class="fas fa-caret-down ml-1"></i>
            </button>
            <div class="b-wishlist-toolbar__menu js-places-sort-menu">
                <button type="button" class="b-wishlist-toolbar__menu-item b-wishlist-toolbar__menu-item--active js-places-sort-option" data-sort="date">по дате посещения</button>
                <button type="button" class="b-wishlist-toolbar__menu-item js-places-sort-option" data-sort="name">по названию</button>
                <button type="button" class="b-wishlist-toolbar__menu-item js-places-sort-option" data-sort="city">по городу</button>
            </div>
        </div>
    </div>
</div>

<div class="b-places-layout">
    <div class="b-places-main">
        @include('user.contacts.parts.places_map', ['contact' => $contact, 'items' => $allItems])

        <div class="b-facts-section-title">
            <span class="js-places-category-label">Все места</span> <span class="text-muted js-places-count">({{ $allItems->count() }})</span>
        </div>

        <p class="text-muted js-places-no-match" style="display: none;">Ничего не найдено в этой категории.</p>

        <div class="b-places-grid js-places-grid">
            @foreach ($allItems as $i => $item)
                @include('user.contacts.parts.places_card', ['item' => $item, 'extra' => $i >= $visibleLimit])
            @endforeach
        </div>

        @if ($allItems->count() > $visibleLimit)
            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-secondary js-places-show-more">Показать ещё места <i class="fas fa-chevron-down ml-1"></i></button>
            </div>
        @endif
    </div>

    <div class="b-places-sidebar">
        <div class="b-facts-card">
            <div class="b-facts-card__header" style="background: #f4f6f9;">
                <span class="b-facts-card__icon" style="color: #495057;"><i class="fas fa-layer-group"></i></span>
                <h3>Категории</h3>
            </div>
            <div class="b-facts-card__body b-places-categories">
                <button type="button" class="b-places-category-item is-active js-places-category-btn" data-category="">
                    <span><i class="fas fa-globe-europe mr-2"></i>Все места</span>
                    <span class="text-muted">{{ $allItems->count() }}</span>
                </button>
                @foreach ($places['categories'] as $slug => $category)
                    <button type="button" class="b-places-category-item js-places-category-btn" data-category="{{ $slug }}">
                        <span><i class="{{ $category['icon'] }} mr-2" style="color: {{ $category['color'] }};"></i>{{ $category['title'] }}</span>
                        <span class="text-muted">{{ count($category['items']) }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="b-facts-card">
            <div class="b-facts-card__header" style="background: #eaf1ff;">
                <span class="b-facts-card__icon" style="color: #2563eb;"><i class="fas fa-chart-bar"></i></span>
                <h3>Статистика</h3>
            </div>
            <ul class="b-contact-info-list b-facts-card__body">
                <li><span class="b-contact-info-list__label"><i class="fas fa-map-marker-alt mr-2"></i>Всего мест</span><span>{{ $places['stats']['total'] }}</span></li>
                <li><span class="b-contact-info-list__label"><i class="fas fa-city mr-2"></i>Города</span><span>{{ $places['stats']['cities'] }}</span></li>
                <li><span class="b-contact-info-list__label"><i class="fas fa-flag mr-2"></i>Страны</span><span>{{ $places['stats']['countries'] }}</span></li>
            </ul>
        </div>

        <div class="b-facts-card">
            <div class="b-facts-card__header" style="background: #fff4e0;">
                <span class="b-facts-card__icon" style="color: #b5720a;"><i class="fas fa-clock"></i></span>
                <h3>Последние посещения</h3>
            </div>
            <div class="b-facts-card__body">
                @foreach ($places['recent'] as $item)
                    <div class="b-places-recent-row">
                        <div class="b-places-recent-row__photo b-contact-place-card__photo--{{ $item['photo'] }}"></div>
                        <div class="min-width-0">
                            <div class="b-relations-row__name">{{ $item['name'] }}</div>
                            <div class="text-muted small">{{ $item['city'] }} · {{ $item['last_visit'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
