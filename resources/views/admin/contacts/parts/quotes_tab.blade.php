{{--
    Вкладка "Высказывания". $contact передаётся из show.blade.php.

    Реальные данные: сам текст, дата, добавление/удаление (contact_quotes).
    Подставные (см. ContactQuote::feedMeta()): источник/платформа (берётся из реальных
    соцсетей контакта по кругу, если они есть), тема, тональность, хэштеги, реакции, тип
    контента — под них нет отдельных полей в БД. Фильтры по всем этим полям при этом
    настоящие: они работают на JS по data-атрибутам карточек (public/admin/admin.js).
    Кнопка "закладка" в футере карточки — тоже чисто декоративная (как лайки/комменты),
    просто переключает иконку на JS, никуда не сохраняется.
--}}
@php
    $quotesFeed = $contact->quotes->reverse()->values()->map(function ($quote) use ($contact) {
        return ['quote' => $quote, 'meta' => $quote->feedMeta($contact->socialLinks)];
    });

    $countBy = function ($key) use ($quotesFeed) {
        return $quotesFeed->countBy(function ($row) use ($key) {
            return $row['meta'][$key];
        });
    };

    $platformCounts = $countBy('platform');
    $platforms = $platformCounts->map(function ($count, $title) {
        return ['slug' => \Illuminate\Support\Str::slug($title), 'title' => $title, 'count' => $count];
    })->values();

    $contentTypeCounts = $countBy('content_type');
    $contentTypes = collect(['text' => 'Текст', 'screenshot' => 'Скриншот', 'video' => 'Видео'])
        ->map(function ($title, $slug) use ($contentTypeCounts) {
            return ['slug' => $slug, 'title' => $title, 'count' => $contentTypeCounts->get($slug, 0)];
        })
        ->values();

    $sentimentCounts = $countBy('sentiment');
    $sentiments = collect([
        'positive' => ['title' => 'Позитивная', 'emoji' => '😊'],
        'neutral' => ['title' => 'Нейтральная', 'emoji' => '😐'],
        'negative' => ['title' => 'Негативная', 'emoji' => '😞'],
    ])->map(function ($row, $slug) use ($sentimentCounts) {
        return ['slug' => $slug, 'title' => $row['title'], 'emoji' => $row['emoji'], 'count' => $sentimentCounts->get($slug, 0)];
    })->values();

    $topicCounts = $countBy('topic');
    $topics = $topicCounts->map(function ($count, $title) {
        return ['slug' => \Illuminate\Support\Str::slug($title), 'title' => $title, 'count' => $count];
    })->values();
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
    <p class="text-muted js-quotes-empty">Пока нет ни одного высказывания.</p>
@else
    <div class="b-quotes-feed js-quotes-feed">
        @foreach ($quotesFeed as $row)
            @php
                [$quote, $meta] = [$row['quote'], $row['meta']];
                $displayDate = $quote->display_date;
                // время показываем только когда это реально время создания записи (нет
                // occurred_at) — у occurred_at нет времени (это date-поле), выдумывать его не будем
                $displayTime = $quote->occurred_at ? null : $quote->created_at->format('H:i');
                $dateLabel = $displayDate->locale('ru')->translatedFormat('d F Y') . ($displayTime ? ', ' . $displayTime : '');
            @endphp
            <div class="b-quotes-card js-quote-card"
                 data-id="{{ $quote->id }}"
                 data-platform="{{ \Illuminate\Support\Str::slug($meta['platform']) }}"
                 data-content-type="{{ $meta['content_type'] }}"
                 data-sentiment="{{ $meta['sentiment'] }}"
                 data-topic="{{ \Illuminate\Support\Str::slug($meta['topic']) }}"
                 data-timestamp="{{ $quote->display_date->timestamp }}"
                 data-search="{{ \Illuminate\Support\Str::lower($quote->quote . ' ' . ($quote->comment ?? '')) }}">
                <div class="b-quotes-card__header">
                    <span class="b-quotes-card__platform-icon" style="background-color: {{ $meta['color'] }}"><i class="{{ $meta['icon'] }}"></i></span>
                    <span class="b-quotes-card__platform-name">{{ $meta['platform'] }}</span>
                    @if ($meta['handle'])<span class="text-muted small">{{ $meta['handle'] }}</span>@endif
                    <span class="b-quotes-card__date">{{ $dateLabel }}</span>

                    <div class="b-quotes-card__menu js-quote-card-menu">
                        <button type="button" class="b-quotes-card__menu-toggle js-quote-card-menu-toggle" title="Ещё">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="b-quotes-card__menu-list js-quote-card-menu-list">
                            @if ($meta['original_url'])
                                <a href="{{ $meta['original_url'] }}" target="_blank" rel="noopener" class="b-quotes-card__menu-item">
                                    <i class="fas fa-external-link-alt mr-2"></i>Открыть оригинал
                                </a>
                            @endif
                            <button type="button" class="b-quotes-card__menu-item b-quotes-card__menu-item--danger js-contact-list-remove" data-base-url="{{ url('/admin_panel/contact-quotes') }}">
                                <i class="fas fa-trash-alt mr-2"></i>Удалить
                            </button>
                        </div>
                    </div>
                </div>

                @if ($meta['real_image_url'])
                    <div class="b-quotes-card__banner">
                        <a href="{{ $meta['real_image_url'] }}" data-fancybox="quote-images-{{ $quote->id }}" class="b-quotes-card__banner-link" title="Открыть изображение">
                            <img src="{{ $meta['real_image_url'] }}" alt="" class="b-quotes-card__banner-image">
                            <span class="b-quotes-card__banner-expand"><i class="fas fa-expand"></i></span>
                            @if ($meta['real_image_count'] > 1)
                                <span class="b-quotes-card__real-image-more">+{{ $meta['real_image_count'] - 1 }}</span>
                            @endif
                        </a>
                        @foreach ($quote->images->skip(1) as $extraImage)
                            <a href="{{ $extraImage->image_url }}" data-fancybox="quote-images-{{ $quote->id }}" class="b-quotes-card__real-image-extra"></a>
                        @endforeach
                    </div>
                    <p class="b-quotes-card__text">«{{ $quote->quote }}»</p>
                @elseif ($meta['content_type'] === 'screenshot')
                    <div class="b-quotes-card__banner b-quotes-card__banner--mock">{{ $quote->quote }}</div>
                    <p class="b-quotes-card__text">«{{ $quote->quote }}»</p>
                @elseif ($meta['content_type'] === 'video')
                    <div class="b-quotes-card__banner b-quotes-card__banner--video b-contact-place-card__photo--{{ $meta['media_variant'] }}">
                        <i class="fas fa-play-circle"></i>
                        <span class="b-quotes-card__media-duration">{{ $meta['video_duration'] }}</span>
                    </div>
                    <p class="b-quotes-card__text">«{{ $quote->quote }}»</p>
                @else
                    <p class="b-quotes-card__text">«{{ $quote->quote }}»</p>
                @endif

                @if ($quote->comment)
                    <div class="b-quotes-card__comment"><i class="fas fa-comment-dots mr-1"></i>{{ $quote->comment }}</div>
                @endif

                <div class="b-quotes-card__tags">
                    @foreach ($meta['hashtags'] as $tag)
                        <span class="b-contact-card__tag">#{{ $tag }}</span>
                    @endforeach
                </div>

                <div class="b-quotes-card__footer">
                    <div class="b-quotes-card__reactions">
                        <span><i class="far fa-heart mr-1"></i>{{ $meta['likes'] }}</span>
                        <span><i class="far fa-comment mr-1"></i>{{ $meta['comments'] }}</span>
                    </div>
                    <button type="button" class="b-quotes-card__bookmark js-quote-card-bookmark" title="В закладки">
                        <i class="far fa-bookmark"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
    <p class="text-muted js-quotes-no-match" style="display: none;">Ничего не найдено по выбранным фильтрам.</p>
@endif

@include('admin.contacts.parts.quotes_filter_panel', [
    'platforms' => $platforms,
    'contentTypes' => $contentTypes,
    'sentiments' => $sentiments,
    'topics' => $topics,
])

@include('admin.contacts.parts.quote_add_modal')
