<?php

namespace App\Parsers;

/**
 * Характеристики и полное описание товара со страницы магазина — для пина "Товар" ("Заполнить по ссылке",
 * WishlistController::parseOpenGraphMeta → PostProductController::linkPreview).
 *
 * Характеристики — пары "подпись — значение": <dt>/<dd> (так у rozetka.com.ua: div.item > dt.label + dd.value)
 * и строки таблиц <th>/<td> или <td>/<td>. Берём только известные подписи (LABELS, укр./рус./англ.), остальное
 * игнорируем. Описание — блок "Опис" rozetka ([data-testid="description"]), иначе элемент itemprop="description".
 * Rozetka отдаёт всё это только в отрисованной странице (обычный запрос получает 403) — её снимает headless Chrome,
 * см. WishlistController::waitUntilProductDataAppears ($waitForSpecs).
 */
class ProductSpecsParser
{
    // поле post_products => подписи на сайтах (в нижнем регистре, без двоеточия, дефисы — обычные)
    private const LABELS = [
        'shelf_life' => ['термін зберігання', 'термін придатності', 'строк зберігання', 'строк придатності', 'срок хранения', 'срок годности', 'shelf life'],
        'weight' => ['вага', 'вага нетто', 'вага товару', 'маса', 'маса нетто', 'вес', 'вес нетто', 'вес товара', 'масса', 'масса нетто', 'weight', 'net weight'],
        'storage_conditions' => ['умови зберігання', 'условия хранения', 'storage conditions'],
        'brand_country' => ['країна реєстрації бренду', 'країна бренду', 'страна регистрации бренда', 'страна бренда', 'brand country'],
        'country' => ['країна-виробник', 'країна виробник', 'країна виробництва', 'країна походження', 'страна-производитель', 'страна производитель', 'страна производства', 'страна изготовления', 'страна происхождения', 'country of origin'],
    ];

    // однострочные поля и их длина (как колонки post_products); storage_conditions — текст с переносами
    private const SINGLE_LINE = [
        'shelf_life' => 100,
        'weight' => 50,
        'brand_country' => 100,
        'country' => 100,
    ];

    private const BLOCK_TAGS = ['p', 'div', 'li', 'ul', 'ol', 'tr', 'table', 'section', 'article', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dt', 'dd', 'dl', 'blockquote'];

    // страны — по-русски, как подсказки поля "Страна производства" (PostProduct::COUNTRIES): магазины Украины
    // пишут по-украински, а "Україна" и "Украина" в фильтрах были бы разными странами
    private const COUNTRIES_UK = [
        'україна' => 'Украина', 'польща' => 'Польша', 'німеччина' => 'Германия', 'франція' => 'Франция',
        'італія' => 'Италия', 'іспанія' => 'Испания', 'великобританія' => 'Великобритания', 'чехія' => 'Чехия',
        'туреччина' => 'Турция', 'сша' => 'США', 'канада' => 'Канада', 'китай' => 'Китай', 'японія' => 'Япония',
        'південна корея' => 'Южная Корея', 'корея' => 'Южная Корея', 'тайвань' => 'Тайвань', "в'єтнам" => 'Вьетнам',
        'вʼєтнам' => 'Вьетнам', 'в’єтнам' => 'Вьетнам', 'індія' => 'Индия', 'таїланд' => 'Таиланд', 'ізраїль' => 'Израиль',
        'швейцарія' => 'Швейцария', 'швеція' => 'Швеция', 'фінляндія' => 'Финляндия', 'нідерланди' => 'Нидерланды',
        'бельгія' => 'Бельгия', 'австрія' => 'Австрия', 'литва' => 'Литва', 'латвія' => 'Латвия', 'естонія' => 'Эстония',
        'угорщина' => 'Венгрия', 'румунія' => 'Румыния', 'болгарія' => 'Болгария', 'словаччина' => 'Словакия',
        'словенія' => 'Словения', 'хорватія' => 'Хорватия', 'сербія' => 'Сербия', 'молдова' => 'Молдова',
        'данія' => 'Дания', 'норвегія' => 'Норвегия', 'португалія' => 'Португалия', 'греція' => 'Греция',
        'ірландія' => 'Ирландия', 'сінгапур' => 'Сингапур', 'малайзія' => 'Малайзия', 'індонезія' => 'Индонезия',
        'бразилія' => 'Бразилия', 'мексика' => 'Мексика', 'австралія' => 'Австралия', 'грузія' => 'Грузия',
        'казахстан' => 'Казахстан', 'білорусь' => 'Беларусь', 'оае' => 'ОАЭ', 'єгипет' => 'Египет',
    ];

    /**
     * @return array{shelf_life: ?string, weight: ?string, storage_conditions: ?string, brand_country: ?string,
     *               country: ?string, full_description: ?string}
     */
    public function parse(\DOMXPath $xpath): array
    {
        $result = array_fill_keys(array_keys(self::LABELS), null);

        foreach ($this->labelValuePairs($xpath) as [$labelNode, $valueNode]) {
            $field = $this->fieldForLabel($labelNode->textContent);
            if ($field === null || $result[$field] !== null) {
                continue;
            }
            $value = $this->fieldValue($field, $valueNode);
            if ($value !== null) {
                $result[$field] = $value;
            }
        }

        $result['full_description'] = $this->description($xpath);

        return $result;
    }

    // "Україна" -> "Украина"; несколько через запятую — каждую; незнакомую оставляем как есть
    public static function normalizeCountry(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $parts = array_map(function ($part) {
            $part = trim($part);
            return self::COUNTRIES_UK[mb_strtolower($part)] ?? $part;
        }, preg_split('~\s*[,;]\s*~u', trim($value)));

        return implode(', ', array_filter($parts, 'strlen'));
    }

    /**
     * Пары [подпись, значение]: dt + следующий dd, строки таблиц из двух ячеек
     *
     * @return array<int, array{0: \DOMNode, 1: \DOMNode}>
     */
    private function labelValuePairs(\DOMXPath $xpath): array
    {
        $pairs = [];

        foreach ($xpath->query('//dt') as $dt) {
            $dd = $xpath->query('following-sibling::*[1][self::dd]', $dt)->item(0);
            if ($dd) {
                $pairs[] = [$dt, $dd];
            }
        }

        foreach ($xpath->query('//tr') as $tr) {
            $cells = $xpath->query('./th|./td', $tr);
            if ($cells->length === 2) {
                $pairs[] = [$cells->item(0), $cells->item(1)];
            }
        }

        return $pairs;
    }

    private function fieldForLabel(string $label): ?string
    {
        $label = mb_strtolower($this->cleanSpaces($label));
        $label = str_replace(["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}"], '-', $label);
        $label = trim(rtrim($label, ':'));
        if ($label === '' || mb_strlen($label) > 60) {
            return null;
        }

        foreach (self::LABELS as $field => $labels) {
            if (in_array($label, $labels, true)) {
                return $field;
            }
        }

        return null;
    }

    private function fieldValue(string $field, \DOMNode $node): ?string
    {
        $text = $this->nodeText($node, "\n");
        if ($text === '') {
            return null;
        }

        if (!isset(self::SINGLE_LINE[$field])) {
            return mb_substr($text, 0, 5000);
        }

        // несколько значений списком (rozetka: ul.sub-list > li) — через запятую
        $text = implode(', ', array_filter(array_map('trim', explode("\n", $text)), 'strlen'));
        if (in_array($field, ['country', 'brand_country'], true)) {
            $text = self::normalizeCountry($text);
        }

        return $text !== null && $text !== '' ? mb_substr($text, 0, self::SINGLE_LINE[$field]) : null;
    }

    // "Опис" rozetka, иначе первый непустой элемент itemprop="description" (не <meta>)
    private function description(\DOMXPath $xpath): ?string
    {
        $queries = [
            '//*[@data-testid="description"]//*[contains(concat(" ", normalize-space(@class), " "), " product-about__description-content ")]',
            '//*[@data-testid="description"]//rz-text-content',
            '//*[@itemprop="description"][not(self::meta)]',
        ];

        foreach ($queries as $query) {
            foreach ($xpath->query($query) as $node) {
                $text = $this->nodeText($node, "\n\n");
                if (mb_strlen($text) >= 20) {
                    return mb_substr($text, 0, 20000);
                }
            }
        }

        return null;
    }

    /**
     * Текст узла с переносами строк на месте блочных тегов и <br>: абзацы разделяются $blockSeparator
     * ("\n\n" для описания — admin.js addParsedDescriptionToEditor делает из них <p>), пустые строки схлопываются
     */
    private function nodeText(\DOMNode $node, string $blockSeparator): string
    {
        $raw = $this->collectText($node);

        $lines = array_map(function ($line) {
            return trim($this->cleanSpaces($line));
        }, explode("\n", $raw));

        $blocks = [];
        $current = [];
        foreach ($lines as $line) {
            if ($line === '') {
                if ($current) {
                    $blocks[] = implode("\n", $current);
                    $current = [];
                }
                continue;
            }
            $current[] = $line;
        }
        if ($current) {
            $blocks[] = implode("\n", $current);
        }

        return trim(implode($blockSeparator, $blocks));
    }

    // текст с "\n\n" вокруг блочных элементов и "\n" на месте <br>; скрипты и стили пропускаем
    private function collectText(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return str_replace(["\r", "\n"], ' ', $node->textContent);
        }
        if (!$node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'noscript', 'svg', 'button'], true)) {
            return '';
        }
        if ($tag === 'br') {
            return "\n";
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->collectText($child);
        }

        return in_array($tag, self::BLOCK_TAGS, true) ? "\n\n" . $text . "\n\n" : $text;
    }

    private function cleanSpaces(string $text): string
    {
        $text = str_replace(["\u{00A0}", "\u{200B}", "\t"], ' ', $text);

        return preg_replace('~ {2,}~u', ' ', $text);
    }
}
