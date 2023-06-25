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
        $response = Http::get($this->url);
        $html = $response->body();
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
        $this->divElements = $this->dom->getElementsByTagName('div');
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
