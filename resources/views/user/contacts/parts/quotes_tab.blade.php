{{--
    Вкладка "Высказывания". Высказывания — посты контакта с хештегом "высказывание": скрин(ы) — картинки
    поста, текст — его текст (ContactController::quotesFeed(), $quotesFeed из show.blade.php). Источник —
    по хештегу поста (QUOTE_PLATFORMS), дата — дата пина, без неё — когда пост добавлен.
    "Добавить" создаёт такой пост (ContactController::createQuotePost()), "Удалить" — убирает пост в корзину.
    Фильтры и сортировка — на JS по data-атрибутам карточек (public/admin/admin.js).
--}}
@php
    $platforms = $quotesFeed->countBy('platform')->map(function ($count, $title) {
        return ['slug' => \Illuminate\Support\Str::slug($title), 'title' => $title, 'count' => $count];
    })->values();

    $contentTypeCounts = $quotesFeed->countBy('content_type');
    $contentTypes = collect(['text' => 'Текст', 'screenshot' => 'Скриншот'])
        ->map(function ($title, $slug) use ($contentTypeCounts) {
            return ['slug' => $slug, 'title' => $title, 'count' => $contentTypeCounts->get($slug, 0)];
        })
        ->values();
@endphp

<div class="b-quotes-toolbar">
    <div>
        <h3 class="b-quotes-toolbar__title"><i class="fas fa-quote-right mr-2"></i>Высказывания</h3>
        <div class="b-quotes-toolbar__count js-quotes-count">Найдено {{ $quotesFeed->count() }}</div>
    </div>
    <div class="b-quotes-toolbar__right">
        <button type="button" class="btn btn-sm btn-outline-info js-quote-add-open"><i class="fas fa-plus mr-1"></i>Добавить</button>

        <span class="b-wishlist-toolbar__divider"></span>

        <div class="b-wishlist-toolbar__dropdown js-quotes-sort-dropdown">
            <button type="button" class="b-wishlist-toolbar__btn js-quotes-sort-toggle">
                <span class="js-quotes-sort-label">Сначала новые</span> <i class="fas fa-caret-down ml-1"></i>
            </button>
            <div class="b-wishlist-toolbar__menu js-quotes-sort-menu">
                <button type="button" class="b-wishlist-toolbar__menu-item b-wishlist-toolbar__menu-item--active js-quotes-sort-option" data-sort="date-desc">Сначала новые</button>
                <button type="button" class="b-wishlist-toolbar__menu-item js-quotes-sort-option" data-sort="date-asc">Сначала старые</button>
            </div>
        </div>

        <button type="button" class="b-wishlist-toolbar__btn js-quotes-filter-toggle"><i class="fas fa-filter mr-1"></i> Фильтр</button>
    </div>
</div>

@if ($quotesFeed->isEmpty())
    <p class="text-muted js-quotes-empty">Пока нет ни одного высказывания. Добавьте его кнопкой «Добавить» или поставьте посту про этого человека хештег «высказывание».</p>
@else
    <div class="b-quotes-feed js-quotes-feed">
        @foreach ($quotesFeed as $row)
            @php($post = $row['post'])
            <div class="b-quotes-card js-quote-card"
                 data-id="{{ $post->id }}"
                 data-platform="{{ \Illuminate\Support\Str::slug($row['platform']) }}"
                 data-content-type="{{ $row['content_type'] }}"
                 data-timestamp="{{ $row['timestamp'] }}"
                 data-search="{{ \Illuminate\Support\Str::lower($row['text'] . ' ' . ($row['comment'] ?? '')) }}">
                <div class="b-quotes-card__header">
                    <span class="b-quotes-card__platform-icon" style="background-color: {{ $row['color'] }}"><i class="{{ $row['icon'] }}"></i></span>
                    <span class="b-quotes-card__platform-name">{{ $row['platform'] }}</span>
                    <span class="b-quotes-card__date">{{ $row['date_label'] }}</span>

                    <div class="b-quotes-card__menu js-quote-card-menu">
                        <button type="button" class="b-quotes-card__menu-toggle js-quote-card-menu-toggle" title="Ещё">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="b-quotes-card__menu-list js-quote-card-menu-list">
                            @if ($row['original_url'])
                                <a href="{{ $row['original_url'] }}" target="_blank" rel="noopener" class="b-quotes-card__menu-item">
                                    <i class="fas fa-external-link-alt mr-2"></i>Открыть оригинал
                                </a>
                            @endif
                            <a href="{{ route('posts.show', $post->id) }}" data-fancybox="posts-gallery" class="b-quotes-card__menu-item">
                                <i class="far fa-eye mr-2"></i>Открыть пост
                            </a>
                            <a href="{{ route('posts.edit', $post->id) }}" class="b-quotes-card__menu-item">
                                <i class="fas fa-pencil-alt mr-2"></i>Редактировать
                            </a>
                            <button type="button" class="b-quotes-card__menu-item b-quotes-card__menu-item--danger js-contact-list-remove" data-base-url="{{ url('/cabinet/posts') }}">
                                <i class="fas fa-trash-alt mr-2"></i>Удалить
                            </button>
                        </div>
                    </div>
                </div>

                @if ($row['images']->isNotEmpty())
                    <div class="b-quotes-card__banner">
                        <a href="{{ $row['images']->first() }}" data-fancybox="quote-images-{{ $post->id }}" class="b-quotes-card__banner-link" title="Открыть изображение">
                            <img src="{{ $row['images']->first() }}" alt="" class="b-quotes-card__banner-image">
                            <span class="b-quotes-card__banner-expand"><i class="fas fa-expand"></i></span>
                            @if ($row['images']->count() > 1)
                                <span class="b-quotes-card__real-image-more">+{{ $row['images']->count() - 1 }}</span>
                            @endif
                        </a>
                        @foreach ($row['images']->skip(1) as $extraImage)
                            <a href="{{ $extraImage }}" data-fancybox="quote-images-{{ $post->id }}" class="b-quotes-card__real-image-extra"></a>
                        @endforeach
                    </div>
                @endif

                @if ($row['quote_html'] !== '')
                    {{-- текст поста: тот же HTML, что на странице поста --}}
                    <div class="b-quotes-card__text">{!! $row['quote_html'] !!}</div>
                @endif

                @if ($row['comment'])
                    <div class="b-quotes-card__comment"><i class="fas fa-comment-dots mr-1"></i>{{ $row['comment'] }}</div>
                @endif

                @if ($row['hashtags']->isNotEmpty())
                    <div class="b-quotes-card__tags">
                        @foreach ($row['hashtags'] as $tag)
                            <span class="b-contact-card__tag">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <p class="text-muted js-quotes-no-match" style="display: none;">Ничего не найдено по выбранным фильтрам.</p>
@endif

@include('user.contacts.parts.quotes_filter_panel', [
    'platforms' => $platforms,
    'contentTypes' => $contentTypes,
])

@include('user.contacts.parts.quote_add_modal')
