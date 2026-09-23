{{-- Поля фильма/сериала — общие для создания (блок #tab-film) и редактирования поста.
     Сохраняются в post_films (App\Models\PostFilm::attributesFromRequest — имена инпутов должны совпадать).
     id полей (film-*, imdb-rating) использует и admin.js — заполнение из парсера.
     На странице поста эти данные показывает parts/film_card.blade.php.
     Чипы и "Сезонов" — public/admin/film-fields.js, стили — public/admin/pin-create.css.

     @param \App\Models\PostFilm|null $film — сохранённые данные (на странице редактирования) --}}
@php
    /** @var \App\Models\PostFilm|null $film */
    $film = $film ?? null;
    $filmChipGroups = [
        'vibes' => ['label' => 'Вайб', 'placeholder' => 'Свой вайб…'],
        'pros' => ['label' => 'Достоинства', 'placeholder' => 'Своё достоинство…'],
        'cons' => ['label' => 'Недостатки', 'placeholder' => 'Свой недостаток…'],
    ];
@endphp
{{-- data-hashtags-action: хештеги из полей (тип, статус, жанры, год, страна, режиссёр, актёры) — после
     парсинга и при правке этих полей film-fields.js кладёт их в выбранные хештеги формы --}}
<div class="b-pin-fields js-film-fields" data-hashtags-action="{{ route('hashtags.for-film') }}">
    <div class="b-pin-field b-pin-field--wide">
        <label for="film-original-title">Оригинальное название</label>
        <input type="text" class="b-pin-input" name="film-original-title" id="film-original-title" placeholder="Например, The Gentlemen" value="{{ $film->original_title ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-kind">Тип</label>
        <select name="film-kind" id="film-kind" class="b-pin-input">
            <option value="">-</option>
            @foreach(\App\Models\PostFilm::KINDS as $kind)
                <option value="{{ $kind }}" {{ ($film->kind ?? null) === $kind ? 'selected' : '' }}>{{ $kind }}</option>
            @endforeach
        </select>
    </div>
    {{-- только для сериалов — показывает/прячет film-fields.js по значению #film-kind --}}
    <div class="b-pin-field js-film-seasons-field" style="display: none;">
        <label for="film-seasons">Сезонов</label>
        <input type="text" class="b-pin-input" name="film-seasons" id="film-seasons" inputmode="numeric" value="{{ $film->seasons ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-status">Статус просмотра</label>
        <select name="film-status" id="film-status" class="b-pin-input">
            <option value="">-</option>
            @foreach(\App\Models\PostFilm::STATUSES as $status)
                <option value="{{ $status }}" {{ ($film->status ?? null) === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="b-pin-field">
        <label for="film-genres">Жанры фильма</label>
        <input type="text" class="b-pin-input" name="film-genres" id="film-genres" value="{{ $film->genres ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="imdb-rating">Рейтинг IMDb</label>
        <input type="text" class="b-pin-input" name="imdb-rating" id="imdb-rating" value="{{ $film->imdb_rating ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="my-assessment">Моя оценка</label>
        {{-- value{N} — исторический формат значений; PostFilm::attributesFromRequest берёт из него число --}}
        <select name="my-assessment" id="my-assessment" class="b-pin-input">
            <option value="value0">-</option>
            @for($rating = 10; $rating >= 1; $rating--)
                <option value="value{{ $rating }}" {{ ($film->my_rating ?? null) === $rating ? 'selected' : '' }}>{{ $rating }}</option>
            @endfor
        </select>
    </div>
    <div class="b-pin-field">
        <label for="film-year">Год</label>
        <input type="text" class="b-pin-input" name="film-year" id="film-year" value="{{ $film->year ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-country">Страна</label>
        <input type="text" class="b-pin-input" name="film-country" id="film-country" value="{{ $film->countries ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-director">Режиссер</label>
        <input type="text" class="b-pin-input" name="film-director" id="film-director" value="{{ $film->directors ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-actors">Актёры</label>
        <input type="text" class="b-pin-input" name="film-actors" id="film-actors" value="{{ $film->actors ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-duration">Длительность</label>
        <input type="text" class="b-pin-input" name="film-duration" id="film-duration" value="{{ $film->duration ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="film-rating-mpaa">Рейтинг MPAA</label>
        <input type="text" class="b-pin-input" name="film-rating-mpaa" id="film-rating-mpaa" value="{{ $film->mpaa_rating ?? '' }}">
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label for="film-description">Описание</label>
        <textarea class="b-pin-input" name="film-description" id="film-description" rows="3">{{ $film->description ?? '' }}</textarea>
    </div>

    {{-- Вайб, достоинства, недостатки: чипы (клик — выбрать/снять, можно несколько) + свои через поле.
         Выбранные film-fields.js складывает JSON-массивом в скрытый инпут film-vibes/film-pros/film-cons.
         Значение чипа — data-value (без эмодзи). Вайбы разбиты на группы с заголовками (PostFilm::VIBE_GROUPS),
         свои — в группе "Свои"; у достоинств/недостатков группа одна, без заголовка, свои — в ней же. --}}
    @foreach($filmChipGroups as $chipKind => $chipGroup)
        @php
            $chipOptions = \App\Models\PostFilm::chipOptions($chipKind, $film);
            $isGrouped = count($chipOptions['groups']) > 1;
        @endphp
        <div class="b-pin-field b-pin-field--wide">
            <label id="film-{{ $chipKind }}-label">{{ $chipGroup['label'] }}</label>
            <input type="hidden" name="film-{{ $chipKind }}" class="js-film-chips-value" value="{{ json_encode($film->{$chipKind} ?? [], JSON_UNESCAPED_UNICODE) }}">
            {{-- data-hashtag-action: выбранный чип сразу добавляется хештегом в #b-selected-tags-2 (film-fields.js) --}}
            <div class="b-pin-chips b-pin-chips--{{ $chipKind }} {{ $isGrouped ? 'b-pin-chips--grouped' : '' }} js-film-chips" id="film-{{ $chipKind }}" role="group" aria-labelledby="film-{{ $chipKind }}-label"
                 data-hashtag-action="{{ route('hashtags.find-or-create') }}">
                @foreach($chipOptions['groups'] as $groupLabel => $options)
                    <div class="b-pin-chips__group">
                        @if ($groupLabel !== '')
                            <div class="b-pin-chips__group-title">{{ $groupLabel }}</div>
                        @endif
                        <div class="b-pin-chips__row {{ $isGrouped ? '' : 'js-film-chips-custom' }}">
                            @foreach(array_merge($options, $isGrouped ? [] : $chipOptions['custom']) as $option)
                                @include('user.posts.parts.film_chip', ['option' => $option])
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @if ($isGrouped)
                    <div class="b-pin-chips__group js-film-chips-custom-group" @if(empty($chipOptions['custom'])) style="display: none;" @endif>
                        <div class="b-pin-chips__group-title">Свои</div>
                        <div class="b-pin-chips__row js-film-chips-custom">
                            @foreach($chipOptions['custom'] as $option)
                                @include('user.posts.parts.film_chip', ['option' => $option])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="b-pin-chips__add">
                <input type="text" class="b-pin-input js-film-chip-input" placeholder="{{ $chipGroup['placeholder'] }}" aria-label="{{ $chipGroup['placeholder'] }}">
                <button type="button" class="b-pin-btn b-pin-btn--ghost js-film-chip-add">Добавить</button>
            </div>
        </div>
    @endforeach
</div>
