<?php

// Поводы и категории для раздела "Мой список подарков" — пока просто фиксированный список
// (как на макете), не отдельная управляемая сущность в БД. Значение occasion/category у
// wishlist/wishlist_item — это slug из списка ниже, либо произвольный текст (если выбрали "Больше").

return [
    'occasions' => [
        ['slug' => 'birthday', 'title' => 'День рождения', 'icon' => 'fa-birthday-cake'],
        ['slug' => 'for_self', 'title' => 'Для себя', 'icon' => 'fa-star'],
        ['slug' => 'wedding', 'title' => 'Свадьба', 'icon' => 'fa-gem'],
        ['slug' => 'new_year', 'title' => 'Новый год / Рождество', 'icon' => 'fa-tree'],
        ['slug' => 'partner', 'title' => 'Партнеру', 'icon' => 'fa-heart'],
        ['slug' => 'graduation', 'title' => 'Выпускной', 'icon' => 'fa-graduation-cap'],
        ['slug' => 'housewarming', 'title' => 'Новоселье', 'icon' => 'fa-home'],
        ['slug' => 'engagement', 'title' => 'Помолвка', 'icon' => 'fa-ring'],
        ['slug' => 'anniversary', 'title' => 'Годовщина', 'icon' => 'fa-glass-cheers'],
        ['slug' => 'family', 'title' => 'Семья', 'icon' => 'fa-users'],
    ],

    'categories' => [
        ['slug' => 'electronics', 'title' => 'Техника'],
        ['slug' => 'clothes', 'title' => 'Одежда'],
        ['slug' => 'shoes', 'title' => 'Обувь'],
        ['slug' => 'accessories', 'title' => 'Аксессуары'],
        ['slug' => 'jewelry', 'title' => 'Украшения'],
        ['slug' => 'cosmetics', 'title' => 'Косметика'],
        ['slug' => 'makeup', 'title' => 'Декоративная косметика'],
        ['slug' => 'perfume', 'title' => 'Парфюм'],
        ['slug' => 'home', 'title' => 'Товары для дома'],
        ['slug' => 'health', 'title' => 'Здоровье'],
        ['slug' => 'books', 'title' => 'Книги'],
        ['slug' => 'toys', 'title' => 'Игры и развлечения'],
        ['slug' => 'creativity', 'title' => 'Творчество'],
        ['slug' => 'hobby', 'title' => 'Хобби'],
        ['slug' => 'sport_goods', 'title' => 'Спортивные товары'],
        ['slug' => 'baby', 'title' => 'Товары для детей'],
        ['slug' => 'pets', 'title' => 'Зоотовары'],
        ['slug' => 'other', 'title' => 'Прочее'],
    ],

    // насколько сильно хочется желание — значение необязательное (null = "приоритет не указан")
    'priorities' => [
        ['slug' => 'want_most', 'title' => 'Хочу больше всего'],
        ['slug' => 'really_want', 'title' => 'Очень хочу'],
        ['slug' => 'want', 'title' => 'Хочу'],
    ],
];
