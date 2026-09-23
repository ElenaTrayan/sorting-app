{{--
    Вкладка "Факты". $contact передаётся из show.blade.php.

    Реальные данные: раздел "Мои заметки" внизу (contact_facts — то, что пользователь
    реально ввёл сам) с добавлением/удалением как и раньше.
    Подставные (см. Contact::getFactSheetAttribute()): всё остальное — категории
    "Основная информация"/"Образ жизни"/"Здоровье"/"Прочее"/"Предпочтения" вместе с их
    "источниками" (соцсеть/интервью/публичные данные + дата) — под них нет отдельных
    полей в БД, часть значений (зодиак, режим, нравится/раздражает, здоровье) при этом
    переиспользует Contact::demo, уже используемый на вкладке "Обзор". Фильтры по
    категории/источнику и поиск при этом настоящие — работают на JS по data-атрибутам
    строк (public/admin/admin.js).
--}}
@php
    $factSheet = $contact->fact_sheet;
    $categoryMeta = [
        'basic' => 'Основная информация',
        'lifestyle' => 'Образ жизни',
        'health' => 'Здоровье',
        'other' => 'Прочее',
        'preferences' => 'Предпочтения',
        'notes' => 'Мои заметки',
    ];

    // считаем строки по категориям/источникам для панели фильтров — включая и подставные
    // категории, и реальные заметки пользователя
    $categoryCounts = [];
    $sourceCounts = [];
    $bumpCategory = function ($slug) use (&$categoryCounts) {
        $categoryCounts[$slug] = ($categoryCounts[$slug] ?? 0) + 1;
    };
    $bumpSource = function ($label) use (&$sourceCounts) {
        $sourceCounts[$label] = ($sourceCounts[$label] ?? 0) + 1;
    };

    foreach ($factSheet['categories'] as $slug => $category) {
        foreach ($category['items'] as $item) {
            $bumpCategory($slug);
            $bumpSource($item['source']['label']);
        }
    }
    $bumpCategory('preferences');
    $bumpCategory('preferences');
    $bumpCategory('preferences');
    $bumpCategory('preferences');
    $bumpSource($factSheet['preferences']['likes_source']['label']);
    $bumpSource($factSheet['preferences']['dislikes_source']['label']);
    $bumpSource($factSheet['preferences']['movies_source']['label']);
    $bumpSource($factSheet['preferences']['food_source']['label']);
    if ($contact->facts->isNotEmpty()) {
        foreach ($contact->facts as $fact) {
            $bumpCategory('notes');
            $bumpSource('Мои заметки');
        }
    }

    $categoriesForFilter = collect($categoryMeta)->map(function ($title, $slug) use ($categoryCounts) {
        return ['slug' => $slug, 'title' => $title, 'count' => $categoryCounts[$slug] ?? 0];
    })->filter(function ($row) {
        return $row['count'] > 0;
    })->values();

    $sourcesForFilter = collect($sourceCounts)->map(function ($count, $label) {
        return ['slug' => \Illuminate\Support\Str::slug($label), 'title' => $label, 'count' => $count];
    })->values();

    $totalFactsCount = $factSheet['total_count'] + $contact->facts->count();

    // простое русское склонение "факт/факта/фактов" по числу, без завязки на локаль trans_choice
    $factsWord = function ($n) {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return 'фактов';
        }
        if ($n1 > 1 && $n1 < 5) {
            return 'факта';
        }
        if ($n1 == 1) {
            return 'факт';
        }
        return 'фактов';
    };
@endphp

<div class="b-quotes-toolbar">
    <div>
        <h3 class="b-quotes-toolbar__title"><i class="fas fa-list-ul mr-2"></i>Факты</h3>
        <div class="b-quotes-toolbar__count">{{ $totalFactsCount }} {{ $factsWord($totalFactsCount) }} · <span class="text-muted">информация собрана из открытых источников</span></div>
    </div>
    <div class="b-quotes-toolbar__right">
        <div class="b-facts-search">
            <i class="fas fa-search"></i>
            <input type="search" class="form-control form-control-sm js-facts-search" placeholder="Поиск по фактам...">
        </div>
        <button type="button" class="b-wishlist-toolbar__btn js-facts-filter-toggle"><i class="fas fa-filter mr-1"></i> Фильтры</button>
        <button type="button" class="btn btn-sm btn-dark js-facts-add-jump"><i class="fas fa-plus mr-1"></i>Добавить факт</button>
    </div>
</div>

<p class="text-muted js-facts-no-match" style="display: none;">Ничего не найдено по выбранным фильтрам.</p>

<div class="b-facts-grid">
    <div class="b-facts-col">
        @include('user.contacts.parts.facts_category_card', ['slug' => 'basic', 'category' => $factSheet['categories']['basic']])
        @include('user.contacts.parts.facts_preferences_card', ['preferences' => $factSheet['preferences']])
    </div>
    <div class="b-facts-col">
        @include('user.contacts.parts.facts_category_card', ['slug' => 'lifestyle', 'category' => $factSheet['categories']['lifestyle']])
        @include('user.contacts.parts.facts_category_card', ['slug' => 'health', 'category' => $factSheet['categories']['health']])
        @include('user.contacts.parts.facts_category_card', ['slug' => 'other', 'category' => $factSheet['categories']['other']])

        {{-- "Мои заметки" — единственная реальная (contact_facts), редактируемая категория --}}
        <div class="b-facts-card js-facts-card" data-category="notes">
            <div class="b-facts-card__header" style="background: #eef7f9;">
                <span class="b-facts-card__icon" style="color: #0f8a9e;"><i class="fas fa-sticky-note"></i></span>
                <h3>Мои заметки <span class="text-muted">({{ $contact->facts->count() }})</span></h3>
            </div>
            <div class="b-facts-card__body">
                <div class="b-contact-section__form js-contact-fact-form" style="display: none;">
                    <textarea class="form-control form-control-sm js-contact-fact-text" rows="2" placeholder="Факт о человеке"></textarea>
                    <button type="button" class="btn btn-sm btn-info js-contact-fact-save">Добавить</button>
                </div>

                <div class="js-contact-facts-list">
                    @forelse ($contact->facts as $fact)
                        <div class="b-facts-row js-fact-item" data-id="{{ $fact->id }}" data-category="notes" data-source="{{ \Illuminate\Support\Str::slug('Мои заметки') }}" data-search="{{ \Illuminate\Support\Str::lower($fact->fact) }}">
                            <div class="b-facts-row__icon"><i class="fas fa-sticky-note"></i></div>
                            <div class="b-facts-row__main">
                                <div class="b-facts-row__value">{{ $fact->fact }}</div>
                            </div>
                            <i class="fas fa-times js-contact-list-remove b-facts-row__remove" data-base-url="{{ url('/cabinet/contact-facts') }}" title="Удалить"></i>
                        </div>
                    @empty
                        <p class="text-muted mb-0 js-facts-empty-note">Пока нет ни одной заметки — добавьте то, чего нет в остальных карточках.</p>
                    @endforelse
                </div>

                <button type="button" class="btn btn-sm btn-outline-info mt-2 js-contact-fact-add-toggle"><i class="fas fa-plus mr-1"></i>Добавить факт</button>
            </div>
        </div>
    </div>
</div>

@include('user.contacts.parts.facts_filter_panel', [
    'categories' => $categoriesForFilter,
    'sources' => $sourcesForFilter,
])
