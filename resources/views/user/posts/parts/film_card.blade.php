{{-- Карточка фильма/сериала из post_films — на странице поста и в превью (Fancybox) на "Все посты".
     Текст поста (content) данных фильма не содержит — они показываются только здесь, поэтому после
     правки полей на странице редактирования карточка сразу актуальна.

     @param \App\Models\PostFilm $film
     @param bool $compact — превью: без описания и списка актёров --}}
@php
    /** @var \App\Models\PostFilm $film */
    $compact = $compact ?? false;

    // тип — заголовком секции, поэтому среди бейджей его нет
    $seasons = null;
    if ($film->seasons) {
        // 1 сезон / 2 сезона / 5 сезонов
        $mod100 = $film->seasons % 100;
        $mod10 = $mod100 % 10;
        $seasonsWord = ($mod100 >= 11 && $mod100 <= 19) ? 'сезонов' : ($mod10 === 1 ? 'сезон' : (($mod10 >= 2 && $mod10 <= 4) ? 'сезона' : 'сезонов'));
        $seasons = $film->seasons . ' ' . $seasonsWord;
    }

    // оценка — цветом, как раньше в сгенерированном тексте: 1-4 красный, 5-7 оранжевый, 8-10 зелёный
    $myRatingLevel = null;
    if ($film->my_rating) {
        $myRatingLevel = $film->my_rating >= 8 ? 'high' : ($film->my_rating >= 5 ? 'mid' : 'low');
    }

    $facts = array_filter([
        'Жанр' => $film->genres,
        'Страна' => $film->countries,
        'Режиссёр' => $film->directors,
        'Актёры' => $compact ? null : $film->actors,
    ]);

    $badges = array_filter([$seasons, $film->year, $film->duration, $film->mpaa_rating]);
@endphp
<div class="b-post-show__section b-post-film-card">
    <div class="b-post-show__section-head">
        <i class="fas fa-film"></i>
        <span class="b-post-show__section-label">{{ $film->kind ?: 'Фильм / Сериал' }}</span>
        @if ($film->status)
            <span class="b-post-film-card__status">{{ $film->status }}</span>
        @endif
    </div>

    @if ($film->original_title)
        <div class="b-post-film-card__original">{{ $film->original_title }}</div>
    @endif

    @if ($badges || $film->imdb_rating || $film->my_rating)
        <div class="b-post-film-card__badges">
            @if ($film->imdb_rating)
                <span class="b-post-film-card__imdb" title="Рейтинг IMDb"><b>IMDb</b> {{ number_format($film->imdb_rating, 1) }}</span>
            @endif
            @if ($film->my_rating)
                <span class="b-post-film-card__my-rating b-post-film-card__my-rating--{{ $myRatingLevel }}" title="Моя оценка">
                    <i class="fas fa-star"></i> {{ $film->my_rating }}<small>/10</small>
                </span>
            @endif
            @foreach ($badges as $badge)
                <span class="b-post-film-card__badge">{{ $badge }}</span>
            @endforeach
        </div>
    @endif

    @if ($film->vibes)
        <div class="b-post-film-card__vibes" title="Вайб">
            @foreach ($film->vibes as $vibe)
                <span class="b-post-film-card__vibe">{{ trim(\App\Models\PostFilm::vibeEmoji($vibe) . ' ' . $vibe) }}</span>
            @endforeach
        </div>
    @endif

    @if ($facts)
        <dl class="b-post-film-card__facts">
            @foreach ($facts as $label => $value)
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            @endforeach
        </dl>
    @endif

    @if (!$compact && $film->description)
        <p class="b-post-film-card__description">{{ $film->description }}</p>
    @endif

    @if ($film->pros || $film->cons)
        <div class="b-post-film-card__verdict">
            @if ($film->pros)
                <div>
                    <div class="b-post-film-card__verdict-title">Достоинства</div>
                    <ul class="b-post-film-card__list b-post-film-card__list--pros">
                        @foreach ($film->pros as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($film->cons)
                <div>
                    <div class="b-post-film-card__verdict-title">Недостатки</div>
                    <ul class="b-post-film-card__list b-post-film-card__list--cons">
                        @foreach ($film->cons as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
