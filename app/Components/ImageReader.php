<?php

namespace App\Components;

use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class ImageReader
{
    //TODO ImageReader

    public $text;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->text = '';
    }

    public function recognizeText($imagePath)
    {
        //ВАРИАНТ 1
        $url = 'https://vision.googleapis.com/v1/images:annotate?key=AIzaSyA-1JhYmhtkhsrGzNFEfYMXBCbFjLVLPvc'; // замените на свой ключ

        $headers = array(
            'Content-Type: application/json; charset=utf-8'
        );

        $file = env('APP_URL') . $imagePath;
        //dd($file);
        ///if (file_exists($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $validExtensions = array('jpeg', 'jpg', 'gif', 'png', 'webp', 'svg');

            if (in_array($ext, $validExtensions)) {

                $requestBody = [
                    'requests' => [
                        [
                            'image' => [
                                'content' => base64_encode(file_get_contents($file))
                            ],
                            'features' => [
                                [
                                    'type' => 'TEXT_DETECTION'
                                ]
                            ]
                        ]
                    ]
                ];
                $jsonData = json_encode($requestBody);
                //dd($jsonData);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($ch);
                curl_close($ch);

                dd($response);

            } else {
                dd('Invalid file extension.');
            }
//        } else {
//            dd('File not found.');
//        }

        //ВАРИАНТ 2
        $httpReq = new \HttpRequest();
        $httpReq->setHeaders(['Content-Type', 'application/json; charset=utf-8']);
        $httpReq->setMethod('POST');
        $httpReq->setUrl('https://vision.googleapis.com/v1/images:annotate?key=AIzaSyA-1JhYmhtkhsrGzNFEfYMXBCbFjLVLPvc'); // замените на свой ключ
        //$httpReq->setTimeout(120000);

        $httpReq->setBody(json_encode($img));
        $res = $httpReq->send();

        dd($res->getBody());

        //dd(file_exists('../resources/project-sorting-389408-8b643c7a590f.json'));
        // Создаем клиента для Google Cloud Vision API
        $client = new ImageAnnotatorClient();

        // Загружаем изображение
        $image = file_get_contents(env('APP_URL') . $imagePath);

        // Создаем объект изображения для анализа
        $visionImage = new \Google\Cloud\Vision\V1\Image();
        $visionImage->setContent($image);

        // Создаем запрос на распознавание текста
        $response = $client->textDetection($visionImage);
        $texts = $response->getTextAnnotations();

        if (!empty($texts)) {
            // Получаем первый результат (считаем его основным текстом)
            $mainText = $texts[0]->getDescription();
            dd($mainText);
            $this->text = $mainText;
        }

        $client->close();
    }
}
