<?php

namespace App\Parsers;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

abstract class BaseParser
{
    /**
     * URL адрес страницы для парсинга
     *
     * @var string
     */
    protected $url;

    protected $dom;
    protected $pElements;
    protected $divElements;

    /**
     * Получает HTML-код страницы для парсинга
     *
     * @return void
     */
    protected function getPageContent(): void
    {
        //dd($this->url);
        // многие сайты отдают заглушку/403 запросам без браузерного User-Agent
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,uk;q=0.8,en;q=0.7',
        ])->timeout(20)->get($this->url);
        $html = $this->toHtmlEntities($response->body(), $response->header('Content-Type'));
        //dd($html);

//        $client = new Client();
//
//        $response = $client->request('GET', $this->url, [
//            'headers' => [
//                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36',
//            ],
//        ]);
//
//        $html = $response->getBody()->getContents();
//        dd($html);

//        $ch = curl_init('https://www.instagram.com/аккаунт/');
//        curl_setopt($ch, CURLOPT_PROXY, 'xxx.xxx.xxx.xxx:8080');
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_HEADER, false); // true - чтобы вывести заголовки
//        $html = curl_exec($ch);
//        curl_close($ch);

//        dd($html);

        $this->dom = new \DOMDocument(); //"ext-dom": "*", в composer.json
        libxml_use_internal_errors(true); // добавим вот эту строку
        $this->dom->loadHTML($html);
        //dd($this->dom);

        $this->pElements = $this->dom->getElementsByTagName('p');
        //dd($this->pElements);
        $this->divElements = $this->dom->getElementsByTagName('div');
    }

    /**
     * DOMDocument::loadHTML без явной кодировки читает страницу как ISO-8859-1, и кириллица превращается
     * в кракозябры. Поэтому переводим страницу в UTF-8, а все не-ASCII символы — в числовые сущности (&#1046;):
     * так результат не зависит от того, какую кодировку libxml решит использовать.
     *
     * @param string $html
     * @param string $contentType - заголовок Content-Type ответа
     * @return string
     */
    protected function toHtmlEntities(string $html, string $contentType = ''): string
    {
        $charset = '';
        if (preg_match('~charset=["\']?([\w-]+)~i', $contentType, $matches)
            || preg_match('~<meta[^>]+charset=["\']?([\w-]+)~i', $html, $matches)) {
            $charset = strtoupper($matches[1]);
        }

        if ($charset === '' || !in_array($charset, array_map('strtoupper', mb_list_encodings()), true)) {
            $charset = mb_check_encoding($html, 'UTF-8') ? 'UTF-8' : 'Windows-1251';
        }

        if ($charset !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $charset);
        }

        return mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
    }

    /**
     * Парсит страницу и возвращает нужную информацию
     *
     * @return mixed
     */
    abstract public function parse();

    /**
     * Название фильма
     *
     * @return string
     */
    protected function getPostTitle(): string
    {
        $titleElement = $this->dom->getElementsByTagName('h1')->item(0);
        $title = $titleElement ? $titleElement->textContent : '';

        return trim($title);
    }

}
