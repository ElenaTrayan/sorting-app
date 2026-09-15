<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Contact
 * @package App\Models
 */
class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = [
        'user_id',
        'name',
        'photo',
        'birth_date',
        'residence',
        'residence_lat',
        'residence_lng',
        'phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'contact_hashtag', 'contact_id', 'hashtag_id');
    }

    public function photos()
    {
        return $this->hasMany(ContactPhoto::class, 'contact_id')->orderBy('created_at');
    }

    public function conversations()
    {
        return $this->hasMany(ContactConversation::class, 'contact_id')->orderBy('created_at');
    }

    public function socialLinks()
    {
        return $this->hasMany(ContactSocialLink::class, 'contact_id')->orderBy('created_at');
    }

    public function quotes()
    {
        return $this->hasMany(ContactQuote::class, 'contact_id')->orderBy('created_at');
    }

    public function facts()
    {
        return $this->hasMany(ContactFact::class, 'contact_id')->orderBy('created_at');
    }

    /**
     * Декодирует поле photo ({name, extension, path}, как у постов) в массив.
     *
     * @return array|null
     */
    public function getPhotoDataAttribute(): ?array
    {
        if (empty($this->attributes['photo'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['photo'], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Готовый URL главного фото для <img src="">, либо null.
     *
     * @return string|null
     */
    public function getPhotoUrlAttribute(): ?string
    {
        $data = $this->photo_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

    /**
     * Инициалы контакта (1-2 буквы) для аватара-заглушки на странице "База контактов"
     * (resources/views/admin/contacts/index.blade.php), когда у контакта нет фото.
     *
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim($this->name)) ?: []));
        $words = array_slice($words, 0, 2);

        return mb_strtoupper(implode('', array_map(function ($word) {
            return mb_substr($word, 0, 1);
        }, $words)));
    }

    /**
     * Короткая дата добавления контакта ("12 сен") для карточки в списке "База контактов".
     *
     * @return string
     */
    public function getShortAddedLabelAttribute(): string
    {
        $ruMonthsShort = [1 => 'янв', 'февр', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

        return $this->created_at->format('d') . ' ' . $ruMonthsShort[(int) $this->created_at->format('n')];
    }

    /**
     * Данные для полей дизайна "People DB" (resources/views/admin/contacts/show.blade.php),
     * которых пока нет в БД — знак зодиака, режим дня, страхи, нравится/раздражает,
     * здоровье, связи, места, бывшие, работа/образование. Пока под них нет отдельных таблиц,
     * отдаём подставные, но СТАБИЛЬНЫЕ для конкретного контакта значения (детерминированные
     * по id, а не случайные при каждой перезагрузке страницы), чтобы страница выглядела как
     * в макете. Как только эти сущности появятся в БД — метод и этот блок вёрстки нужно убрать.
     *
     * @return array
     */
    public function getDemoAttribute(): array
    {
        $surname = trim(explode(' ', trim($this->name))[0] ?? '') ?: 'Иванова';
        // мужская форма фамилии для родственников-мужчин (Иванова -> Иванов)
        $maleSurname = \Illuminate\Support\Str::endsWith($surname, ['а', 'я']) ? mb_substr($surname, 0, -1) : $surname;

        $femaleFirstNames = ['Мария', 'Ольга', 'Екатерина', 'Наталья', 'Ирина'];
        $maleFirstNames = ['Сергей', 'Максим', 'Дмитрий', 'Андрей', 'Игорь'];
        $n = $this->id ?: 1;
        $childIsSon = $n % 2 === 0;

        return [
            'handle' => '@' . \Illuminate\Support\Str::slug($this->name, '.'),
            'role_line' => ['Маркетолог • Блогер • Путешественница', 'Дизайнер • Фотограф • Кофеман', 'Предприниматель • Инвестор • Спортсмен'][$n % 3],
            'zodiac' => $this->birth_date ? static::zodiacFor((int) $this->birth_date->format('n'), (int) $this->birth_date->format('j')) : null,
            'regime' => $n % 2 === 0 ? 'Сова' : 'Жаворонок',
            'daily_routine' => ['09:00 – 01:00', '07:00 – 23:00', '10:00 – 02:00'][$n % 3],
            'free_time' => ['Путешествия, спорт, чтение, фото', 'Кино, музыка, готовка', 'Рисование, йога, прогулки'][$n % 3],
            'fear' => ['Потерять близких', 'Высота', 'Одиночество'][$n % 3],
            'status_badges' => ['Активна', 'Публичная персона'],
            'likes' => array_slice(['Путешествия', 'Кофе', 'Кошки', 'Фотография', 'Хорошая еда', 'Фильмы', 'Мода', 'Спорт', 'Музыка', 'Книги'], $n % 4, 6),
            'dislikes' => array_slice(['Опоздания', 'Грубость', 'Шум', 'Некомпетентность', 'Жара', 'Нарушение личных границ', 'Ложь'], $n % 2, 5),
            'allergies' => ['Пыльца, кошки', 'Нет', 'Цитрусовые'][$n % 3],
            'chronic' => 'Нет',
            'relatives' => [
                ['name' => $surname . ' ' . $femaleFirstNames[$n % 5] . ' Петровна', 'relation' => 'Мама'],
                ['name' => $maleSurname . ' ' . $maleFirstNames[($n + 1) % 5] . ' Николаевич', 'relation' => 'Папа'],
                [
                    'name' => ($childIsSon ? $maleSurname . ' ' . $maleFirstNames[($n + 2) % 5] : $surname . ' ' . $femaleFirstNames[($n + 3) % 5]),
                    'relation' => $childIsSon ? 'Сын' : 'Дочь',
                ],
                ['name' => ['Петрова', 'Коваленко', 'Бондаренко'][$n % 3] . ' ' . $femaleFirstNames[($n + 4) % 5], 'relation' => 'Двоюродная сестра'],
            ],
            'places_loves' => [
                ['name' => 'Bali Beach Club', 'address' => 'Бали, Индонезия'],
                ['name' => 'Faro Restaurant', 'address' => 'Киев, ул. Набережная, 1'],
                ['name' => 'Kotor', 'address' => 'Котор, Черногория'],
            ],
            'places_visits' => [
                ['name' => 'Sport Life', 'address' => 'Киев, ул. Большая Васильковская, 102'],
                ['name' => 'Сильпо', 'address' => 'Киев, ул. Саксаганского, 44'],
                ['name' => 'Good Wine', 'address' => 'Киев, ул. Мечникова, 9'],
            ],
            'exes' => [
                ['name' => 'Сидорова Екатерина', 'relation' => 'Бывшая девушка', 'years' => '2018 – 2021'],
                ['name' => 'Ковальчук Марина', 'relation' => 'Бывшая девушка', 'years' => '2016 – 2018'],
            ],
            'education' => [
                ['period' => '2012 – 2017', 'place' => 'Киевский национальный университет', 'role' => 'Факультет журналистики, бакалавр'],
                ['period' => '2010 – 2012', 'place' => 'Колледж им. И. Франко', 'role' => 'Специальность: издательское дело'],
            ],
            'work_history' => [
                ['period' => '2022 – сейчас', 'place' => 'Creative Lab', 'role' => 'Маркетолог (текущее место)', 'current' => true],
                ['period' => '2019 – 2022', 'place' => 'Media Group', 'role' => 'SMM-специалист', 'current' => false],
                ['period' => '2017 – 2019', 'place' => 'Digital Agency', 'role' => 'Контент-менеджер', 'current' => false],
            ],
        ];
    }

    /**
     * Данные для вкладки "Связи" (resources/views/admin/contacts/parts/relations_tab.blade.php) —
     * категории "Родственники"/"Партнёры" переиспользуют demo.relatives/demo.exes (та же
     * информация, что и на вкладке "Обзор"), остальные категории (близкие друзья, друзья,
     * враги, коллеги, другие связи) — подставные, стабильные по id, целиком выдуманные:
     * под связи между контактами нет отдельной таблицы в БД.
     *
     * @return array
     */
    /**
     * Данные для вкладки "Места" (resources/views/admin/contacts/parts/places_tab.blade.php) —
     * категории "Места отдыха"/"Рабочие места" частично переиспользуют demo.places_loves и
     * demo.work_history (та же информация, что и на вкладке "Обзор"/"Работа и образование"),
     * остальное — целиком подставные места, стабильные по id. Дата последнего посещения —
     * единственное по-настоящему рассчитываемое поле здесь (стабильно по id + названию места),
     * под сами места нет отдельной таблицы в БД, поэтому кнопки "Добавить место" тут нет.
     *
     * @return array
     */
    public function getPlacesAttribute(): array
    {
        $demo = $this->demo;
        $n = $this->id ?: 1;
        $ruMonthsShort = [1 => 'янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.'];

        $lastVisit = function ($seed) use ($ruMonthsShort) {
            $daysAgo = 3 + (crc32($this->id . '|place|' . $seed) % 250);
            $time = now()->subDays($daysAgo);
            $label = (int) $time->format('j') . ' ' . $ruMonthsShort[(int) $time->format('n')] . ' ' . $time->format('Y') . ', ' . $time->format('H:i');

            return ['label' => $label, 'timestamp' => $time->timestamp];
        };

        // {name, type, street, city, country, icon, photo}
        $categories = [
            'visits' => [
                'title' => 'Обычно посещает', 'icon' => 'fas fa-map-marker-alt', 'color' => '#7367f0', 'bg' => '#eef0fe',
                'items' => [
                    ['name' => 'Good Wine', 'type' => 'Винный магазин', 'street' => 'ул. Мечникова, 9', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-wine-bottle', 'photo' => 0],
                    ['name' => 'Sport Life', 'type' => 'Фитнес-клуб', 'street' => 'ул. Большая Васильковская, 102', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-dumbbell', 'photo' => 1],
                    ['name' => 'Сильпо', 'type' => 'Супермаркет', 'street' => 'ул. Саксаганского, 44', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-shopping-cart', 'photo' => 2],
                    ['name' => 'Bottega Italia', 'type' => 'Ресторан', 'street' => 'ул. Золотоворотская, 15', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-utensils', 'photo' => 3],
                    ['name' => 'One Love Coffee', 'type' => 'Кофейня', 'street' => 'ул. Ярославов Вал, 21', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-mug-hot', 'photo' => 0],
                    ['name' => 'Кинотеатр «Оскар»', 'type' => 'Кинотеатр', 'street' => 'ул. Гетьмана, 6', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-film', 'photo' => 1],
                ],
            ],
            'favorites' => [
                'title' => 'Любимые места', 'icon' => 'fas fa-heart', 'color' => '#d6336c', 'bg' => '#fdeaf3',
                'items' => [
                    ['name' => 'Faro Restaurant', 'type' => 'Ресторан', 'street' => 'ул. Набережная, 1', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-utensils', 'photo' => 2],
                    ['name' => 'Café de Flore', 'type' => 'Кафе', 'street' => 'Boulevard Saint-Germain', 'city' => 'Париж', 'country' => 'Франция', 'icon' => 'fas fa-coffee', 'photo' => 3],
                ],
            ],
            'vacation' => [
                'title' => 'Места отдыха', 'icon' => 'fas fa-umbrella-beach', 'color' => '#2f9e44', 'bg' => '#eafbe9',
                'items' => [
                    ['name' => 'Bali Beach Club', 'type' => 'Пляжный клуб', 'street' => '', 'city' => 'Бали', 'country' => 'Индонезия', 'icon' => 'fas fa-umbrella-beach', 'photo' => 0],
                    ['name' => 'Kotor', 'type' => 'Город', 'street' => '', 'city' => 'Котор', 'country' => 'Черногория', 'icon' => 'fas fa-mountain', 'photo' => 1],
                    ['name' => 'Santorini View', 'type' => 'Отель', 'street' => '', 'city' => 'Санторини', 'country' => 'Греция', 'icon' => 'fas fa-hotel', 'photo' => 2],
                ],
            ],
            'former' => [
                'title' => 'Бывшие места', 'icon' => 'fas fa-history', 'color' => '#8a8f98', 'bg' => '#f1f2f5',
                'items' => [
                    ['name' => 'FitZone', 'type' => 'Бывший фитнес-клуб', 'street' => 'ул. Шота Руставели, 39', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-dumbbell', 'photo' => 3],
                    ['name' => 'Кафе «Ковчег»', 'type' => 'Закрылось', 'street' => 'ул. Прорізна, 4', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-coffee', 'photo' => 0],
                ],
            ],
            'work' => [
                'title' => 'Рабочие места', 'icon' => 'fas fa-briefcase', 'color' => '#2563eb', 'bg' => '#eaf1ff',
                'items' => array_map(function ($job) {
                    return ['name' => $job['place'], 'type' => $job['role'], 'street' => '', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-briefcase', 'photo' => 1];
                }, $demo['work_history']),
            ],
            'other' => [
                'title' => 'Другое', 'icon' => 'fas fa-map-pin', 'color' => '#b5720a', 'bg' => '#fff4e0',
                'items' => [
                    ['name' => 'Аптека 911', 'type' => 'Аптека', 'street' => 'вул. Хрещатик, 15', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-prescription-bottle-alt', 'photo' => 2],
                    ['name' => 'Salon Glam', 'type' => 'Салон красоты', 'street' => 'ул. Владимирская, 47', 'city' => 'Киев', 'country' => 'Украина', 'icon' => 'fas fa-cut', 'photo' => 3],
                ],
            ],
        ];

        $allItems = [];
        foreach ($categories as $slug => &$category) {
            foreach ($category['items'] as &$item) {
                $visit = $lastVisit($slug . '|' . $item['name']);
                $item['last_visit'] = $visit['label'];
                $item['last_visit_ts'] = $visit['timestamp'];
                $item['category'] = $slug;
                $item['category_color'] = $category['color'];
                $allItems[] = $item;
            }
            unset($item);
        }
        unset($category);

        usort($allItems, function ($a, $b) {
            return $b['last_visit_ts'] - $a['last_visit_ts'];
        });

        $cities = array_unique(array_column($allItems, 'city'));
        $countries = array_unique(array_column($allItems, 'country'));

        return [
            'categories' => $categories,
            'recent' => array_slice($allItems, 0, 5),
            'stats' => [
                'total' => count($allItems),
                'cities' => count($cities),
                'countries' => count($countries),
            ],
        ];
    }

    public function getRelationsAttribute(): array
    {
        $demo = $this->demo;
        $n = $this->id ?: 1;

        $categories = [
            'relatives' => [
                'title' => 'Родственники', 'icon' => 'fas fa-users', 'color' => '#2563eb', 'bg' => '#eaf1ff',
                'items' => $demo['relatives'],
            ],
            'close_friends' => [
                'title' => 'Близкие друзья', 'icon' => 'fas fa-heart', 'color' => '#d6336c', 'bg' => '#fdeaf3',
                'items' => [
                    ['name' => 'Сидорова Екатерина', 'relation' => 'Подруга'],
                    ['name' => 'Ковальчук Марина', 'relation' => 'Подруга'],
                    ['name' => 'Левченко Анастасия', 'relation' => 'Подруга'],
                ],
            ],
            'friends' => [
                'title' => 'Друзья', 'icon' => 'fas fa-user-friends', 'color' => '#2f9e44', 'bg' => '#eafbe9',
                'items' => [
                    ['name' => 'Петрова Анна', 'relation' => 'Друг'],
                    ['name' => 'Смирнов Алексей', 'relation' => 'Друг'],
                    ['name' => 'Кравченко Игорь', 'relation' => 'Друг'],
                    ['name' => 'Бондаренко Светлана', 'relation' => 'Друг'],
                    ['name' => 'Федоров Максим', 'relation' => 'Друг'],
                ],
            ],
            'partners' => [
                'title' => 'Партнёры / Бывшие партнёры', 'icon' => 'fas fa-heart-broken', 'color' => '#e64980', 'bg' => '#fdeaf3',
                'items' => $demo['exes'],
            ],
            'enemies' => [
                'title' => 'Враги', 'icon' => 'fas fa-user-slash', 'color' => '#dc3545', 'bg' => '#fdeaea',
                'items' => [
                    ['name' => 'Сидоров Игорь', 'relation' => 'Бизнес-конкурент'],
                    ['name' => 'Кузнецова Наталья', 'relation' => 'Личная неприязнь'],
                ],
            ],
            'colleagues' => [
                'title' => 'Коллеги', 'icon' => 'fas fa-briefcase', 'color' => '#7367f0', 'bg' => '#eef0fe',
                'items' => [
                    ['name' => 'Харченко Владислав', 'relation' => 'Коллега'],
                    ['name' => 'Морозова Елена', 'relation' => 'Коллега'],
                    ['name' => 'Савченко Артём', 'relation' => 'Коллега'],
                ],
            ],
            'other' => [
                'title' => 'Другие связи', 'icon' => 'fas fa-link', 'color' => '#0f8a9e', 'bg' => '#eef7f9',
                'items' => [
                    ['name' => 'Екатерина Лебедева', 'relation' => 'Клиент'],
                    ['name' => 'Андрей Костенко', 'relation' => 'Наставник'],
                    ['name' => 'Мария Соколова', 'relation' => 'Куратор'],
                    ['name' => 'Владимир Гриценко', 'relation' => 'Знакомый'],
                ],
            ],
        ];

        // узлы для радиальной "Карты связей" — те же категории, разложенные равномерно по кругу
        // (шаг считаем от реального числа категорий, а не фиксируем углы вручную, иначе при
        // изменении числа категорий соседние узлы могут наехать друг на друга)
        $nonEmptyCount = count(array_filter($categories, function ($category) {
            return !empty($category['items']);
        }));
        $angleStep = $nonEmptyCount > 0 ? 360 / $nonEmptyCount : 0;
        $mapNodes = [];
        $i = 0;
        foreach ($categories as $slug => $category) {
            if (empty($category['items'])) {
                continue;
            }
            $angle = -90 + $i * $angleStep;
            $mapNodes[] = [
                'slug' => $slug,
                'title' => $category['title'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'count' => count($category['items']),
                'x' => 50 + 42 * cos(deg2rad($angle)),
                'y' => 50 + 42 * sin(deg2rad($angle)),
            ];
            $i++;
        }

        return [
            'categories' => $categories,
            'map_nodes' => $mapNodes,
        ];
    }

    /**
     * Данные для вкладки "Факты" (resources/views/admin/contacts/parts/facts_tab.blade.php) —
     * та же информация, что и в getDemoAttribute() (зодиак, режим, страхи, нравится/раздражает,
     * здоровье), просто разложенная по категориям-карточкам, как в макете, плюс ещё немного
     * подставных полей (гражданство, языки, привычки, "прочее"), которых нет нигде в БД.
     * У каждой строки — подставной "источник" (соцсеть/интервью/публичные данные + дата),
     * потому что в реальности факты в contact_facts просто текст без источника. Настоящие,
     * добавленные пользователем факты (contact_facts) выводятся отдельным блоком "Мои заметки"
     * средствами show.blade.php/facts_tab.blade.php, сюда не попадают.
     *
     * @return array
     */
    public function getFactSheetAttribute(): array
    {
        $demo = $this->demo;
        $n = $this->id ?: 1;

        $sourcesPool = [
            ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => '#E1306C'],
            ['label' => 'TikTok', 'icon' => 'fab fa-tiktok', 'color' => '#010101'],
            ['label' => 'YouTube', 'icon' => 'fab fa-youtube', 'color' => '#FF0000'],
            ['label' => 'Telegram', 'icon' => 'fab fa-telegram-plane', 'color' => '#229ED9'],
            ['label' => 'Интервью', 'icon' => 'fas fa-microphone', 'color' => '#6c757d'],
        ];
        $source = function ($seed) use ($sourcesPool) {
            $i = crc32($this->id . '|' . $seed) % count($sourcesPool);
            $daysAgo = 5 + (crc32($this->id . '|' . $seed . '|d') % 400);
            $row = $sourcesPool[$i];
            $row['date'] = now()->subDays($daysAgo)->format('d.m.Y');

            return $row;
        };
        $publicSource = ['label' => 'Публичные данные', 'icon' => 'fas fa-globe', 'color' => '#8a8f98', 'date' => '—'];

        // ->translatedFormat() зависит от текущей локали приложения (может оказаться не 'ru') —
        // не полагаемся на неё и берём русское название месяца из фиксированного списка
        $ruMonths = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        $birthDateLabel = $this->birth_date
            ? $this->birth_date->format('j') . ' ' . $ruMonths[(int) $this->birth_date->format('n')] . ' ' . $this->birth_date->format('Y')
            : '—';

        $citizenship = $this->residence && \Illuminate\Support\Str::contains($this->residence, ',')
            ? trim(\Illuminate\Support\Str::afterLast($this->residence, ','))
            : ($this->residence ?: 'Не указано');

        // тот же признак, что и в getDemoAttribute() для мужской/женской формы фамилии родственников
        $surname = trim(explode(' ', trim($this->name))[0] ?? '');
        $gender = \Illuminate\Support\Str::endsWith($surname, ['а', 'я']) ? 'Женский' : 'Мужской';

        $categories = [
            'basic' => [
                'title' => 'Основная информация', 'icon' => 'fas fa-user', 'color' => '#2563eb', 'bg' => '#eaf1ff',
                'items' => [
                    ['icon' => 'fas fa-birthday-cake', 'label' => 'Дата рождения', 'value' => $birthDateLabel, 'source' => $source('birth_date')],
                    ['icon' => 'fas fa-star', 'label' => 'Знак зодиака', 'value' => $demo['zodiac'] ?? '—', 'source' => $source('zodiac')],
                    ['icon' => 'fas fa-flag', 'label' => 'Гражданство', 'value' => $citizenship, 'source' => $publicSource],
                    ['icon' => 'fas fa-venus-mars', 'label' => 'Пол', 'value' => $gender, 'source' => $source('gender')],
                    ['icon' => 'fas fa-language', 'label' => 'Языки', 'value' => 'Русский, Украинский, Английский', 'source' => $source('languages')],
                    ['icon' => 'fas fa-map-marker-alt', 'label' => 'Город проживания', 'value' => $this->residence ?: '—', 'source' => $source('city')],
                ],
            ],
            'lifestyle' => [
                'title' => 'Образ жизни', 'icon' => 'far fa-clock', 'color' => '#2f9e44', 'bg' => '#eafbe9',
                'items' => [
                    ['icon' => 'fas fa-' . ($demo['regime'] === 'Сова' ? 'moon' : 'sun'), 'label' => 'Режим', 'value' => $demo['regime'], 'source' => $source('regime')],
                    ['icon' => 'far fa-clock', 'label' => 'Распорядок дня', 'value' => $demo['daily_routine'], 'source' => $source('routine')],
                    ['icon' => 'fas fa-umbrella-beach', 'label' => 'Чем занимается в свободное время', 'value' => $demo['free_time'], 'source' => $source('free_time')],
                    ['icon' => 'fas fa-exclamation-circle', 'label' => 'Самый большой страх', 'value' => $demo['fear'], 'source' => $source('fear')],
                    ['icon' => 'fas fa-redo', 'label' => 'Привычки', 'value' => ['Пьёт кофе каждый день, ведёт дневник', 'Читает перед сном', 'Бегает по утрам'][$n % 3], 'source' => $source('habits')],
                ],
            ],
            'health' => [
                'title' => 'Здоровье', 'icon' => 'fas fa-notes-medical', 'color' => '#7367f0', 'bg' => '#eef0fe',
                'items' => [
                    ['icon' => 'fas fa-allergies', 'label' => 'Аллергии', 'value' => $demo['allergies'], 'source' => $source('allergies')],
                    ['icon' => 'fas fa-briefcase-medical', 'label' => 'Хронические заболевания', 'value' => $demo['chronic'], 'source' => $source('chronic')],
                    ['icon' => 'fas fa-glasses', 'label' => 'Особые отметки', 'value' => ['Носит линзы', 'Носит очки', 'Нет'][$n % 3], 'source' => $source('notes')],
                    ['icon' => 'fas fa-wine-glass-alt', 'label' => 'Отношение к алкоголю', 'value' => ['Редко', 'Не пьёт', 'Иногда'][$n % 3], 'source' => $source('alcohol')],
                ],
            ],
            'other' => [
                'title' => 'Прочее', 'icon' => 'fas fa-icons', 'color' => '#b5720a', 'bg' => '#fff4e0',
                'items' => [
                    ['icon' => 'fas fa-leaf', 'label' => 'Вегетарианка', 'value' => ['Да, с 2020 года', 'Нет'][$n % 2], 'source' => $source('vegetarian')],
                    ['icon' => 'fas fa-record-vinyl', 'label' => 'Коллекционирует', 'value' => ['Виниловые пластинки', 'Марки', 'Кроссовки'][$n % 3], 'source' => $source('collects')],
                    ['icon' => 'fas fa-blog', 'label' => 'Ведёт блог', 'value' => ['Да, о путешествиях', 'Да, о еде', 'Нет'][$n % 3], 'source' => $source('blog')],
                    ['icon' => 'fas fa-cloud-sun', 'label' => 'Мечтает', 'value' => ['Посетить Японию', 'Открыть своё дело', 'Написать книгу'][$n % 3], 'source' => $source('dream')],
                    ['icon' => 'fas fa-paw', 'label' => 'Домашние животные', 'value' => ['Кошка (Мия)', 'Собака (Рекс)', 'Нет'][$n % 3], 'source' => $source('pets')],
                    ['icon' => 'fas fa-smoking-ban', 'label' => 'Курит', 'value' => ['Нет', 'Иногда'][$n % 2], 'source' => $source('smoking')],
                ],
            ],
        ];

        $preferences = [
            'title' => 'Предпочтения', 'icon' => 'fas fa-heart', 'color' => '#d6336c', 'bg' => '#fdeaf3',
            'likes' => $demo['likes'],
            'likes_source' => $source('likes'),
            'dislikes' => $demo['dislikes'],
            'dislikes_source' => $source('dislikes'),
            'favorite_movies' => ['Интерстеллар, Дюна, Амели', 'Матрица, Начало, Форрест Гамп', 'Ла-Ла Ленд, Амели, Тайна Коко'][$n % 3],
            'movies_source' => $source('movies'),
            'favorite_food' => ['Итальянская кухня, суши', 'Грузинская кухня, стейки', 'Азиатская кухня, десерты'][$n % 3],
            'food_source' => $source('food'),
        ];

        $totalCount = array_sum(array_map(function ($category) {
            return count($category['items']);
        }, $categories)) + count($preferences['likes']) + count($preferences['dislikes']) + 2;

        return [
            'categories' => $categories,
            'preferences' => $preferences,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Знак зодиака по месяцу/дню рождения — единственное поле в getDemoAttribute(),
     * которое считается по-настоящему, а не берётся из подставного набора.
     */
    private static function zodiacFor(int $month, int $day): string
    {
        $table = [
            1 => [20, 'Козерог', 'Водолей'],
            2 => [19, 'Водолей', 'Рыбы'],
            3 => [21, 'Рыбы', 'Овен'],
            4 => [20, 'Овен', 'Телец'],
            5 => [21, 'Телец', 'Близнецы'],
            6 => [21, 'Близнецы', 'Рак'],
            7 => [23, 'Рак', 'Лев'],
            8 => [23, 'Лев', 'Дева'],
            9 => [23, 'Дева', 'Весы'],
            10 => [23, 'Весы', 'Скорпион'],
            11 => [22, 'Скорпион', 'Стрелец'],
            12 => [22, 'Стрелец', 'Козерог'],
        ];

        [$threshold, $before, $after] = $table[$month];

        return $day < $threshold ? $before : $after;
    }
}
