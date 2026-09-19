<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Разовый (пере-запускаемый) импорт локальной базы городов/стран для автоподсказок
 * в поле "Место жительства" (см. ContactController::searchResidence) — из дампов
 * GeoNames (бесплатные, лицензия CC BY 4.0, https://www.geonames.org/):
 *   - cities5000.txt   — города мира от ~5000 жителей: https://download.geonames.org/export/dump/cities5000.zip
 *   - countryInfo.txt  — страны:                        https://download.geonames.org/export/dump/countryInfo.txt
 *   - alternateNamesV2.txt (из alternateNamesV2.zip) — переводы названий, отсюда
 *     достаём только строки с isolanguage=ru/en и только для geonameid из городов/
 *     стран выше (иначе файл ~780 МБ вместо нужных нам сотен КБ). Предварительная
 *     фильтрация вынесена в shell (awk) — см. историю разработки; путь к уже
 *     отфильтрованному файлу передаётся через --alt.
 */
class ImportResidenceGeoData extends Command
{
    protected $signature = 'residence:import
        {--cities= : Путь к cities5000.txt}
        {--countries= : Путь к countryInfo.txt}
        {--alt= : Путь к отфильтрованному (isolanguage=ru/en) alternateNamesV2.txt}';

    protected $description = 'Импортирует локальную базу городов/стран (GeoNames) для автоподсказок поля "Место жительства"';

    public function handle()
    {
        $citiesPath = $this->option('cities');
        $countriesPath = $this->option('countries');
        $altPath = $this->option('alt');

        foreach (['cities' => $citiesPath, 'countries' => $countriesPath, 'alt' => $altPath] as $label => $path) {
            if (!$path || !is_readable($path)) {
                $this->error("Файл для --{$label} не найден или не читается: " . ($path ?: '(не задан)'));

                return 1;
            }
        }

        $this->info('Читаю альтернативные названия (ru/en)...');
        [$altRu, $altEn] = $this->readAlternateNames($altPath);
        $this->info('Найдено: ' . count($altRu) . ' ru-названий, ' . count($altEn) . ' en-названий.');

        $this->info('Импортирую страны...');
        $countriesInserted = $this->importCountries($countriesPath, $altRu, $altEn);
        $this->info("Стран импортировано: {$countriesInserted}");

        $this->info('Импортирую города...');
        $citiesInserted = $this->importCities($citiesPath, $altRu, $altEn);
        $this->info("Городов импортировано: {$citiesInserted}");

        return 0;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>} [geoname_id => ru-имя], [geoname_id => en-имя]
     *         При нескольких вариантах на один geonameid побеждает isPreferredName=1,
     *         иначе — первый попавшийся.
     */
    private function readAlternateNames(string $path): array
    {
        $ru = [];
        $en = [];
        $ruPreferred = [];
        $enPreferred = [];

        $handle = fopen($path, 'r');
        while (($line = fgets($handle)) !== false) {
            $cols = explode("\t", rtrim($line, "\n\r"));
            // alternateNameId, geonameid, isolanguage, alternate name, isPreferredName, ...
            if (count($cols) < 4) {
                continue;
            }
            $geonameId = (int) $cols[1];
            $lang = $cols[2];
            $name = trim($cols[3]);
            $isPreferred = ($cols[4] ?? '') === '1';

            if ($geonameId <= 0 || $name === '') {
                continue;
            }

            if ($lang === 'ru' && (!isset($ru[$geonameId]) || ($isPreferred && !($ruPreferred[$geonameId] ?? false)))) {
                $ru[$geonameId] = $name;
                $ruPreferred[$geonameId] = $isPreferred;
            } elseif ($lang === 'en' && (!isset($en[$geonameId]) || ($isPreferred && !($enPreferred[$geonameId] ?? false)))) {
                $en[$geonameId] = $name;
                $enPreferred[$geonameId] = $isPreferred;
            }
        }
        fclose($handle);

        return [$ru, $en];
    }

    private function importCountries(string $path, array $altRu, array $altEn): int
    {
        $rows = [];
        $handle = fopen($path, 'r');
        while (($line = fgets($handle)) !== false) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $cols = explode("\t", rtrim($line, "\n\r"));
            // ISO, ISO3, ISO-Numeric, fips, Country, Capital, Area, Population, Continent,
            // tld, CurrencyCode, CurrencyName, Phone, PostalCodeFormat, PostalCodeRegex,
            // Languages, geonameid, neighbours, EquivalentFipsCode
            $iso = $cols[0] ?? null;
            $name = $cols[4] ?? null;
            $geonameId = (int) ($cols[16] ?? 0);

            if (!$iso || !$name || $geonameId <= 0) {
                continue;
            }

            $rows[] = [
                'geoname_id' => $geonameId,
                'iso_code' => $iso,
                'name' => $name,
                'name_en' => $altEn[$geonameId] ?? $name,
                'name_ru' => $altRu[$geonameId] ?? null,
            ];
        }
        fclose($handle);

        DB::table('residence_countries')->truncate();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('residence_countries')->insert($chunk);
        }

        return count($rows);
    }

    private function importCities(string $path, array $altRu, array $altEn): int
    {
        $handle = fopen($path, 'r');
        $buffer = [];
        $total = 0;

        DB::table('residence_cities')->truncate();

        while (($line = fgets($handle)) !== false) {
            $cols = explode("\t", rtrim($line, "\n\r"));
            // geonameid, name, asciiname, alternatenames, latitude, longitude, feature class,
            // feature code, country code, cc2, admin1, admin2, admin3, admin4, population,
            // elevation, dem, timezone, modification date
            if (count($cols) < 15) {
                continue;
            }
            $geonameId = (int) $cols[0];
            $name = $cols[1];
            $asciiName = $cols[2] ?: null;
            $lat = $cols[4] !== '' ? (float) $cols[4] : null;
            $lng = $cols[5] !== '' ? (float) $cols[5] : null;
            $countryCode = $cols[8] ?: null;
            $population = (int) $cols[14];

            if ($geonameId <= 0 || $name === '') {
                continue;
            }

            $buffer[] = [
                'geoname_id' => $geonameId,
                'name' => $name,
                'name_ascii' => $asciiName,
                'name_en' => $altEn[$geonameId] ?? $asciiName ?? $name,
                'name_ru' => $altRu[$geonameId] ?? null,
                'country_code' => $countryCode,
                'population' => $population,
                'lat' => $lat,
                'lng' => $lng,
            ];
            $total++;

            if (count($buffer) >= 1000) {
                DB::table('residence_cities')->insert($buffer);
                $buffer = [];
            }
        }
        fclose($handle);

        if ($buffer) {
            DB::table('residence_cities')->insert($buffer);
        }

        return $total;
    }
}
