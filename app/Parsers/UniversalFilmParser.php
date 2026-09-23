<?php

namespace App\Parsers;

use App\Http\Controllers\User\Packages\UploadImageController;

/**
 * Универсальный парсер страницы фильма/сериала для сайтов, под которые нет отдельного парсера.
 *
 * Источники данных по убыванию надёжности (каждое поле берётся из первого источника, где оно нашлось):
 *  1. JSON-LD (schema.org Movie / TVSeries / ...) — есть на большинстве крупных киносайтов;
 *  2. микроразметка itemprop (schema.org microdata);
 *  3. мета-теги Open Graph (og:title, og:image, video:actor, ...);
 *  4. текстовые подписи на странице: "Жанр: ...", "Режиссёр: ...", "В ролях: ..." (ru/uk/en).
 *
 * Возвращает те же ключи, что и остальные парсеры фильмов (их читает admin.js на странице создания поста).
 */
class UniversalFilmParser extends BaseParser
{
    private const FILM_TYPES = [
        'movie', 'film', 'tvseries', 'tvseason', 'tvepisode', 'episode',
        'creativeworkseries', 'creativeworkseason', 'videoobject',
    ];

    /**
     * Подписи полей на страницах (без двоеточия, в нижнем регистре)
     */
    private const LABELS = [
        'film_genres' => ['жанр', 'жанры', 'жанр(ы)', 'жанри', 'genre', 'genres'],
        'film_year' => ['год', 'год выпуска', 'год выхода', 'год производства', 'дата выхода', 'премьера',
            'рік', 'рік виходу', 'рік випуску', 'дата виходу', 'прем\'єра', 'year', 'release date', 'released'],
        'film_countries' => ['страна', 'страны', 'страна производства', 'країна', 'країни', 'country', 'countries'],
        'film_directors' => ['режиссер', 'режиссёр', 'режиссеры', 'режиссёры', 'режисер', 'режисери',
            'director', 'directors', 'directed by'],
        'film_actors' => ['актеры', 'актёры', 'в ролях', 'в главных ролях', 'актори', 'актори та актриси',
            'у ролях', 'в головних ролях', 'cast', 'stars', 'starring'],
        'film_duration' => ['продолжительность', 'длительность', 'время', 'тривалість', 'час', 'runtime', 'duration', 'length'],
        'mpaa_rating' => ['рейтинг mpaa', 'mpaa', 'возраст', 'возрастной рейтинг', 'возрастное ограничение',
            'вікове обмеження', 'віковий рейтинг', 'age rating', 'rated'],
        'imdb_rating' => ['imdb', 'рейтинг imdb'],
        'film_original_title' => ['оригинальное название', 'название в оригинале', 'оригинал', 'оригінальна назва',
            'назва в оригіналі', 'original title'],
        'film_seasons' => ['сезонов', 'количество сезонов', 'сезони', 'кількість сезонів', 'seasons'],
        'film_description' => ['описание', 'сюжет', 'опис', 'про фільм', 'о фильме', 'description', 'plot', 'storyline'],
    ];

    /**
     * Максимум имён в списках актёров/режиссёров
     */
    private const MAX_LIST_ITEMS = 20;

    /**
     * Длительность короче — почти наверняка ошибка в разметке сайта (см. pickDuration)
     */
    private const MIN_DURATION_MINUTES = 5;

    /**
     * Год в скобках в конце названия: "Драйв (2026)", "Во все тяжкие (2008–2013)", "Сериал (2019– )"
     */
    private const TITLE_YEAR_PATTERN = '~\s*\(((?:18|19|20)\d{2})(?:\s*[-–—]\s*(?:(?:18|19|20)\d{2})?\s*)?\)$~u';

    /**
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * UniversalFilmParser constructor.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->url = $link;
        $this->getPageContent();
        $this->xpath = new \DOMXPath($this->dom);
    }

    /**
     * Парсит страницу фильма и возвращает нужную информацию
     *
     * @return array
     */
    public function parse(): array
    {
        $sources = [
            $this->parseJsonLd(),
            $this->parseMicrodata(),
            $this->parseOpenGraph(),
            $this->parseLabels(),
        ];

        $data = [];
        foreach ($sources as $source) {
            foreach ($source as $field => $value) {
                if (empty($data[$field]) && !empty($value)) {
                    $data[$field] = $value;
                }
            }
        }

        $result = [];

        $rawTitle = $data['title'] ?? $this->getPostTitle();
        $result['title'] = $this->cleanTitle($rawTitle);

        foreach (['film_genres', 'film_countries', 'film_directors', 'film_actors'] as $field) {
            if (!empty($data[$field])) {
                $result[$field] = $this->normalizeList($data[$field]);
            }
        }

        $year = $this->extractYear($data['film_year'] ?? '');
        // года нигде нет — но он мог быть в названии: "Драйв (2026)"
        if ($year === '' && preg_match(self::TITLE_YEAR_PATTERN, $this->cleanText($rawTitle), $matches)) {
            $year = $matches[1];
        }
        if ($year !== '') {
            $result['film_year'] = $year;
        }

        $imdbRating = $this->extractRating($data['imdb_rating'] ?? '') ?: $this->findImdbRatingInText();
        if ($imdbRating === '' && !empty($data['aggregate_rating'])) {
            $imdbRating = $data['aggregate_rating'];
        }
        if ($imdbRating !== '') {
            $result['imdb_rating'] = $imdbRating;
        }

        $duration = $this->pickDuration(array_column($sources, 'film_duration'));
        if ($duration !== '') {
            $result['film_duration'] = $duration;
        }

        if (!empty($data['mpaa_rating'])) {
            $result['mpaa_rating'] = $this->cleanText($data['mpaa_rating']);
        }

        $originalTitle = $this->cleanText($data['film_original_title'] ?? '');
        // на некоторых сайтах "оригинальное название" совпадает с русским — тогда оно не нужно
        if ($originalTitle !== '' && mb_strtolower($originalTitle) !== mb_strtolower($result['title'])) {
            $result['film_original_title'] = $originalTitle;
        }

        if (!empty($data['film_kind'])) {
            $result['film_kind'] = $data['film_kind'];
        }

        $seasons = preg_match('~\d+~', $data['film_seasons'] ?? '', $matches) ? $matches[0] : '';
        if ($seasons !== '' && $seasons !== '0') {
            $result['film_seasons'] = $seasons;
            // сезоны есть — значит сериал, даже если тип не нашёлся
            if (empty($result['film_kind']) || $result['film_kind'] === 'Фильм') {
                $result['film_kind'] = 'Сериал';
            }
        }

        if (!empty($data['film_description'])) {
            $result['film_description'] = $this->cleanText($data['film_description']);
        }

        // alias
        $result['alias'] = str_slug($result['title']);

        if (!empty($data['image'])) {
            $images = $this->savePoster($this->absoluteUrl($data['image']), $result['alias']);
            if (!empty($images)) {
                $result['images'] = $images;
            }
        }

        return array_filter($result, function ($value) {
            return $value !== '' && $value !== [];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Источники данных
    |--------------------------------------------------------------------------
    */

    /**
     * JSON-LD: <script type="application/ld+json">{"@type": "Movie", ...}</script>
     *
     * @return array
     */
    private function parseJsonLd(): array
    {
        $scripts = $this->xpath->query('//script[contains(@type, "ld+json")]');

        foreach ($scripts as $script) {
            $json = trim($script->textContent);
            // некоторые сайты оставляют в JSON переносы строк и табы внутри строк
            $json = preg_replace('/[\x00-\x1F]+/u', ' ', $json);
            $decoded = json_decode($json, true);

            if (!is_array($decoded)) {
                continue;
            }

            $item = $this->findFilmItem($decoded);
            if ($item === null) {
                continue;
            }

            $rating = $item['aggregateRating'] ?? [];
            $bestRating = (float)($rating['bestRating'] ?? 10);

            return [
                'title' => $this->jsonText($item['name'] ?? ''),
                'film_original_title' => $this->jsonText($item['alternateName'] ?? $item['alternativeHeadline'] ?? ''),
                'film_kind' => $this->filmKindByType((array)$item['@type']),
                'film_seasons' => $this->jsonText($item['numberOfSeasons'] ?? ''),
                'film_genres' => $this->jsonNames($item['genre'] ?? []),
                'film_countries' => $this->jsonNames($item['countryOfOrigin'] ?? []),
                'film_directors' => $this->jsonNames($item['director'] ?? []),
                'film_actors' => $this->jsonNames($item['actor'] ?? $item['actors'] ?? []),
                'film_year' => $this->jsonText($item['datePublished'] ?? $item['dateCreated'] ?? $item['startDate'] ?? ''),
                'film_duration' => $this->jsonText($item['duration'] ?? $item['timeRequired'] ?? ''),
                'mpaa_rating' => $this->jsonText($item['contentRating'] ?? ''),
                'film_description' => $this->jsonText($item['description'] ?? ''),
                'image' => $this->jsonImage($item['image'] ?? $item['thumbnailUrl'] ?? ''),
                // рейтинг сайта, а не IMDb — берём, только если по IMDb ничего не нашлось и шкала десятибалльная
                'aggregate_rating' => $bestRating == 10 ? $this->extractRating($this->jsonText($rating['ratingValue'] ?? '')) : '',
            ];
        }

        return [];
    }

    /**
     * Ищет в JSON-LD объект с типом фильма/сериала (в том числе внутри @graph и массивов)
     *
     * @param array $data
     * @return array|null
     */
    private function findFilmItem(array $data): ?array
    {
        $types = array_map('strtolower', (array)($data['@type'] ?? []));
        if (array_intersect($types, self::FILM_TYPES)) {
            return $data;
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $item = $this->findFilmItem($value);
                if ($item !== null) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Тип для поля "Тип" на странице создания поста по schema.org @type / itemtype
     *
     * @param array $types
     * @return string
     */
    private function filmKindByType(array $types): string
    {
        $types = array_map('strtolower', $types);

        if (array_intersect($types, ['tvseries', 'tvseason', 'tvepisode', 'episode', 'creativeworkseries', 'creativeworkseason'])) {
            return 'Сериал';
        }
        if (array_intersect($types, ['movie', 'film'])) {
            return 'Фильм';
        }

        return '';
    }

    /**
     * Микроразметка: <div itemscope itemtype="http://schema.org/Movie"> ... itemprop="genre" ...
     *
     * @return array
     */
    private function parseMicrodata(): array
    {
        $scope = null;
        foreach ($this->xpath->query('//*[@itemscope][@itemtype]') as $node) {
            $type = strtolower(basename(rtrim($node->getAttribute('itemtype'), '/')));
            if (in_array($type, self::FILM_TYPES, true)) {
                $scope = $node;
                break;
            }
        }

        $root = $scope ?? $this->dom->documentElement;
        if ($root === null) {
            return [];
        }

        $result = [
            'film_kind' => $scope !== null ? $this->filmKindByType([$type]) : '',
            'film_original_title' => $this->itempropValues($root, 'alternateName')[0] ?? $this->itempropValues($root, 'alternativeHeadline')[0] ?? '',
            'film_seasons' => $this->itempropValues($root, 'numberOfSeasons')[0] ?? '',
            'film_genres' => $this->itempropValues($root, 'genre'),
            'film_countries' => $this->itempropValues($root, 'countryOfOrigin'),
            'film_directors' => $this->itempropValues($root, 'director'),
            'film_actors' => $this->itempropValues($root, 'actor') ?: $this->itempropValues($root, 'actors'),
            'film_year' => $this->itempropValues($root, 'datePublished')[0] ?? $this->itempropValues($root, 'dateCreated')[0] ?? '',
            'film_duration' => $this->itempropValues($root, 'duration')[0] ?? '',
            'mpaa_rating' => $this->itempropValues($root, 'contentRating')[0] ?? '',
            'film_description' => $this->itempropValues($root, 'description')[0] ?? '',
            'image' => $this->itempropValues($root, 'image')[0] ?? '',
        ];

        // itemprop="name" есть и у актёров/режиссёров, поэтому название берём только у самого фильма
        if ($scope !== null) {
            foreach ($this->xpath->query('.//*[@itemprop="name"]', $scope) as $node) {
                if ($this->closestScope($node) === $scope) {
                    $result['title'] = $this->propValue($node);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Open Graph и обычные мета-теги
     *
     * @return array
     */
    private function parseOpenGraph(): array
    {
        $meta = function (string $name, bool $all = false) {
            $nodes = $this->xpath->query('//meta[@property="' . $name . '" or @name="' . $name . '"]');
            $values = [];
            foreach ($nodes as $node) {
                $value = trim($node->getAttribute('content'));
                if ($value !== '') {
                    $values[] = $value;
                }
            }

            return $all ? $values : ($values[0] ?? '');
        };

        // в video:director / video:actor часто лежат ссылки на профили, а не имена
        $notUrl = function (array $values) {
            return array_values(array_filter($values, function ($value) {
                return !preg_match('~^https?://~i', $value);
            }));
        };

        $duration = $meta('video:duration');

        return [
            'title' => $meta('og:title'),
            'film_directors' => $notUrl($meta('video:director', true)),
            'film_actors' => $notUrl($meta('video:actor', true)),
            'film_year' => $meta('video:release_date'),
            // video:duration — в секундах
            'film_duration' => ctype_digit($duration) ? 'PT' . $duration . 'S' : $duration,
            'film_description' => $meta('og:description') ?: $meta('description'),
            'image' => $meta('og:image') ?: $meta('og:image:url') ?: $meta('twitter:image'),
        ];
    }

    /**
     * Текстовые подписи: "<li><b>Жанр:</b> драма, комедия</li>", "<tr><td>Страна</td><td>США</td></tr>",
     * "<dt>Режиссёр</dt><dd>...</dd>"
     *
     * @return array
     */
    private function parseLabels(): array
    {
        $labelToField = [];
        foreach (self::LABELS as $field => $labels) {
            foreach ($labels as $label) {
                $labelToField[$label] = $field;
            }
        }

        // длинные подписи первыми, чтобы "рейтинг imdb" не перехватывалось "imdb"
        uksort($labelToField, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        $labelsPattern = implode('|', array_map(function ($label) {
            return preg_quote($label, '~');
        }, array_keys($labelToField)));

        $found = [];
        $nodes = $this->xpath->query('//body//*[not(self::script or self::style or self::noscript)]');

        foreach ($nodes as $node) {
            // быстрый отсев больших контейнеров до дорогого разбора текста
            if (strlen($node->textContent) > 5000) {
                continue;
            }

            $text = $this->cleanText($this->spacedText($node));
            $length = mb_strlen($text);
            if ($length === 0 || $length > 1000) {
                continue;
            }

            // "Жанр: драма, комедия" внутри одного элемента
            if (preg_match('~^(' . $labelsPattern . ')\s*[:：]\s*(.+)$~iu', $text, $matches)) {
                $field = $labelToField[mb_strtolower($matches[1])];
                $value = $this->linksAsList([$node], $field) ?: $matches[2];
            // "Жанр" / "Жанр:" — сама подпись, значение рядом
            } elseif ($length <= 40 && preg_match('~^(' . $labelsPattern . ')\s*[:：]?$~iu', $text, $matches)) {
                $field = $labelToField[mb_strtolower($matches[1])];
                $value = $this->valueNextToLabel($node, $field);
            } else {
                continue;
            }

            // trim() с многобайтными символами в списке режет кириллицу по байтам, поэтому регулярка
            $value = preg_replace('~^[\s:：]+|[\s:：]+$~u', '', $value);
            if ($value === '') {
                continue;
            }

            // из нескольких совпавших элементов (li внутри ul внутри div) берём самый короткий — он точнее
            if (!isset($found[$field]) || mb_strlen($value) < mb_strlen($found[$field])) {
                $found[$field] = $value;
            }
        }

        return $found;
    }

    /*
    |--------------------------------------------------------------------------
    | Вспомогательные методы
    |--------------------------------------------------------------------------
    */

    /**
     * Значение рядом с подписью:
     *  - <tr><td>Жанр</td><td>драма</td></tr>, <dt>Жанр</dt><dd>драма</dd> — следующий элемент;
     *  - <li><b>Жанр:</b> драма, <a>комедия</a></li> — всё, что идёт после подписи в родителе.
     *
     * @param \DOMElement $label
     * @param string $field
     * @return string
     */
    private function valueNextToLabel(\DOMElement $label, string $field): string
    {
        $parent = $label->parentNode;
        $following = [];
        for ($sibling = $label->nextSibling; $sibling !== null; $sibling = $sibling->nextSibling) {
            if (trim($sibling->textContent) !== '') {
                $following[] = $sibling;
            }
        }

        if (empty($following)) {
            return '';
        }

        $isCell = in_array(strtolower($label->nodeName), ['dt', 'th', 'td'], true)
            || ($parent !== null && in_array(strtolower($parent->nodeName), ['dl', 'tr', 'tbody', 'table'], true));

        if (!$isCell) {
            $value = $this->linksAsList($following, $field) ?: $this->nodesText($following);
            if (mb_strlen($value) <= 300) {
                return $value;
            }
        }

        // ячейка таблицы или в родителе ещё много чего — берём только ближайший элемент
        return $this->linksAsList([$following[0]], $field) ?: $this->nodesText([$following[0]]);
    }

    /**
     * Для списков (актёры, жанры...) — тексты ссылок через запятую, если их несколько:
     * <a>Том Харди</a><a>Шарлиз Терон</a> -> "Том Харди, Шарлиз Терон"
     *
     * @param \DOMNode[] $nodes
     * @param string $field
     * @return string
     */
    private function linksAsList(array $nodes, string $field): string
    {
        if (!in_array($field, ['film_genres', 'film_countries', 'film_directors', 'film_actors'], true)) {
            return '';
        }

        $names = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $links = strtolower($node->nodeName) === 'a' ? [$node] : $node->getElementsByTagName('a');
            foreach ($links as $link) {
                $name = $this->cleanText($link->textContent);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return count($names) >= 2 ? implode(', ', $names) : '';
    }

    /**
     * @param \DOMNode[] $nodes
     * @return string
     */
    private function nodesText(array $nodes): string
    {
        $text = '';
        foreach ($nodes as $node) {
            $text .= ' ' . $this->spacedText($node);
        }

        return $this->cleanText($text);
    }

    /**
     * textContent, но с пробелами между элементами: "<td>Жанр</td><td>драма</td>" -> "Жанр драма"
     *
     * @param \DOMNode $node
     * @return string
     */
    private function spacedText(\DOMNode $node): string
    {
        if (!$node instanceof \DOMElement) {
            return $node->textContent;
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->nodeName);
                if (in_array($tag, ['script', 'style', 'noscript'], true)) {
                    continue;
                }
                $text .= ' ' . $this->spacedText($child) . ' ';
            } else {
                $text .= $child->textContent;
            }
        }

        return $text;
    }

    /**
     * Значения itemprop внутри области (для людей — их itemprop="name")
     *
     * @param \DOMNode $root
     * @param string $prop
     * @return array
     */
    private function itempropValues(\DOMNode $root, string $prop): array
    {
        $values = [];
        $nodes = $this->xpath->query('.//*[@itemprop="' . $prop . '"]', $root);

        foreach ($nodes as $node) {
            if ($node->hasAttribute('itemscope')) {
                $nameNode = $this->xpath->query('.//*[@itemprop="name"]', $node)->item(0);
                $value = $nameNode ? $this->propValue($nameNode) : $this->cleanText($node->textContent);
            } else {
                $value = $this->propValue($node);
            }

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Значение элемента с itemprop по правилам microdata
     *
     * @param \DOMElement $node
     * @return string
     */
    private function propValue(\DOMElement $node): string
    {
        foreach (['content', 'datetime'] as $attribute) {
            if ($node->hasAttribute($attribute)) {
                return trim($node->getAttribute($attribute));
            }
        }

        $tag = strtolower($node->nodeName);
        if (in_array($tag, ['img', 'source', 'video', 'audio', 'iframe'], true)) {
            return trim($node->getAttribute('data-src') ?: $node->getAttribute('src'));
        }
        if (in_array($tag, ['a', 'link'], true) && $node->getAttribute('itemprop') === 'image') {
            return trim($node->getAttribute('href'));
        }

        return $this->cleanText($node->textContent);
    }

    /**
     * Ближайший родитель с itemscope
     *
     * @param \DOMNode $node
     * @return \DOMNode|null
     */
    private function closestScope(\DOMNode $node): ?\DOMNode
    {
        for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
            if ($parent->hasAttribute('itemscope')) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * Строка из значения JSON-LD
     *
     * @param mixed $value
     * @return string
     */
    private function jsonText($value): string
    {
        if (is_array($value)) {
            $value = $value['name'] ?? $value['@value'] ?? reset($value);
        }

        return is_scalar($value) ? $this->cleanText((string)$value) : '';
    }

    /**
     * Имена из значения JSON-LD: "драма", ["драма", "комедия"], {"name": "..."}, [{"@type": "Person", "name": "..."}]
     *
     * @param mixed $value
     * @return array
     */
    private function jsonNames($value): array
    {
        if (is_string($value)) {
            return [$this->cleanText($value)];
        }
        if (!is_array($value)) {
            return [];
        }
        if (isset($value['name'])) {
            return [$this->jsonText($value['name'])];
        }

        $names = [];
        foreach ($value as $item) {
            $names = array_merge($names, $this->jsonNames($item));
        }

        return $names;
    }

    /**
     * URL картинки из JSON-LD: "url", {"url": "..."}, ["url", ...]
     *
     * @param mixed $value
     * @return string
     */
    private function jsonImage($value): string
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value)) {
            if (isset($value['url']) || isset($value['contentUrl'])) {
                return $this->jsonImage($value['url'] ?? $value['contentUrl']);
            }

            return $this->jsonImage(reset($value));
        }

        return '';
    }

    /**
     * Список из строки "драма, комедия / триллер" или массива; без дублей и "и др."
     *
     * @param array|string $value
     * @return array
     */
    private function normalizeList($value): array
    {
        $items = [];
        foreach ((array)$value as $item) {
            foreach (preg_split('~\s*[,;/|•·]\s*~u', $this->cleanText((string)$item)) as $part) {
                $part = trim(preg_replace('~\s*(и\s+др(угие)?|та\s+ін(ші)?|and\s+others|\.\.\.|…)\.?$~iu', '', $part), " .\t");
                if ($part !== '') {
                    $items[mb_strtolower($part)] = $part;
                }
            }
        }

        return array_slice(array_values($items), 0, self::MAX_LIST_ITEMS);
    }

    /**
     * @param string $value - "2021", "2021-05-14", "14 мая 2021 г."
     * @return string
     */
    private function extractYear(string $value): string
    {
        return preg_match('~\b(18|19|20)\d{2}\b~', $value, $matches) ? $matches[0] : '';
    }

    /**
     * @param string $value - "7.5", "7,5 (123 456)", "IMDb 7.5/10"
     * @return string
     */
    private function extractRating(string $value): string
    {
        if (preg_match('~\b(10|\d)([.,]\d{1,2})?\b~', $value, $matches)) {
            return str_replace(',', '.', $matches[0]);
        }

        return '';
    }

    /**
     * Рейтинг IMDb, написанный просто текстом: "IMDb: 7.5", "IMDb 7,5"
     *
     * @return string
     */
    private function findImdbRatingInText(): string
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }

        if (preg_match('~IMDb\D{0,20}?(\d(?:[.,]\d{1,2})?|10)\b~iu', $this->cleanText($body->textContent), $matches)) {
            return str_replace(',', '.', $matches[1]);
        }

        return '';
    }

    /**
     * Длительность из всех источников (в порядке приоритета). Берётся первая правдоподобная:
     * сайты ошибаются в разметке — например, uakinogo.io пишет в JSON-LD "PT1M" при "Тривалість: 1 ч 43 мин"
     * на странице, поэтому значения короче MIN_DURATION_MINUTES пропускаем и смотрим следующий источник.
     *
     * @param array $values
     * @return string - "1 ч 43 мин"; текст, который не удалось разобрать, — как есть; '' — ничего не нашлось
     */
    private function pickDuration(array $values): string
    {
        $unparsed = '';

        foreach ($values as $value) {
            $value = $this->cleanText((string)$value);
            if ($value === '') {
                continue;
            }

            $minutes = $this->durationMinutes($value);
            if ($minutes === null) {
                // непонятный формат — запасной вариант, если нигде не найдётся нормального
                $unparsed = $unparsed ?: $value;
            } elseif ($minutes >= self::MIN_DURATION_MINUTES) {
                return $this->formatMinutes($minutes);
            }
        }

        return $unparsed;
    }

    /**
     * "PT1H43M" / "PT6180S" / "103" / "01:43:00" / "1 ч 43 мин" / "1 год. 43 хв." / "103 min" -> минуты
     *
     * @param string $value
     * @return int|null - null, если формат не распознан
     */
    private function durationMinutes(string $value): ?int
    {
        // ISO 8601
        if (preg_match('~^P(?:\d+D)?T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$~i', $value, $matches)) {
            return (int)($matches[1] ?? 0) * 60 + (int)($matches[2] ?? 0) + intdiv((int)($matches[3] ?? 0), 60);
        }
        // просто число — минуты (video:duration в секундах сюда приходит уже как ISO, см. parseOpenGraph)
        if (ctype_digit($value)) {
            return (int)$value;
        }
        // 01:43:00 / 1:43
        if (preg_match('~^(\d{1,2}):(\d{2})(?::\d{2})?$~', $value, $matches)) {
            return (int)$matches[1] * 60 + (int)$matches[2];
        }

        // текстом: часы (ч, час, год, h, hr, hour) и/или минуты (мин, хв, m, min)
        $hours = preg_match('~(\d+)\s*(?:ч|час|год|h|hr|hour)~iu', $value, $matches) ? (int)$matches[1] : null;
        $minutes = preg_match('~(\d+)\s*(?:мин|хв|m\b|min)~iu', $value, $matches) ? (int)$matches[1] : null;

        if ($hours === null && $minutes === null) {
            return null;
        }

        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * 103 -> "1 ч 43 мин"
     *
     * @param int $minutes
     * @return string
     */
    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        return trim(($hours ? $hours . ' ч ' : '') . ($minutes ? $minutes . ' мин' : ''));
    }

    /**
     * Убирает из названия хвосты вида " смотреть онлайн в HD", " | Название сайта"
     *
     * @param string $title
     * @return string
     */
    private function cleanTitle(string $title): string
    {
        $title = $this->cleanText($title);
        $title = preg_replace('~\s+(смотреть|дивитися|watch)\s.*$~iu', '', $title);
        // тире не трогаем — оно бывает частью названия ("Звёздные войны - Эпизод IV")
        $title = preg_replace('~\s+\|\s+.*$~u', '', $title);
        // год в скобках в конце ("Драйв (2026)", "Во все тяжкие (2008–2013)") — для него есть отдельное поле
        $title = preg_replace(self::TITLE_YEAR_PATTERN, '', $title);

        return preg_replace('~^[\s\-—–|]+|[\s\-—–|]+$~u', '', $title);
    }

    /**
     * Убирает теги, html-сущности и лишние пробелы
     *
     * @param string $text
     * @return string
     */
    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('~[\s\x{00A0}]+~u', ' ', $text));
    }

    /**
     * Относительный URL картинки -> абсолютный
     *
     * @param string $url
     * @return string
     */
    private function absoluteUrl(string $url): string
    {
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        $parts = parse_url($this->url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (strpos($url, '//') === 0) {
            return $scheme . ':' . $url;
        }
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }

        $path = preg_replace('~/[^/]*$~', '/', $parts['path'] ?? '/');

        return $scheme . '://' . $host . $path . $url;
    }

    /**
     * Скачивает постер во временную папку пользователя (как при обычной загрузке картинки)
     *
     * @param string $imageUrl
     * @param string $alias
     * @return array
     */
    private function savePoster(string $imageUrl, string $alias): array
    {
        $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $name = $alias ?: str_slug(pathinfo($path, PATHINFO_FILENAME));
        if ($name === '') {
            $name = 'poster-' . time();
        }

        try {
            $images = (new UploadImageController())->saveParseImageToTempDirectory($imageUrl, [
                'name' => $name,
                'extension' => $extension,
            ]);
        } catch (\Throwable $exception) {
            return [];
        }

        // при ошибке saveParseImageToTempDirectory возвращает JsonResponse
        return is_array($images) ? $images : [];
    }

}
