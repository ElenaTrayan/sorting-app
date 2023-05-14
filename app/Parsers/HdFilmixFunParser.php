<?php

namespace App\Parsers;

class HdFilmixFunParser extends BaseParser
{
    /*
        $parser = new HdFilmixFunParser(link);
        $result = $parser->parse();

        echo $result['title']; // Выведет "Отпетые мошенницы"
        echo $result['rating']; // Выведет "7.9"
        echo $result['description']; // Выведет описание фильма
     */

    protected $ulElements;

    /**
     * HdFilmixFunParser constructor.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->url = $link;
        $this->getPageContent();
        $this->ulElements = $this->dom->getElementsByTagName('ul');
    }

    /**
     * Парсит страницу фильма и возвращает нужную информацию
     *
     * @return array
     */
    public function parse(): array
    {
        $result = [];

        // Парсим название фильма
        $result['title'] = $this->getPostTitle();

        foreach ($this->divElements as $divElement) {
            if ($divElement && $divElement->hasAttribute('class')) {
                // Парсим описание фильма
                if (strripos($divElement->getAttribute('class'), 'movie-desc') !== false) {
                    $result['film_description'] = trim(str_replace('@Filmix.fun', '', $divElement->nodeValue));
                }
            }
        }

        foreach ($this->ulElements as $ulElement) {
            if ($ulElement && $ulElement->hasAttribute('class') && strripos($ulElement->getAttribute('class'), 'movie-lines') !== false) {
                $liElements = $ulElement->getElementsByTagName('li');
                foreach ($liElements as $key => $liElement) {
                    $divElements = $liElement->getElementsByTagName('div');
                    foreach ($divElements as $divElement) {
                        if ($divElement && $divElement->hasAttribute('class') && strripos($divElement->getAttribute('class'), 'ml-desc') !== false) {
                            if ($key === 0) {
                                // Парсим режиссеров фильма
                                $result['film_directors'] = $divElement->nodeValue;
                            } elseif ($key === 1) {
                                // Парсим актеров фильма
                                $result['film_actors'] = $divElement->nodeValue;
                            } elseif ($key === 2) {
                                // Парсим жанры фильма
                                $result['film_genres'] = $divElement->nodeValue;
                            } elseif ($key === 4) {
                                // Парсим страны фильма
                                $result['film_countries'] = $divElement->nodeValue;
                            } elseif ($key === 5) {
                                // Парсим год фильма
                                $result['film_year'] = $divElement->nodeValue;
                            } elseif ($key === 6) {
                                // Парсим длительность фильма
                                $result['film_duration'] = $divElement->nodeValue;
                            }
                            //return trim($pElement->nodeValue);
                        }
                    }
                }
            }
        }

        // alias
        $result['alias'] = str_slug($result['title']);

        //dd($result);

        return $result;
    }

}
