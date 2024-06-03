<?php

namespace App\Parsers;

use Illuminate\Support\Facades\Http;

class UaKinoClubParser extends BaseParser
{
    /*
        $parser = new HdRezkaParser(link);
        $result = $parser->parse();

        echo $result['title']; // Выведет "Отпетые мошенницы"
        echo $result['rating']; // Выведет "7.9"
        echo $result['description']; // Выведет описание фильма
     */

    /**
     * SweetTvParser constructor.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->url = $link;
        $this->getPageContent();
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
        // Парсим жанры фильма
//        $result['film_genres'] = $this->getFilmGenres();
//        // Парсим рейтинг фильма
//        $result['imdb_rating'] = $this->getPostIMDbRating();
//        // Парсим год фильма
//        $result['film_year'] = $this->getFilmYear();
//        // Парсим страны фильма
//        $result['film_countries'] = $this->getFilmCountries();
//        // Парсим режиссеров фильма
//        $result['film_directors'] = $this->getFilmDirectors();
//        // Парсим актеров фильма
//        $result['film_actors'] = $this->getFilmActors();
//        // Парсим длительность фильма
//        $result['film_duration'] = $this->getFilmDuration();
//        // Парсим описание фильма
//        $result['film_description'] = $this->getFilmDescription();
        // alias
        $result['alias'] = str_slug($result['title']);

        dd($result);

        return $result;
    }

    /**
     * Название фильма
     *
     * @return string
     */
    protected function getPostTitle(): string
    {
        //dd($this->dom);
        $titleElement = $this->dom->getElementsByTagName('h1')->item(0);
        //dd($titleElement);
        $title = $titleElement ? $titleElement->textContent : '';

        return trim($title);
    }

    /**
     * Рейтинг фильма
     *
     * @return string
     */
    private function getPostIMDbRating(): string
    {
        $ratingElements = $this->dom->getElementsByTagName('span');
        foreach ($ratingElements as $ratingElement) {
            if ($ratingElement->hasAttribute('itemprop') && $ratingElement->getAttribute('itemprop') === 'ratingValue') {
                return trim($ratingElement->nodeValue);
            }
        }

        return '';
    }

    /**
     * Год фильма
     *
     * @return string
     */
    private function getFilmYear(): string
    {
        $divElements = $this->dom->getElementsByTagName('div');
        foreach ($divElements as $divElement) {
            if ($divElement && $divElement->hasAttribute('class') && $divElement->getAttribute('class') === 'd-flex align-items-start film__years') {
                $pElement = $divElement->getElementsByTagName('p')->item(1);
                if ($pElement && $pElement->getAttribute('class') === 'film-left__details') {
                    return trim($pElement->nodeValue);
                }
            }
        }

        return '';
    }

    /**
     * Жанры фильма
     *
     * @return array
     */
    private function getFilmGenres(): array
    {
        $filmGenres = [];

        $elements = $this->dom->getElementsByTagName('a');
        foreach ($elements as $element) {
            if ($element->hasAttribute('itemprop') && $element->getAttribute('itemprop') === 'genre') {
                $filmGenres[] = trim(str_replace(',', '', $element->nodeValue));
            }
        }

        return $filmGenres;
    }

    /**
     * Страны фильма
     *
     * @return array
     */
    private function getFilmCountries(): array
    {
        $filmCountries = [];

        $pElements = $this->dom->getElementsByTagName('p');
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'countryOfOrigin') {

                $aElements = $pElement->getElementsByTagName('a');
                foreach ($aElements as $aElement) {
                    $filmCountries[] = trim($aElement->nodeValue);
                }

            }
        }

        return $filmCountries;
    }

    /**
     * Актеры фильма
     *
     * @return array
     */
    private function getFilmActors(): array
    {
        $filmActors = [];

        $pElements = $this->dom->getElementsByTagName('p');
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'actor') {

                $aElements = $pElement->getElementsByTagName('a');
                foreach ($aElements as $aElement) {
                    $filmActors[] = trim($aElement->nodeValue);
                }

            }
        }

        return $filmActors;
    }

    /**
     * Режиссеры фильма
     *
     * @return array
     */
    private function getFilmDirectors(): array
    {
        $filmDirectors = [];

        $pElements = $this->dom->getElementsByTagName('p');
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'director') {

                $aElements = $pElement->getElementsByTagName('a');
                foreach ($aElements as $aElement) {
                    $filmDirectors[] = trim($aElement->nodeValue);
                }

            }
        }

        return $filmDirectors;
    }

    private function getFilmDuration()
    {
        $filmDuration = '';

//        $finder = new \DomXPath($this->dom);
//        $classname="film-left__time";
//        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

        $pElements = $this->dom->getElementsByTagName('span');
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('class') && $pElement->getAttribute('class') === 'film-left__time') {

                $filmDuration = trim($pElement->nodeValue);

            }
        }

        return $filmDuration;
    }

    /**
     * @return string
     */
    private function getFilmDescription(): string
    {
        $filmDescription = '';

        $pElements = $this->dom->getElementsByTagName('p');
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'description') {

                $filmDescription = trim($pElement->nodeValue);

            }
        }

        return $filmDescription;
    }

}
