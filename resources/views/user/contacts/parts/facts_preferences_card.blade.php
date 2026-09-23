{{-- $preferences: Contact::getFactSheetAttribute()['preferences'] --}}
<div class="b-facts-card js-facts-card" data-category="preferences">
    <div class="b-facts-card__header" style="background: {{ $preferences['bg'] }};">
        <span class="b-facts-card__icon" style="color: {{ $preferences['color'] }};"><i class="{{ $preferences['icon'] }}"></i></span>
        <h3>{{ $preferences['title'] }} <span class="text-muted">({{ count($preferences['likes']) + count($preferences['dislikes']) + 2 }})</span></h3>
    </div>
    <div class="b-facts-card__body">
        <div class="b-facts-row b-facts-row--tags js-fact-item" data-category="preferences" data-source="{{ \Illuminate\Support\Str::slug($preferences['likes_source']['label']) }}" data-search="нравится {{ \Illuminate\Support\Str::lower(implode(' ', $preferences['likes'])) }}">
            <div class="b-facts-row__main">
                <div class="b-facts-row__label"><i class="fas fa-heart text-success mr-1"></i>Нравится</div>
                <div class="b-facts-pref-tags">
                    @foreach ($preferences['likes'] as $like)
                        <span class="b-contact-pref-tag b-contact-pref-tag--like">{{ $like }}</span>
                    @endforeach
                </div>
            </div>
            <div class="b-facts-row__source" title="Источник: {{ $preferences['likes_source']['label'] }}, {{ $preferences['likes_source']['date'] }}">
                <span class="b-facts-row__source-icon" style="background: {{ $preferences['likes_source']['color'] }};"><i class="{{ $preferences['likes_source']['icon'] }}"></i></span>
                <div>
                    <div>{{ $preferences['likes_source']['label'] }}</div>
                    <div class="text-muted">{{ $preferences['likes_source']['date'] }}</div>
                </div>
            </div>
        </div>

        <div class="b-facts-row b-facts-row--tags js-fact-item" data-category="preferences" data-source="{{ \Illuminate\Support\Str::slug($preferences['dislikes_source']['label']) }}" data-search="не нравится раздражает {{ \Illuminate\Support\Str::lower(implode(' ', $preferences['dislikes'])) }}">
            <div class="b-facts-row__main">
                <div class="b-facts-row__label"><i class="fas fa-heart-broken text-danger mr-1"></i>Не нравится / раздражает</div>
                <div class="b-facts-pref-tags">
                    @foreach ($preferences['dislikes'] as $dislike)
                        <span class="b-contact-pref-tag b-contact-pref-tag--dislike">{{ $dislike }}</span>
                    @endforeach
                </div>
            </div>
            <div class="b-facts-row__source" title="Источник: {{ $preferences['dislikes_source']['label'] }}, {{ $preferences['dislikes_source']['date'] }}">
                <span class="b-facts-row__source-icon" style="background: {{ $preferences['dislikes_source']['color'] }};"><i class="{{ $preferences['dislikes_source']['icon'] }}"></i></span>
                <div>
                    <div>{{ $preferences['dislikes_source']['label'] }}</div>
                    <div class="text-muted">{{ $preferences['dislikes_source']['date'] }}</div>
                </div>
            </div>
        </div>

        <div class="b-facts-row js-fact-item" data-category="preferences" data-source="{{ \Illuminate\Support\Str::slug($preferences['movies_source']['label']) }}" data-search="{{ \Illuminate\Support\Str::lower('любимые фильмы ' . $preferences['favorite_movies']) }}">
            <div class="b-facts-row__main">
                <div class="b-facts-row__label">Любимые фильмы</div>
                <div class="b-facts-row__value">{{ $preferences['favorite_movies'] }}</div>
            </div>
            <div class="b-facts-row__source" title="Источник: {{ $preferences['movies_source']['label'] }}, {{ $preferences['movies_source']['date'] }}">
                <span class="b-facts-row__source-icon" style="background: {{ $preferences['movies_source']['color'] }};"><i class="{{ $preferences['movies_source']['icon'] }}"></i></span>
                <div>
                    <div>{{ $preferences['movies_source']['label'] }}</div>
                    <div class="text-muted">{{ $preferences['movies_source']['date'] }}</div>
                </div>
            </div>
        </div>

        <div class="b-facts-row js-fact-item" data-category="preferences" data-source="{{ \Illuminate\Support\Str::slug($preferences['food_source']['label']) }}" data-search="{{ \Illuminate\Support\Str::lower('любимая еда ' . $preferences['favorite_food']) }}">
            <div class="b-facts-row__main">
                <div class="b-facts-row__label">Любимая еда</div>
                <div class="b-facts-row__value">{{ $preferences['favorite_food'] }}</div>
            </div>
            <div class="b-facts-row__source" title="Источник: {{ $preferences['food_source']['label'] }}, {{ $preferences['food_source']['date'] }}">
                <span class="b-facts-row__source-icon" style="background: {{ $preferences['food_source']['color'] }};"><i class="{{ $preferences['food_source']['icon'] }}"></i></span>
                <div>
                    <div>{{ $preferences['food_source']['label'] }}</div>
                    <div class="text-muted">{{ $preferences['food_source']['date'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
