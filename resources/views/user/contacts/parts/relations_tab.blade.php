{{--
    Вкладка "Связи". $contact передаётся из show.blade.php.

    Целиком подставные данные (см. Contact::getRelationsAttribute()) — под связи между
    контактами нет отдельной таблицы в БД, поэтому категории "Близкие друзья"/"Друзья"/
    "Враги"/"Коллеги"/"Другие связи" выдуманы, стабильны по id. Родственники и Партнёры
    переиспользуют Contact::demo (те же люди, что и на вкладке "Обзор"). Поиск и фильтр
    по категории реальные — работают на JS по data-атрибутам строк, как на вкладках
    "Факты"/"Высказывания". Добавление связей не реализовано (не с чем — нет таблицы),
    поэтому кнопки "Добавить связь" здесь нет.
--}}
@php
    $relations = $contact->relations;
@endphp

<div class="b-quotes-toolbar">
    <div>
        <h3 class="b-quotes-toolbar__title"><i class="fas fa-users mr-2"></i>Связи</h3>
        <div class="b-quotes-toolbar__count">Родственники, друзья, коллеги и другие люди, связанные с этим контактом</div>
    </div>
    <div class="b-quotes-toolbar__right">
        <div class="b-facts-search">
            <i class="fas fa-search"></i>
            <input type="search" class="form-control form-control-sm js-relations-search" placeholder="Поиск по связям...">
        </div>
        <div class="b-wishlist-toolbar__dropdown js-relations-category-dropdown">
            <button type="button" class="b-wishlist-toolbar__btn js-relations-category-toggle">
                <span class="js-relations-category-label">Все категории</span> <i class="fas fa-caret-down ml-1"></i>
            </button>
            <div class="b-wishlist-toolbar__menu js-relations-category-menu">
                <button type="button" class="b-wishlist-toolbar__menu-item b-wishlist-toolbar__menu-item--active js-relations-category-option" data-category="">Все категории</button>
                @foreach ($relations['categories'] as $slug => $category)
                    <button type="button" class="b-wishlist-toolbar__menu-item js-relations-category-option" data-category="{{ $slug }}">{{ $category['title'] }}</button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<p class="text-muted js-relations-no-match" style="display: none;">Ничего не найдено.</p>

<div class="b-relations-grid">
    @foreach ($relations['categories'] as $slug => $category)
        @include('user.contacts.parts.relations_category_card', ['slug' => $slug, 'category' => $category])
    @endforeach

    @include('user.contacts.parts.relations_map', ['contact' => $contact, 'mapNodes' => $relations['map_nodes']])
</div>
