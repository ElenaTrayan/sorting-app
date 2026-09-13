{{--
    Поиск — общее содержимое, используется в двух местах:
    - в шапке на странице "Все посты" (admin_layout.blade.php, всегда видно, как и раньше);
    - в поп-апе "Поиск" (modal-search-global, тоже в admin_layout.blade.php) на всех
      остальных страницах — открывается кнопкой "Поиск" в меню слева.

    Поиск по хэштегам зависит от текущей страницы: на "Все посты" фильтрует грид постов
    на месте (#posts-index .b-cards .grid, AJAX), на "База контактов" — фильтрует список
    контактов на месте (#contacts-index, на JS, без бэкенда — все контакты и так уже
    в DOM). На остальных страницах вкладки "по хэштегам" и "по картинке" вообще не
    показываются (там нет ни постов, ни контактов, которые можно так фильтровать) —
    остаётся только вкладка "по тексту" (готовая вёрстка без бэкенда за ней).

    Иконки/подзаголовки у вкладок, шапка "Хештеги" с "Очистить все" и кнопка "Сбросить" —
    разметка под дизайн поп-апа (см. #modal-search-global в admin_layout.blade.php);
    в шапке на странице "Все посты" она скрыта по умолчанию (см. admin-panel.css), чтобы
    там всё осталось как было — только в модалке эти элементы показываются и стилизуются.
--}}
@php
    $searchContext = request()->routeIs('posts.index')
        ? 'posts'
        : (request()->routeIs('contacts.*') ? 'contacts' : 'other');
    $showAllTabs = in_array($searchContext, ['posts', 'contacts']);
    $hashtagsEntityLabel = $searchContext === 'contacts' ? 'контактов' : 'постов';
@endphp
<div class="b-tabs">
    <div class="b-tabs__header" @if (!$showAllTabs) style="display: none;" @endif>
        @if ($showAllTabs)
            <button class="b-tabs__button js-b-tabs__button b-tabs__button--active" data-tab="hashtags">
                <span class="b-tabs__button-icon"><i class="fas fa-hashtag"></i></span>
                <span class="b-tabs__button-text">
                    <span class="b-tabs__button-title">Поиск по хэштегам</span>
                    <span class="b-tabs__button-subtitle">Найдите по темам и тегам</span>
                </span>
            </button>
        @endif
        <button class="b-tabs__button js-b-tabs__button @if (!$showAllTabs) b-tabs__button--active @endif" data-tab="text">
            <span class="b-tabs__button-icon"><i class="fas fa-file-alt"></i></span>
            <span class="b-tabs__button-text">
                <span class="b-tabs__button-title">Поиск по тексту</span>
                <span class="b-tabs__button-subtitle">Ищите по словам и фразам</span>
            </span>
        </button>
        @if ($showAllTabs)
            <button class="b-tabs__button js-b-tabs__button" data-tab="image">
                <span class="b-tabs__button-icon"><i class="fas fa-image"></i></span>
                <span class="b-tabs__button-text">
                    <span class="b-tabs__button-title">Поиск по картинке</span>
                    <span class="b-tabs__button-subtitle">Загрузите изображение</span>
                </span>
            </button>
        @endif
    </div>
    <div class="b-tabs__content">
        @if ($showAllTabs)
            <!-- Поиск по хэштегам -->
            <div class="b-tabs__panel js-b-tabs__panel" data-tab="hashtags" style="display: block;">
                <div class="b-hashtags-section-head">
                    <span class="b-hashtags-section-head__title">Хештеги <i class="fas fa-info-circle" title="{{ ucfirst($hashtagsEntityLabel) }}, у которых есть все хэштеги из поля «Введите хэштег»"></i></span>
                    <button type="button" class="b-hashtags-section-head__clear js-b-tags__clear-all"><i class="fas fa-trash-alt mr-1"></i>Очистить все</button>
                </div>
                <div class="b-search-by-hashtags" style="display: flex;justify-content: space-between;align-items: self-start;">
                    <div class="b-search-by-hashtags__fields" style="margin: 0 7px 0 0; width: 100%; position: relative;">

                        <div class="b-search-by-hashtags__group" style="margin: 0px 0 5px;display: flex; justify-content: space-between; width: 100%;">
                            <button type="button" class="b-tags__clear js-b-tags__clear" title="Очистить все хэштеги">×</button>
                            <div class="b-search-by-hashtags__field js-b-search-by-hashtags__field" data-action="{{ route('search.hashtag') }}">
                                <!-- Поле для ввода хэштегов -->
                                <div class="b-tags">
                                    <div class="b-tags__container js-b-tags__container" id="include-tags-container"><!-- Выбранные хэштеги будут появляться здесь -->
                                    </div>
                                    <input type="text" class="b-tags__input js-b-tags__input" id="include-tags-input" placeholder="Введите хэштег...">
                                </div>
                                <ul class="b-tags__dropdown" id="include-tags-dropdown"></ul>
                            </div>
                        </div>

                        <div class="b-search-by-hashtags__toggle-group" style="display: flex;flex-flow: row wrap;align-items: center; margin: 0 0 10px;">
                            <span class="b-search-by-hashtags__toggle js-b-search-by-hashtags__toggle">исключить хэштег из поиска</span>
                            <ul class="b-search-by-hashtags__popular-tags js-b-search-by-hashtags__popular-tags"
                                data-action="{{ route('search.related-hashtags') }}"
                                style="display: none;">
                                <span class="b-search-by-hashtags__popular-title">Связанные:</span>
                            </ul>
                        </div>

                        <div class="b-search-by-hashtags__exclude-container js-b-search-by-hashtags__exclude-container">
                            <button type="button" class="b-search-by-hashtags__exclude-close js-b-search-by-hashtags__exclude-hide" title="Скрыть блок исключений">×</button>
                            <div class="b-search-by-hashtags__exclude-group">
                                <div class="b-search-by-hashtags__exclude js-b-search-by-hashtags__exclude" data-action="{{ route('search.hashtag') }}">
                                    <div class="b-tags">
                                        <div class="b-tags__container js-b-tags-exclude__container"
                                             id="exclude-tags-container">
                                            <!-- Исключённые хэштеги будут появляться здесь -->
                                        </div>
                                        <input type="text" class="b-tags__input js-b-tags-exclude__input"
                                               id="exclude-tags-input"
                                               placeholder="Исключить хэштег из поиска..."/>
                                    </div>
                                    <ul class="b-tags__dropdown" id="exclude-tags-dropdown"></ul>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="b-hashtags-section-foot">
                        <button type="button" class="b-hashtags-section-foot__reset js-b-search-reset"><i class="fas fa-sync-alt mr-1"></i>Сбросить</button>
                        <button type="button" class="btn btn-block btn-info b-search-by-hashtags__button js-b-search-by-hashtags__button" data-action="{{ route('search.posts-by-hashtags') }}" data-posts-url="{{ route('posts.index') }}"><i class="fas fa-search mr-1"></i>Найти</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Поиск по тексту -->
        <div class="b-tabs__panel js-b-tabs__panel" data-tab="text" @if ($showAllTabs) style="display: none;" @else style="display: block;" @endif>
            <div class="b-search-text">
                <input type="text" class="b-search-text__input" placeholder="Введите текст для поиска...">
                <button class="b-search-text__button">Поиск</button>
            </div>
        </div>

        @if ($showAllTabs)
            <!-- Поиск по картинке -->
            <div class="b-tabs__panel js-b-tabs__panel" data-tab="image" style="display: none;">
                <div class="b-search-image">
                    <input type="file" class="b-search-image__input" accept="image/*">
                    <button class="b-search-image__button">Загрузить и искать</button>
                </div>
            </div>
        @endif
    </div>
</div>
