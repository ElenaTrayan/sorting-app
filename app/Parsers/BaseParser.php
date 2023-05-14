<?php

namespace App\Parsers;

use Illuminate\Support\Facades\Http;

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
