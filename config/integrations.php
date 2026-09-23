<?php

/*
|--------------------------------------------------------------------------
| Внешние API
|--------------------------------------------------------------------------
|
| Реестр внешних сервисов, которые использует приложение. Админ в разделе
| "Внешние API" может отключить интеграцию и/или задать её ключи прямо из
| панели — значения хранятся в таблице api_integrations (ключи шифруются).
| Если в панели ключ не задан, берётся значение из config/.env (см. 'config' у поля).
|
| fields: name => ['label' => подпись, 'secret' => скрывать ли значение, 'config' => путь в config()]
|
| Чтобы подключить новый сервис — добавить его сюда и читать ключ через
| App\Services\Integrations\IntegrationManager::credential().
|
*/

return [

    'ocr_space' => [
        'name' => 'OCR.space',
        'description' => 'Распознавание текста на картинках (поп-ап "Добавить высказывание", пины)',
        'url' => 'https://ocr.space/ocrapi',
        'fields' => [
            'key' => ['label' => 'API-ключ', 'secret' => true, 'config' => 'services.ocr_space.key'],
        ],
    ],

    'photon' => [
        'name' => 'Photon (Komoot)',
        'description' => 'Геокодинг на данных OSM — подсказки города проживания в контактах',
        'url' => 'https://photon.komoot.io',
        'fields' => [],
    ],

    'nominatim' => [
        'name' => 'Nominatim (OpenStreetMap)',
        'description' => 'Резервный геокодинг — русские названия городов в контактах',
        'url' => 'https://nominatim.openstreetmap.org',
        'fields' => [],
    ],

];
