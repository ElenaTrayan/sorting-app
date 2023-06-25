<?php

namespace App\Parsers;

use App\Http\Controllers\Admin\Packages\UploadImageController;
use Illuminate\Support\Facades\Http;

class PinterestParser extends BaseParser
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
    }

    /**
     * Парсит страницу фильма и возвращает нужную информацию
     *
     * @return array
     */
    public function parse(): array
    {
        $result = [];

        $imageUrl = $this->getImageUrl();
        $imageName = $this->getImageName($imageUrl);

        //$imgUrl = 'https://i.pinimg.com/originals/0f/74/81/0f748195794757ba9740f92b94138fb1.jpg';
        $response = Http::get($imageUrl);
        $imagePath = '../public/storage/temp_directory/' . $imageName['name'] . '.' . $imageName['extension'];
        $saveToTempDirectory = file_put_contents($imagePath, $response);
        //TODO проверка $saveToTempDirectory

        $this->imageName = $imageName['name'];

        $imageSmallObject = new UploadImageController();
        $image = $imageSmallObject->saveParseImageToTempDirectory(
            $imagePath,
            $imageName['name'],
            $imageName['extension'],
        );

        if (!empty($image)) {
            $result['image'] = $image;
        }

        // Парсим описание фильма
        $result['film_description'] = $this->getDescription();

        // Парсим название фильма
        $result['title'] = $this->getPostTitle();

        return $result;
    }

    /**
     * @return string
     */
    private function getImageUrl(): string
    {
        $this->imgElements = $this->dom->getElementsByTagName('img');
        foreach ($this->imgElements as $imgElement) {
            if ($imgElement->hasAttribute('class') && strripos($imgElement->getAttribute('class'), 'hCL kVc L4E MIw') !== false) {
                if (strripos($imgElement->getAttribute('src'), 'originals')) {
                    return $imgElement->getAttribute('src');
                }
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
        $imageNameWithExtension = end($parseName);

        $parseImageExtension = explode('.', $imageUrl);
        $imageExtension = end($parseImageExtension);

        $imageName = str_replace('.' . $imageExtension, '', $imageNameWithExtension);

        $sizePattern = '/[_]\d{2,4}[x_]\d{2,4}/';
        if (preg_match($sizePattern, $imageName, $matches)) {
            $imageSize = $matches[0];
            $imageName = str_replace($imageSize, '', $imageName);
        }

        return [
            'name' => $imageName,
            'extension' => $imageExtension,
        ];
    }

    /**
     * @return string
     */
    private function getDescription(): string
    {
        $divElements = $this->dom->getElementsByTagName('div');
        foreach ($divElements as $divElement) {
            if ($divElement && $divElement->hasAttribute('class')
                && $divElement->getAttribute('class') === 'tBJ dyH iFc sAJ O2T zDA IZT swG CKL'
            ) {
                return trim($divElement->nodeValue);
            }
        }

        return '';
    }

}
