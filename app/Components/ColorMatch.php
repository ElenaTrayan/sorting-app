<?php

namespace App\Components;

/**
 * Поиск постов по цвету: насколько палитра поста (posts.palette, App\Components\ImagePalette)
 * похожа на выбранный цвет.
 *
 * Близость цветов — CIEDE2000 (ΔE в пространстве Lab, учитывает, как глаз различает цвета: например,
 * разница между двумя серыми заметнее, чем та же разница в RGB между двумя насыщенными синими).
 * Оценка поста — сумма долей его цветов, взвешенных по близости к искомому: пост, где искомый цвет
 * занимает полкартинки, выше поста, где он — маленькая деталь.
 */
class ColorMatch
{
    // ΔE, дальше которого цвет уже "не тот" (≈ 2.3 — порог, когда разницу вообще замечают)
    public const MAX_DISTANCE = 16;

    // минимальная оценка, чтобы пост попал в результаты: искомый цвет занимает хотя бы ~10% картинки
    public const MIN_SCORE = 0.1;

    // вес фона (цвет с 'background' => true, см. ImagePalette): белая подложка на полкартинки не должна
    // перевешивать белую вещь на ней. Сам фон тоже находится — большой фон набирает MIN_SCORE и так,
    // просто такие посты ниже тех, где этот цвет — у самого предмета
    public const BACKGROUND_WEIGHT = 0.25;

    /**
     * Названия цветов: цвету из коллекции (CollectionColors) достаётся ближайшее по ΔE — подпись к кружку
     * во вкладке "Поиск по цвету". Название => HEX
     */
    public const NAMES = [
        'белый' => '#FFFFFF', 'молочный' => '#F4F0E6', 'слоновая кость' => '#EFE6D2', 'кремовый' => '#F3E5C4',
        'светло-серый' => '#D5D5D5', 'серый' => '#9E9E9E', 'тёмно-серый' => '#6E6E6E', 'графитовый' => '#4A4A4F',
        'чёрный' => '#1E1E1E', 'соломенный' => '#D6C47F', 'абрикосовый' => '#EE9A6C', 'розовое дерево' => '#8F6468',
        'петроль' => '#2F5566',
        'тауп' => '#8E7F72', 'бежевый' => '#D8C3A5', 'песочный' => '#C9B08A', 'карамельный' => '#B5835A',
        'коричневый' => '#6D4C41', 'шоколадный' => '#4A2F27', 'терракотовый' => '#C0613F', 'ржавый' => '#A34A28',
        'персиковый' => '#F5BFA0', 'коралловый' => '#F0806A', 'красный' => '#C62828', 'вишнёвый' => '#99213A',
        'бордовый' => '#7B1E2B', 'малиновый' => '#C2185B', 'фуксия' => '#D6337F', 'розовый' => '#F4A7B9',
        'нежно-розовый' => '#F9D9E3', 'пыльная роза' => '#C9959A', 'пудровый' => '#E8CFC8',
        'лавандовый' => '#B9A7D6', 'сиреневый' => '#A782C0', 'фиолетовый' => '#7B4BA8', 'баклажановый' => '#4A2745',
        'тёмно-синий' => '#1C2A4A', 'синий' => '#1F4E9C', 'джинсовый' => '#4F6D8F', 'васильковый' => '#6F8FD0',
        'голубой' => '#7EC8E8', 'небесный' => '#BFE0F2', 'бирюзовый' => '#2BA8A0', 'аквамарин' => '#6FD8C8',
        'мятный' => '#C4EDDC', 'изумрудный' => '#1F7A5A', 'тёмно-зелёный' => '#24443A', 'зелёный' => '#3E8E47',
        'травяной' => '#7AAE4A', 'салатовый' => '#B5E08A', 'фисташковый' => '#CDEBC4', 'оливковый' => '#8A8A4A',
        'хаки' => '#8C8660', 'горчичный' => '#C9A227', 'жёлтый' => '#F6C83E', 'лимонный' => '#F2E36B',
        'ванильный' => '#FBF3C4', 'оранжевый' => '#EF7A1A',
    ];

    /**
     * Ближайшее название цвета из NAMES
     *
     * @param string $hex
     * @return string
     */
    public static function nameFor(string $hex): string
    {
        $lab = self::hexToLab($hex);
        $best = '';
        $bestDistance = INF;
        foreach (self::NAMES as $name => $namedHex) {
            $distance = self::deltaE2000($lab, self::hexToLab($namedHex));
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $name;
            }
        }

        return $best;
    }

    /**
     * @param array|null $palette [['hex' => '#D8C3A5', 'share' => 0.34], ...]
     * @param array $targetLab - результат hexToLab() искомого цвета
     * @return float 0..1
     */
    public static function score(?array $palette, array $targetLab): float
    {
        $score = 0.0;
        foreach ($palette ?? [] as $color) {
            if (empty($color['hex'])) {
                continue;
            }
            $distance = self::deltaE2000(self::hexToLab($color['hex']), $targetLab);
            if ($distance < self::MAX_DISTANCE) {
                $score += self::weight($color) * (1 - $distance / self::MAX_DISTANCE);
            }
        }

        return $score;
    }

    /**
     * Вклад цвета палитры в оценку: его доля картинки, у фона — с понижающим весом
     *
     * @param array $color ['share' => 0.34, 'background' => true|отсутствует]
     * @return float
     */
    public static function weight(array $color): float
    {
        return ($color['share'] ?? 0) * (empty($color['background']) ? 1 : self::BACKGROUND_WEIGHT);
    }

    /**
     * "#D8C3A5" / "D8C3A5" -> [L, a, b] (sRGB, D65)
     *
     * @param string $hex
     * @return array
     */
    public static function hexToLab(string $hex): array
    {
        static $cache = [];
        $hex = strtoupper(ltrim($hex, '#'));
        if (isset($cache[$hex])) {
            return $cache[$hex];
        }

        $linear = array_map(function ($channel) {
            $channel /= 255;
            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, sscanf($hex, '%02x%02x%02x'));
        [$r, $g, $b] = $linear;

        // sRGB -> XYZ, нормированный на белую точку D65
        $x = ($r * 0.4124 + $g * 0.3576 + $b * 0.1805) / 0.95047;
        $y = ($r * 0.2126 + $g * 0.7152 + $b * 0.0722) / 1.00000;
        $z = ($r * 0.0193 + $g * 0.1192 + $b * 0.9505) / 1.08883;

        $f = function ($t) {
            return $t > 216 / 24389 ? $t ** (1 / 3) : (24389 / 27 * $t + 16) / 116;
        };
        [$fx, $fy, $fz] = [$f($x), $f($y), $f($z)];

        return $cache[$hex] = [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }

    /**
     * CIEDE2000 (Sharma, Wu, Dalal, 2005)
     *
     * @param array $lab1
     * @param array $lab2
     * @return float
     */
    public static function deltaE2000(array $lab1, array $lab2): float
    {
        [$l1, $a1, $b1] = $lab1;
        [$l2, $a2, $b2] = $lab2;

        $c1 = sqrt($a1 ** 2 + $b1 ** 2);
        $c2 = sqrt($a2 ** 2 + $b2 ** 2);
        $cMean = ($c1 + $c2) / 2;
        $g = 0.5 * (1 - sqrt($cMean ** 7 / ($cMean ** 7 + 25 ** 7)));

        $a1p = (1 + $g) * $a1;
        $a2p = (1 + $g) * $a2;
        $c1p = sqrt($a1p ** 2 + $b1 ** 2);
        $c2p = sqrt($a2p ** 2 + $b2 ** 2);

        $h1p = ($a1p == 0 && $b1 == 0) ? 0 : fmod(rad2deg(atan2($b1, $a1p)) + 360, 360);
        $h2p = ($a2p == 0 && $b2 == 0) ? 0 : fmod(rad2deg(atan2($b2, $a2p)) + 360, 360);

        $dLp = $l2 - $l1;
        $dCp = $c2p - $c1p;

        if ($c1p * $c2p == 0) {
            $dhp = 0;
        } elseif (abs($h2p - $h1p) <= 180) {
            $dhp = $h2p - $h1p;
        } elseif ($h2p - $h1p > 180) {
            $dhp = $h2p - $h1p - 360;
        } else {
            $dhp = $h2p - $h1p + 360;
        }
        $dHp = 2 * sqrt($c1p * $c2p) * sin(deg2rad($dhp / 2));

        $lpMean = ($l1 + $l2) / 2;
        $cpMean = ($c1p + $c2p) / 2;

        if ($c1p * $c2p == 0) {
            $hpMean = $h1p + $h2p;
        } elseif (abs($h1p - $h2p) <= 180) {
            $hpMean = ($h1p + $h2p) / 2;
        } elseif ($h1p + $h2p < 360) {
            $hpMean = ($h1p + $h2p + 360) / 2;
        } else {
            $hpMean = ($h1p + $h2p - 360) / 2;
        }

        $t = 1
            - 0.17 * cos(deg2rad($hpMean - 30))
            + 0.24 * cos(deg2rad(2 * $hpMean))
            + 0.32 * cos(deg2rad(3 * $hpMean + 6))
            - 0.20 * cos(deg2rad(4 * $hpMean - 63));

        $dTheta = 30 * exp(-((($hpMean - 275) / 25) ** 2));
        $rc = 2 * sqrt($cpMean ** 7 / ($cpMean ** 7 + 25 ** 7));
        $sl = 1 + (0.015 * ($lpMean - 50) ** 2) / sqrt(20 + ($lpMean - 50) ** 2);
        $sc = 1 + 0.045 * $cpMean;
        $sh = 1 + 0.015 * $cpMean * $t;
        $rt = -sin(deg2rad(2 * $dTheta)) * $rc;

        return sqrt(
            ($dLp / $sl) ** 2
            + ($dCp / $sc) ** 2
            + ($dHp / $sh) ** 2
            + $rt * ($dCp / $sc) * ($dHp / $sh)
        );
    }
}
