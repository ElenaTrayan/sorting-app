<?php

namespace App\Components;

/**
 * Палитра главных цветов изображения — для posts.palette (полоска цветов на странице поста и точки
 * на карточке в "Все посты").
 *
 * Картинка уменьшается до SAMPLE_SIZE px по большей стороне, пиксели группируются k-means'ом.
 * Начальные центры — самые частые цвета (с шагом 16 по каждому каналу), поэтому результат
 * для одной и той же картинки всегда одинаковый. Близкие друг к другу цвета сливаются в один,
 * совсем мелкие (меньше MIN_SHARE площади) отбрасываются. Только GD, без внешних библиотек.
 *
 * Фон: цвет, который занимает большую часть краёв картинки (BORDER_WIDTH px по периметру
 * уменьшенной копии), помечается 'background' => true и ставится в конец палитры — главными
 * считаются цвета того, что на картинке, а не подложки. В поиске по цвету у фона меньший вес
 * (ColorMatch::BACKGROUND_WEIGHT).
 */
class ImagePalette
{
    private const SAMPLE_SIZE = 64;
    private const MAX_ITERATIONS = 12;

    // начальные центры не ближе этого — иначе k-means стартует с оттенков одного цвета
    private const MIN_INIT_DISTANCE = 48;
    // итоговые цвета ближе этого — один и тот же цвет, сливаем
    private const MERGE_DISTANCE = 28;
    private const MIN_SHARE = 0.02;
    // пиксель прозрачнее этого (0 — непрозрачный, 127 — прозрачный) не учитываем
    private const MAX_ALPHA = 100;

    // sharesFor(): пиксель дальше этого (по RGB) от всех заданных цветов ни к одному не засчитывается
    private const SHARE_MATCH_DISTANCE = 60;

    // ширина рамки по краям уменьшенной копии, по которой ищем фон
    private const BORDER_WIDTH = 2;
    // цвет — фон, если занимает не меньше этой доли рамки. Крупный план, где предмет упирается в края
    // (рука на весь кадр), тоже может уйти в фон — такое правится вручную. Проверка "фона нет в центре"
    // не помогла: у документа на цветной подложке фон и в центре, а у руки центр занят ногтями
    private const BACKGROUND_BORDER_SHARE = 0.6;

    /**
     * @param string $absolutePath
     * @param int $maxColors
     * @return array [['hex' => '#D8C3A5', 'share' => 0.34], ..., ['hex' => '#FAFAFA', 'share' => 0.5, 'background' => true]] —
     *               цвета по убыванию доли, фон (если нашёлся) — последним; [] если картинку не прочитать
     */
    public static function fromFile(string $absolutePath, int $maxColors = 6): array
    {
        $sample = self::samplePixels($absolutePath);
        $pixels = $sample['pixels'];
        if (empty($pixels)) {
            return [];
        }

        $clusters = self::kMeans($pixels, self::initialCenters($pixels, $maxColors));
        $clusters = self::mergeClose($clusters);

        $total = count($pixels);
        $palette = [];
        foreach ($clusters as $cluster) {
            $share = $cluster['count'] / $total;
            if ($share < self::MIN_SHARE) {
                continue;
            }
            $palette[] = [
                'center' => $cluster['center'],
                'hex' => sprintf('#%02X%02X%02X', ...array_map('round', $cluster['center'])),
                'share' => round($share, 3),
            ];
        }

        $backgroundIndex = self::findBackground($palette, $sample['border']);

        foreach ($palette as $i => &$color) {
            unset($color['center']);
            if ($i === $backgroundIndex) {
                $color['background'] = true;
            }
        }
        unset($color);

        // сначала цвета того, что на картинке, по убыванию доли; фон — в конце
        usort($palette, function ($a, $b) {
            return [!empty($a['background']), $b['share']] <=> [!empty($b['background']), $a['share']];
        });

        return $palette;
    }

    /**
     * Доли заданных цветов на картинке — для палитры, поправленной вручную (удалили цвет, добавили пипеткой):
     * пиксель засчитывается ближайшему из цветов, если он к нему достаточно близок (SHARE_MATCH_DISTANCE).
     * Пиксели, не похожие ни на один цвет (например, убранного фона), ни к кому не прибавляются —
     * иначе удалённый фон раздувал бы доли оставшихся цветов.
     *
     * @param string $absolutePath
     * @param array $hexes ['#D8C3A5', ...]
     * @return array|null доли в том же порядке; null — картинку не прочитать
     */
    public static function sharesFor(string $absolutePath, array $hexes): ?array
    {
        $pixels = self::samplePixels($absolutePath)['pixels'];
        if (empty($pixels)) {
            return null;
        }

        $centers = array_map(function ($hex) {
            return sscanf($hex, '#%02x%02x%02x');
        }, $hexes);

        $hits = array_fill(0, count($centers), 0);
        foreach ($pixels as $pixel) {
            $nearest = null;
            $nearestDistance = self::SHARE_MATCH_DISTANCE;
            foreach ($centers as $i => $center) {
                $distance = self::distance($pixel, $center);
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearest = $i;
                }
            }
            if ($nearest !== null) {
                $hits[$nearest]++;
            }
        }

        return array_map(function ($count) use ($pixels) {
            return round($count / count($pixels), 3);
        }, $hits);
    }

    /**
     * Индекс цвета палитры, который занимает большую часть краёв картинки, или null.
     * У одноцветной картинки фона не ищем — "фон" без того, что на нём, смысла не имеет.
     *
     * @param array $palette - с 'center' => [r, g, b]
     * @param array $borderPixels - [[r, g, b], ...] пиксели рамки
     * @return int|null
     */
    private static function findBackground(array $palette, array $borderPixels): ?int
    {
        if (count($palette) < 2 || empty($borderPixels)) {
            return null;
        }

        $borderHits = self::nearestColorHits($palette, $borderPixels);
        arsort($borderHits);
        $index = array_key_first($borderHits);

        return $borderHits[$index] / count($borderPixels) >= self::BACKGROUND_BORDER_SHARE ? $index : null;
    }

    /**
     * Сколько пикселей ближе всего к каждому цвету палитры
     *
     * @param array $palette - с 'center' => [r, g, b]
     * @param array $pixels
     * @return array [индекс цвета => число пикселей]
     */
    private static function nearestColorHits(array $palette, array $pixels): array
    {
        $hits = array_fill(0, count($palette), 0);
        foreach ($pixels as $pixel) {
            $nearest = 0;
            $nearestDistance = INF;
            foreach ($palette as $i => $color) {
                $distance = self::distance($pixel, $color['center']);
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearest = $i;
                }
            }
            $hits[$nearest]++;
        }

        return $hits;
    }

    /**
     * Пиксели уменьшенной копии и отдельно — пиксели рамки по краям (для поиска фона)
     *
     * @param string $absolutePath
     * @return array ['pixels' => [[r, g, b], ...], 'border' => [[r, g, b], ...]]
     */
    private static function samplePixels(string $absolutePath): array
    {
        $empty = ['pixels' => [], 'border' => []];
        if (!is_file($absolutePath)) {
            return $empty;
        }

        $data = @file_get_contents($absolutePath);
        $source = $data !== false ? @imagecreatefromstring($data) : false;
        if (!$source) {
            return $empty;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::SAMPLE_SIZE / max($width, $height));
        $sampleWidth = max(1, (int)round($width * $scale));
        $sampleHeight = max(1, (int)round($height * $scale));

        $sample = imagecreatetruecolor($sampleWidth, $sampleHeight);
        imagealphablending($sample, false);
        imagesavealpha($sample, true);
        imagecopyresampled($sample, $source, 0, 0, 0, 0, $sampleWidth, $sampleHeight, $width, $height);
        imagedestroy($source);

        $pixels = [];
        $border = [];
        for ($y = 0; $y < $sampleHeight; $y++) {
            for ($x = 0; $x < $sampleWidth; $x++) {
                $color = imagecolorat($sample, $x, $y);
                if ((($color >> 24) & 0x7F) > self::MAX_ALPHA) {
                    continue;
                }
                $pixel = [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
                $pixels[] = $pixel;

                if ($x < self::BORDER_WIDTH || $y < self::BORDER_WIDTH
                    || $x >= $sampleWidth - self::BORDER_WIDTH || $y >= $sampleHeight - self::BORDER_WIDTH) {
                    $border[] = $pixel;
                }
            }
        }
        imagedestroy($sample);

        return ['pixels' => $pixels, 'border' => $border];
    }

    /**
     * Самые частые цвета (корзины по 16 оттенков на канал), не ближе MIN_INIT_DISTANCE друг к другу
     *
     * @param array $pixels
     * @param int $maxColors
     * @return array [[r, g, b], ...]
     */
    private static function initialCenters(array $pixels, int $maxColors): array
    {
        $buckets = [];
        foreach ($pixels as [$r, $g, $b]) {
            $key = ($r >> 4) << 8 | ($g >> 4) << 4 | ($b >> 4);
            if (!isset($buckets[$key])) {
                $buckets[$key] = [0, 0, 0, 0];
            }
            $buckets[$key][0] += $r;
            $buckets[$key][1] += $g;
            $buckets[$key][2] += $b;
            $buckets[$key][3]++;
        }

        // по убыванию частоты, при равенстве — по ключу, чтобы порядок не зависел от сортировки PHP
        uksort($buckets, function ($a, $b) use ($buckets) {
            return [$buckets[$b][3], $a] <=> [$buckets[$a][3], $b];
        });

        $centers = [];
        foreach ($buckets as [$r, $g, $b, $count]) {
            $color = [$r / $count, $g / $count, $b / $count];
            foreach ($centers as $center) {
                if (self::distance($color, $center) < self::MIN_INIT_DISTANCE) {
                    continue 2;
                }
            }
            $centers[] = $color;
            if (count($centers) >= $maxColors) {
                break;
            }
        }

        return $centers;
    }

    /**
     * @param array $pixels
     * @param array $centers
     * @return array [['center' => [r, g, b], 'count' => N], ...]
     */
    private static function kMeans(array $pixels, array $centers): array
    {
        $assignments = [];

        for ($iteration = 0; $iteration < self::MAX_ITERATIONS; $iteration++) {
            $sums = array_fill(0, count($centers), [0, 0, 0, 0]);
            $changed = false;

            foreach ($pixels as $i => $pixel) {
                $nearest = 0;
                $nearestDistance = PHP_INT_MAX;
                foreach ($centers as $c => $center) {
                    $distance = ($pixel[0] - $center[0]) ** 2 + ($pixel[1] - $center[1]) ** 2 + ($pixel[2] - $center[2]) ** 2;
                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearest = $c;
                    }
                }
                if (($assignments[$i] ?? -1) !== $nearest) {
                    $assignments[$i] = $nearest;
                    $changed = true;
                }
                $sums[$nearest][0] += $pixel[0];
                $sums[$nearest][1] += $pixel[1];
                $sums[$nearest][2] += $pixel[2];
                $sums[$nearest][3]++;
            }

            foreach ($sums as $c => [$r, $g, $b, $count]) {
                if ($count > 0) {
                    $centers[$c] = [$r / $count, $g / $count, $b / $count];
                }
            }

            if (!$changed) {
                break;
            }
        }

        $clusters = [];
        foreach ($sums as $c => $sum) {
            if ($sum[3] > 0) {
                $clusters[] = ['center' => $centers[$c], 'count' => $sum[3]];
            }
        }

        return $clusters;
    }

    /**
     * Сливает кластеры с почти одинаковым цветом (взвешенное среднее по числу пикселей)
     *
     * @param array $clusters
     * @return array
     */
    private static function mergeClose(array $clusters): array
    {
        usort($clusters, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $merged = [];
        foreach ($clusters as $cluster) {
            foreach ($merged as &$target) {
                if (self::distance($cluster['center'], $target['center']) < self::MERGE_DISTANCE) {
                    $total = $target['count'] + $cluster['count'];
                    foreach ([0, 1, 2] as $channel) {
                        $target['center'][$channel] = ($target['center'][$channel] * $target['count']
                            + $cluster['center'][$channel] * $cluster['count']) / $total;
                    }
                    $target['count'] = $total;
                    continue 2;
                }
            }
            unset($target);
            $merged[] = $cluster;
        }
        unset($target);

        return $merged;
    }

    private static function distance(array $a, array $b): float
    {
        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2);
    }
}
