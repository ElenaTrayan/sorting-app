<?php

namespace App\Components;

use App\Models\Hashtag;
use App\Models\PostFilm;

/**
 * Хештеги из данных фильма: тип ("фильмы"/"сериалы"...), статус просмотра, жанры, год, страны, режиссёры
 * и главные актёры. На форме поста они сразу попадают в выбранные хештеги — после парсинга и при правке
 * полей (film-fields.js -> HashtagController::forFilm), а на сервер уходят вместе с остальными выбранными.
 * Сам пост при сохранении их не добавляет — иначе хештег, убранный пользователем крестиком, вернулся бы.
 *
 * Существующие хештеги пользователя переиспользуются (без учёта регистра и ё/е), недостающие создаются.
 * Для жанров, если точного совпадения нет, ищется форма с другим окончанием — чтобы "комедия" с сайта
 * попала в уже существующий "комедии", а не завела рядом дубль.
 */
class FilmHashtags
{
    // тип -> хештег во множественном числе, как уже заведённые у пользователя "фильмы"/"сериалы"
    private const KIND_HASHTAGS = [
        'Фильм' => 'фильмы',
        'Сериал' => 'сериалы',
        'Мини-сериал' => 'сериалы',
        'Мультфильм' => 'мультфильмы',
        'Мультсериал' => 'мультсериалы',
        'Аниме' => 'аниме',
        'Документальный' => 'документальные фильмы',
    ];

    // статус просмотра -> хештег без "(а)": по нему удобно собрать "хочу посмотреть" в одну подборку
    private const STATUS_HASHTAGS = [
        'Посмотрел(а)' => 'просмотрено',
        'Смотрю' => 'смотрю',
        'Хочу посмотреть' => 'хочу посмотреть',
        'Бросил(а)' => 'брошено',
    ];

    // актёров на сайтах бывает 20+ — хештегами только главные роли (первые в списке)
    private const MAX_ACTORS = 5;
    private const MAX_DIRECTORS = 3;

    // длина hashtags.title
    private const MAX_TITLE_LENGTH = 150;

    /**
     * Находит/создаёт хештеги пользователя по данным фильма
     *
     * @param int $userId
     * @param PostFilm $film - может быть и не сохранённым (данные прямо с формы)
     * @return Hashtag[] - без повторов, в порядке: тип, статус, жанры, год, страны, режиссёры, актёры
     */
    public static function resolve(int $userId, PostFilm $film): array
    {
        $titles = self::titles($film);
        if (empty($titles)) {
            return [];
        }

        $byKey = [];
        foreach (Hashtag::where('user_id', $userId)->get(['id', 'title']) as $hashtag) {
            $byKey[self::key($hashtag->title)] = $hashtag;
        }

        $hashtags = [];
        foreach ($titles as $title => $isGenre) {
            $hashtag = $byKey[self::key($title)] ?? ($isGenre ? self::findGenreVariant($title, $byKey) : null);

            if ($hashtag === null) {
                $hashtag = new Hashtag();
                $hashtag->title = $title;
                $hashtag->parent_id = 0;
                $hashtag->user_id = $userId;
                $hashtag->save();
                $byKey[self::key($title)] = $hashtag;
            }

            // "Комедии" и "комедия" могли свестись к одному хештегу
            $hashtags[$hashtag->id] = $hashtag;
        }

        return array_values($hashtags);
    }

    /**
     * Один хештег пользователя по названию: существующий (без учёта регистра и ё/е) или новый
     *
     * @param int $userId
     * @param string $title
     * @return Hashtag
     */
    public static function findOrCreate(int $userId, string $title): Hashtag
    {
        $title = mb_substr(trim(preg_replace('~\s+~u', ' ', $title)), 0, self::MAX_TITLE_LENGTH);
        $key = self::key($title);

        foreach (Hashtag::where('user_id', $userId)->get(['id', 'title']) as $hashtag) {
            if (self::key($hashtag->title) === $key) {
                return $hashtag;
            }
        }

        $hashtag = new Hashtag();
        $hashtag->title = $title;
        $hashtag->parent_id = 0;
        $hashtag->user_id = $userId;
        $hashtag->save();

        return $hashtag;
    }

    /**
     * Названия хештегов из данных фильма
     *
     * @param PostFilm $film
     * @return array [название => это жанр?]
     */
    public static function titles(PostFilm $film): array
    {
        $titles = [];
        $add = function (?string $title, bool $isGenre = false) use (&$titles) {
            $title = trim(preg_replace('~\s+~u', ' ', (string)$title), " \t.,;");
            if ($title !== '' && mb_strlen($title) <= self::MAX_TITLE_LENGTH) {
                $titles[$title] = $isGenre;
            }
        };

        if ($film->kind) {
            $add(self::KIND_HASHTAGS[$film->kind] ?? mb_strtolower($film->kind));
        }

        if ($film->status) {
            $add(self::STATUS_HASHTAGS[$film->status] ?? mb_strtolower($film->status));
        }

        // жанры — строчными, как остальные хештеги ("комедии", "боевик")
        foreach (self::split($film->genres) as $genre) {
            $add(mb_strtolower($genre), true);
        }

        if ($film->year) {
            $add((string)$film->year);
        }

        // страны и имена — как написаны ("США", "Марго Робби")
        foreach (self::split($film->countries) as $country) {
            $add($country);
        }
        foreach (array_slice(self::split($film->directors), 0, self::MAX_DIRECTORS) as $director) {
            $add($director);
        }
        foreach (array_slice(self::split($film->actors), 0, self::MAX_ACTORS) as $actor) {
            $add($actor);
        }

        return $titles;
    }

    /**
     * "драма, комедия / триллер" -> ["драма", "комедия", "триллер"]
     *
     * @param string|null $value
     * @return array
     */
    private static function split(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = preg_split('~\s*[,;/|•·]\s*~u', $value);
        // "и др." в конце списка актёров
        $items = array_map(function ($item) {
            return trim(preg_replace('~\s*(и\s+др(угие)?|та\s+ін(ші)?|and\s+others)\.?$~iu', '', $item));
        }, $items);

        return array_values(array_filter($items, function ($item) {
            return $item !== '';
        }));
    }

    /**
     * Ключ для сравнения: без регистра, ё = е, пробелы схлопнуты
     *
     * @param string $title
     * @return string
     */
    private static function key(string $title): string
    {
        return str_replace('ё', 'е', mb_strtolower(trim(preg_replace('~\s+~u', ' ', $title))));
    }

    /**
     * Жанр в другой форме среди существующих хештегов: "комедия" <-> "комедии", "боевик" <-> "боевики",
     * "драма" <-> "драмы" — сравниваем без последней гласной/ь
     *
     * @param string $genre
     * @param Hashtag[] $byKey
     * @return Hashtag|null
     */
    private static function findGenreVariant(string $genre, array $byKey): ?Hashtag
    {
        $stem = function (string $key): string {
            return preg_replace('~[аяыиеь]$~u', '', $key);
        };

        $genreStem = $stem(self::key($genre));
        // слишком короткая основа совпадёт с чем попало
        if (mb_strlen($genreStem) < 4) {
            return null;
        }

        foreach ($byKey as $key => $hashtag) {
            if ($stem($key) === $genreStem) {
                return $hashtag;
            }
        }

        return null;
    }
}
