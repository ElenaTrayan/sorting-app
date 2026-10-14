<?php

namespace App\Components;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * Цвета коллекции пользователя — кружки во вкладке "Поиск по цвету": оттенки, которые реально есть
 * в палитрах его постов (posts.palette), с числом постов, которые найдёт поиск по каждому.
 *
 * Все цвета палитр группируются в оттенки (жадно: самый частый цвет — центр оттенка, всё, что ближе
 * CLUSTER_RADIUS по ΔE2000, — в него), для каждого оттенка считается, сколько постов найдёт
 * ColorMatch — то же число, что покажет поиск. Оттенки меньше MIN_POSTS постов не показываем.
 *
 * Считается ~2 с на полторы тысячи постов, поэтому кешируется навсегда и сбрасывается, когда у поста
 * пользователя меняется палитра или пост удаляют (Post::booted) — и после posts:backfill-palettes.
 */
class CollectionColors
{
    private const CLUSTER_RADIUS = 12;
    private const MAX_COLORS = 40;
    private const MIN_POSTS = 3;

    // цвета с насыщенностью (C в LCh) меньше этой — "нейтральные": белый-серый-чёрный, бежево-серые
    private const NEUTRAL_CHROMA = 10;

    public static function cacheKey(int $userId): string
    {
        return 'collection-colors:' . $userId;
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    /**
     * @param int $userId
     * @return array [['hex' => '#D8C3A5', 'name' => 'бежевый', 'count' => 348], ...] — нейтральные от светлых
     *               к тёмным, потом цветные по кругу оттенков
     */
    public static function forUser(int $userId): array
    {
        return Cache::rememberForever(self::cacheKey($userId), function () use ($userId) {
            return self::compute($userId);
        });
    }

    private static function compute(int $userId): array
    {
        $palettes = [];
        $buckets = [];
        foreach (Post::where('user_id', $userId)->whereNotNull('palette')->get(['id', 'palette']) as $post) {
            $palette = [];
            foreach ($post->palette as $color) {
                if (empty($color['hex'])) {
                    continue;
                }
                // вес — как в поиске (у фона понижен), чтобы число на кружке совпадало с результатами
                $weight = ColorMatch::weight($color);
                $palette[] = [ColorMatch::hexToLab($color['hex']), $weight];

                // корзины по 32 оттенка на канал — иначе тысячи почти одинаковых цветов
                [$r, $g, $b] = sscanf($color['hex'], '#%02x%02x%02x');
                $key = sprintf('#%02X%02X%02X', ($r >> 3) << 3 | 4, ($g >> 3) << 3 | 4, ($b >> 3) << 3 | 4);
                $buckets[$key] = ($buckets[$key] ?? 0) + $weight;
            }
            $palettes[] = $palette;
        }
        arsort($buckets);

        $clusters = [];
        foreach ($buckets as $hex => $weight) {
            $lab = ColorMatch::hexToLab($hex);
            foreach ($clusters as &$cluster) {
                if (ColorMatch::deltaE2000($lab, $cluster['lab']) < self::CLUSTER_RADIUS) {
                    $cluster['weight'] += $weight;
                    continue 2;
                }
            }
            unset($cluster);
            $clusters[] = ['hex' => $hex, 'lab' => $lab, 'weight' => $weight];
        }
        unset($cluster);

        // самые частые оттенки; с запасом — часть отсеется по MIN_POSTS
        usort($clusters, function ($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
        $colors = [];
        foreach (array_slice($clusters, 0, self::MAX_COLORS * 2) as $cluster) {
            $count = 0;
            foreach ($palettes as $palette) {
                if (self::score($palette, $cluster['lab']) >= ColorMatch::MIN_SCORE) {
                    $count++;
                }
            }
            if ($count >= self::MIN_POSTS) {
                $colors[] = $cluster + ['count' => $count];
            }
            if (count($colors) >= self::MAX_COLORS) {
                break;
            }
        }

        usort($colors, function ($a, $b) {
            return self::displayOrder($a['lab']) <=> self::displayOrder($b['lab']);
        });

        return array_map(function ($color) {
            return [
                'hex' => $color['hex'],
                'name' => ColorMatch::nameFor($color['hex']),
                'count' => $color['count'],
            ];
        }, $colors);
    }

    /**
     * То же, что ColorMatch::score(), но палитра уже в Lab — чтобы не пересчитывать для каждого оттенка
     */
    private static function score(array $palette, array $targetLab): float
    {
        $score = 0.0;
        foreach ($palette as [$lab, $weight]) {
            $distance = ColorMatch::deltaE2000($lab, $targetLab);
            if ($distance < ColorMatch::MAX_DISTANCE) {
                $score += $weight * (1 - $distance / ColorMatch::MAX_DISTANCE);
            }
        }

        return $score;
    }

    /**
     * Порядок кружков: сначала нейтральные от светлых к тёмным, потом цветные по кругу оттенков
     * (красные -> жёлтые -> зелёные -> синие -> фиолетовые), внутри оттенка — от светлых к тёмным
     */
    private static function displayOrder(array $lab): array
    {
        [$l, $a, $b] = $lab;
        $chroma = sqrt($a ** 2 + $b ** 2);
        if ($chroma < self::NEUTRAL_CHROMA) {
            return [0, 0, -$l];
        }
        // начало круга — чуть до красного, чтобы малиновые не уехали в конец к фиолетовым
        $hue = fmod(rad2deg(atan2($b, $a)) + 360 + 20, 360);

        return [1, (int)floor($hue / 20), -$l];
    }
}
