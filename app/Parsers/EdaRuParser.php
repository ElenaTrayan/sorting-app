<?php

namespace App\Parsers;

use App\Http\Controllers\Admin\Packages\UploadImageController;
use Illuminate\Support\Facades\Http;

class EdaRuParser extends BaseParser
{
    protected $spanElements;

    /**
     * SweetTvParser constructor.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->url = $link;
        $this->getPageContent();
        $this->spanElements = $this->dom->getElementsByTagName('span');
//        var_dump($this->spanElements);
//        exit();
    }

    /**
     * Парсит страницу фильма и возвращает нужную информацию
     *
     * @return array
     */
    public function parse(): array
    {
        $result = [];

        $titleElement = $this->dom->getElementsByTagName('h1')->item(0);
        $title = $titleElement ? $titleElement->textContent : '';

        // Парсим название фильма
        $result['title'] = $title;
        // Парсим жанры фильма
//        $result['film_genres'] = $this->getFilmGenres();
//        // Парсим год фильма
//        $result['film_year'] = $this->getFilmYear();
//
//        $result['film_countries'] = [];
//        $result['film_directors'] = [];
//        $result['film_actors'] = [];
//
//        foreach ($this->pElements as $pElement) {
//            if ($pElement->hasAttribute('itemprop')) {
//                if ($pElement->getAttribute('itemprop') === 'countryOfOrigin') {
//                    // Парсим страны фильма
//                    $aElements = $pElement->getElementsByTagName('a');
//                    foreach ($aElements as $aElement) {
//                        $result['film_countries'][] = trim($aElement->nodeValue);
//                    }
//
//                } elseif ($pElement->getAttribute('itemprop') === 'director') {
//                    // Парсим режиссеров фильма
//                    $aElements = $pElement->getElementsByTagName('a');
//                    foreach ($aElements as $aElement) {
//                        $result['film_directors'][] = trim($aElement->nodeValue);
//                    }
//
//                } elseif ($pElement->getAttribute('itemprop') === 'actor') {
//                    // Парсим актеров фильма
//                    $aElements = $pElement->getElementsByTagName('a');
//                    foreach ($aElements as $aElement) {
//                        $result['film_actors'][] = trim($aElement->nodeValue);
//                    }
//
//                } elseif ($pElement->getAttribute('itemprop') === 'description') {
//                    // Парсим описание фильма
//                    $result['film_description'] = trim($pElement->nodeValue);
//                }
//
//            }
//        }
//
//        foreach ($this->spanElements as $spanElement) {
//            if ($spanElement->hasAttribute('itemprop') && $spanElement->getAttribute('itemprop') === 'ratingValue') {
//                // Парсим рейтинг фильма
//                $result['imdb_rating'] = trim($spanElement->nodeValue);
//            } elseif ($spanElement->hasAttribute('class') && $spanElement->getAttribute('class') === 'film-left__time') {
//                // Парсим длительность фильма
//                $result['film_duration'] = trim($spanElement->nodeValue);
//            }
//        }
//
//        // alias
//        $result['alias'] = str_slug($result['title']);
//
//        $imageUrl = $this->getImageUrl();
//        $imageName = $this->getImageName($imageUrl);
//
//        //$imgUrl = 'https://static.sweet.tv/images/cache/movie_banners/BCEVSEQCOJ2SAAQ=/11401-kniga-dzhungley_1280x720.jpg';
//        $response = Http::get($imageUrl);
//        $imagePath = '../public/storage/temp_directory/' . $imageName['name'];
//        $saveToTempDirectory = file_put_contents($imagePath, $response);
//        //TODO проверка $saveToTempDirectory
//
//        $imageSmallObject = new UploadImageController();
//        $image = $imageSmallObject->saveParseImageToTempDirectory(
//            $imagePath,
//            $imageName['name'],
//            $imageName['extension'],
//        );
//
//        if (!empty($image)) {
//            $result['image'] = $image;
//        }

        //dd($result);

        return $result;
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
     * @return string
     */
    private function getImageUrl(): string
    {
        $elementPicture = $this->dom->getElementsByTagName('picture')->item(0);
        $sourceElements = $elementPicture->getElementsByTagName('source');
        foreach ($sourceElements as $sourceElement) {
            if ($sourceElement->hasAttribute('media') && $sourceElement->getAttribute('media') === '(min-width: 600px)') {
                return $sourceElement->getAttribute('srcset');
            }
        }

        return '';
    }

    /**
     * @param string $imageUrl - example: 'https://static.sweet.tv/images/cache/movie_banners/BCEVSEQCOJ2SAAQ=/11401-kniga-dzhungley_1280x720.jpg'
     * @return array
     */
    private function getImageName(string $imageUrl): array
    {
        $parseName = explode('/', $imageUrl);
        $imageName = end($parseName);
        $parseImageExtension = explode('.', $imageUrl);
        $imageExtension = end($parseImageExtension);

        return [
            'name' => $imageName,
            'extension' => $imageExtension,
        ];
    }

//    /**
//     * Страны фильма
//     *
//     * @return array
//     */
//    private function getFilmCountries(): array
//    {
//        $filmCountries = [];
//
//        $pElements = $this->dom->getElementsByTagName('p');
//        foreach ($pElements as $pElement) {
//            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'countryOfOrigin') {
//
//                $aElements = $pElement->getElementsByTagName('a');
//                foreach ($aElements as $aElement) {
//                    $filmCountries[] = trim($aElement->nodeValue);
//                }
//
//            }
//        }
//
//        return $filmCountries;
//    }

//    /**
//     * Актеры фильма
//     *
//     * @return array
//     */
//    private function getFilmActors(): array
//    {
//        $filmActors = [];
//
//        $pElements = $this->dom->getElementsByTagName('p');
//        foreach ($pElements as $pElement) {
//            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'actor') {
//
//                $aElements = $pElement->getElementsByTagName('a');
//                foreach ($aElements as $aElement) {
//                    $filmActors[] = trim($aElement->nodeValue);
//                }
//
//            }
//        }
//
//        return $filmActors;
//    }

//    /**
//     * Режиссеры фильма
//     *
//     * @return array
//     */
//    private function getFilmDirectors(): array
//    {
//        $filmDirectors = [];
//
//        $pElements = $this->dom->getElementsByTagName('p');
//        foreach ($pElements as $pElement) {
//            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'director') {
//
//                $aElements = $pElement->getElementsByTagName('a');
//                foreach ($aElements as $aElement) {
//                    $filmDirectors[] = trim($aElement->nodeValue);
//                }
//
//            }
//        }
//
//        return $filmDirectors;
//    }

//    private function getFilmDuration()
//    {
//        $filmDuration = '';
//
////        $finder = new \DomXPath($this->dom);
////        $classname="film-left__time";
////        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
//
//        $pElements = $this->dom->getElementsByTagName('span');
//        foreach ($pElements as $pElement) {
//            if ($pElement->hasAttribute('class') && $pElement->getAttribute('class') === 'film-left__time') {
//
//                $filmDuration = trim($pElement->nodeValue);
//
//            }
//        }
//
//        return $filmDuration;
//    }

//    /**
//     * @return string
//     */
//    private function getFilmDescription(): string
//    {
//        $filmDescription = '';
//
//        $pElements = $this->dom->getElementsByTagName('p');
//        foreach ($pElements as $pElement) {
//            if ($pElement->hasAttribute('itemprop') && $pElement->getAttribute('itemprop') === 'description') {
//
//                $filmDescription = trim($pElement->nodeValue);
//
//            }
//        }
//
//        return $filmDescription;
//    }

}
