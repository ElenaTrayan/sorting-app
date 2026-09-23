{{-- Краткая сводка фильма/сериала (post_films) для карточки в сетке "Все посты" (post_items.blade.php).
     Полная карточка — parts/film_card.blade.php (страница поста и превью). Бейджи IMDb/оценки — те же классы.

     @param \App\Models\PostFilm $film --}}
@php
    /** @var \App\Models\PostFilm $film */
    $meta = array_filter([$film->kind, $film->year, $film->duration]);
    $myRatingLevel = $film->my_rating ? ($film->my_rating >= 8 ? 'high' : ($film->my_rating >= 5 ? 'mid' : 'low')) : null;
@endphp
<div class="b-card__film">
    @if ($meta)
        <div class="b-card__film-meta"><i class="fas fa-film"></i> {{ implode(' · ', $meta) }}</div>
    @endif

    @if ($film->imdb_rating || $film->my_rating || $film->status)
        <div class="b-card__film-badges">
            @if ($film->imdb_rating)
                <span class="b-post-film-card__imdb" title="Рейтинг IMDb"><b>IMDb</b> {{ number_format($film->imdb_rating, 1) }}</span>
            @endif
            @if ($film->my_rating)
                <span class="b-post-film-card__my-rating b-post-film-card__my-rating--{{ $myRatingLevel }}" title="Моя оценка">
                    <i class="fas fa-star"></i> {{ $film->my_rating }}<small>/10</small>
                </span>
            @endif
            @if ($film->status)
                <span class="b-card__film-status">{{ $film->status }}</span>
            @endif
        </div>
    @endif

    @if ($film->genres)
        <div class="b-card__film-genres">{{ $film->genres }}</div>
    @endif

    {{-- карточка в сетке узкая — только первые вайбы, все — на странице поста --}}
    @if ($film->vibes)
        <div class="b-post-film-card__vibes">
            @foreach (array_slice($film->vibes, 0, 3) as $vibe)
                <span class="b-post-film-card__vibe">{{ trim(\App\Models\PostFilm::vibeEmoji($vibe) . ' ' . $vibe) }}</span>
            @endforeach
            @if (count($film->vibes) > 3)
                <span class="b-post-film-card__vibe b-post-film-card__vibe--more">+{{ count($film->vibes) - 3 }}</span>
            @endif
        </div>
    @endif
</div>
