<?php

namespace App\Components;

use Dompdf\Dompdf;
use ZipArchive;

/**
 * Экспорт группы текстов в файл (кнопки "DOCX" / "PDF" на странице группы, PostController::exportGroup):
 * название группы и тексты постов по порядку, каждый пост — один абзац.
 *
 * Текст поста — HTML из редактора. В файл идёт только то, что есть в обоих форматах: сам текст, жирный,
 * курсив, подчёркивание; абзацы и пункты списка внутри одного поста — переносы строки в его абзаце.
 * DOCX собираем сами (это zip с XML) — отдельная библиотека ради одного простого документа не нужна;
 * PDF — через Dompdf, шрифт DejaVu Sans (идёт с Dompdf, в нём есть кириллица).
 */
class TextGroupExport
{
    /**
     * Абзацы документа — по одному на пост с непустым текстом
     *
     * @param iterable $contents тексты постов (HTML) по порядку
     * @return array [[['text' => string, 'b' => bool, 'i' => bool, 'u' => bool] | ['break' => true], ...], ...]
     */
    public static function paragraphs(iterable $contents): array
    {
        $paragraphs = [];
        foreach ($contents as $content) {
            $runs = self::runs((string)$content);
            if ($runs) {
                $paragraphs[] = $runs;
            }
        }

        return $paragraphs;
    }

    /**
     * Разбирает HTML поста на куски текста с оформлением и переносы строк
     *
     * @param string $html
     * @return array
     */
    private static function runs(string $html): array
    {
        // пункт списка — с маркером; конец блока (абзац, пункт, заголовок...) и <br> — перенос строки
        $html = preg_replace('~<li\b[^>]*>~i', '• ', $html);
        $html = preg_replace('~<br\b[^>]*>|</(p|div|li|h[1-6]|blockquote|tr|pre)\s*>~i', "\n", $html);
        $html = strip_tags($html, '<b><strong><i><em><u>');

        $state = ['b' => 0, 'i' => 0, 'u' => 0];
        $kinds = ['b' => 'b', 'strong' => 'b', 'i' => 'i', 'em' => 'i', 'u' => 'u'];
        $lines = [[]];

        foreach (preg_split('~(</?(?:strong|em|b|i|u)\b[^>]*>|\n)~i', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $token) {
            if ($token === "\n") {
                $lines[] = [];
            } elseif (preg_match('~^<(/?)(strong|em|b|i|u)\b~i', $token, $tag)) {
                $kind = $kinds[strtolower($tag[2])];
                $state[$kind] = max(0, $state[$kind] + ($tag[1] === '/' ? -1 : 1));
            } else {
                $text = html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // неразрывные пробелы и переводы строк из разметки — обычные пробелы; символы, недопустимые в XML, — долой
                $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);
                $text = preg_replace('/[^\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
                if ($text !== '') {
                    $lines[count($lines) - 1][] = ['text' => $text, 'b' => $state['b'] > 0, 'i' => $state['i'] > 0, 'u' => $state['u'] > 0];
                }
            }
        }

        // пустые строки (между абзацами поста, после последнего) не нужны: пост — один абзац
        $lines = array_values(array_filter($lines, function (array $line) {
            return trim(implode('', array_column($line, 'text'))) !== '';
        }));

        $runs = [];
        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $runs[] = ['break' => true];
            }
            $line[0]['text'] = ltrim($line[0]['text']);
            $last = count($line) - 1;
            $line[$last]['text'] = rtrim($line[$last]['text']);
            foreach ($line as $run) {
                if ($run['text'] !== '') {
                    $runs[] = $run;
                }
            }
        }

        return $runs;
    }

    /**
     * Документ Word
     *
     * @param string $title название группы, '' — без заголовка
     * @param array $paragraphs см. paragraphs()
     * @return string содержимое .docx
     */
    public static function docx(string $title, array $paragraphs): string
    {
        $font = '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>';
        $text = function (string $text): string {
            return '<w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t>';
        };

        $body = '';
        if ($title !== '') {
            $body .= '<w:p><w:pPr><w:spacing w:after="280"/></w:pPr><w:r><w:rPr>' . $font . '<w:b/><w:sz w:val="36"/></w:rPr>'
                . $text($title) . '</w:r></w:p>';
        }

        foreach ($paragraphs as $runs) {
            $body .= '<w:p><w:pPr><w:spacing w:after="200" w:line="320" w:lineRule="auto"/></w:pPr>';
            foreach ($runs as $run) {
                if (!empty($run['break'])) {
                    $body .= '<w:r><w:br/></w:r>';
                    continue;
                }
                $body .= '<w:r><w:rPr>' . $font
                    . ($run['b'] ? '<w:b/>' : '')
                    . ($run['i'] ? '<w:i/>' : '')
                    . '<w:sz w:val="24"/>'
                    . ($run['u'] ? '<w:u w:val="single"/>' : '')
                    . '</w:rPr>' . $text($run['text']) . '</w:r>';
            }
            $body .= '</w:p>';
        }

        // A4, поля 2 см
        $body .= '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
                . '</Relationships>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>',
        ];

        // ZipArchive пишет только в файл
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Не удалось создать файл DOCX');
        }
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $docx = file_get_contents($path);
        unlink($path);

        return $docx;
    }

    /**
     * Документ PDF
     *
     * @param string $title название группы, '' — без заголовка
     * @param array $paragraphs см. paragraphs()
     * @return string содержимое .pdf
     */
    public static function pdf(string $title, array $paragraphs): string
    {
        $escape = function (string $text): string {
            return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        $html = '<html><head><meta charset="UTF-8"><style>'
            . '@page { margin: 2cm; }'
            . 'body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.45; color: #000; }'
            . 'h1 { margin: 0 0 14pt; font-size: 18pt; line-height: 1.25; }'
            . 'p { margin: 0 0 10pt; }'
            . '</style></head><body>';

        if ($title !== '') {
            $html .= '<h1>' . $escape($title) . '</h1>';
        }

        foreach ($paragraphs as $runs) {
            $html .= '<p>';
            foreach ($runs as $run) {
                if (!empty($run['break'])) {
                    $html .= '<br>';
                    continue;
                }
                $html .= ($run['b'] ? '<b>' : '') . ($run['i'] ? '<i>' : '') . ($run['u'] ? '<u>' : '')
                    . $escape($run['text'])
                    . ($run['u'] ? '</u>' : '') . ($run['i'] ? '</i>' : '') . ($run['b'] ? '</b>' : '');
            }
            $html .= '</p>';
        }

        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }
}
