<?php

// Магазины, которые узнаём по адресу ссылки — поле "Магазин" у пина "Товар" ("Спарсить информацию" / "Заполнить по
// ссылке", PostProductController::linkPreview).
//
// domains — домен магазина без "www." (поддомены тоже подходят: a.aliexpress.com, share.temu.com); вместо зоны можно
//           написать "*", тогда подходит любая: "amazon.*" — amazon.com, amazon.de, amazon.co.uk…
// name    — как подставить, если у пользователя такого магазина ещё нет
// aliases — как пользователь мог уже записать этот магазин (без учёта регистра): если такой уже есть в его
//           товарах или "Запасах", подставляем его написание, чтобы не плодить "Aliexpress" и "AliExpress"
//
// После правки этого файла — php artisan config:cache (конфиг закэширован).

return [
    'by_domain' => [
        ['domains' => ['aliexpress.*', 'aliexpress.com', 'aliexpress.ru', 'aliexpress.us'], 'name' => 'AliExpress', 'aliases' => ['aliexpress', 'ali express', 'алиэкспресс', 'али экспресс', 'али']],
        ['domains' => ['temu.com', 'temu.to'], 'name' => 'Temu', 'aliases' => ['temu', 'тему', 'тему.ком']],
        ['domains' => ['rozetka.com.ua'], 'name' => 'Rozetka', 'aliases' => ['rozetka', 'розетка']],
        ['domains' => ['makeup.com.ua'], 'name' => 'Makeup', 'aliases' => ['makeup', 'мейкап', 'макияж']],
        ['domains' => ['epicentrk.ua'], 'name' => 'Epicentr', 'aliases' => ['epicentr', 'epicentrk', 'эпицентр', 'епіцентр']],
        ['domains' => ['prom.ua'], 'name' => 'Prom', 'aliases' => ['prom', 'prom.ua', 'пром']],
        ['domains' => ['olx.ua'], 'name' => 'OLX', 'aliases' => ['olx', 'олх']],
        ['domains' => ['eva.ua'], 'name' => 'EVA', 'aliases' => ['eva', 'ева']],
        ['domains' => ['allo.ua'], 'name' => 'Allo', 'aliases' => ['allo', 'алло']],
        ['domains' => ['comfy.ua'], 'name' => 'Comfy', 'aliases' => ['comfy', 'комфи']],
        ['domains' => ['foxtrot.com.ua'], 'name' => 'Foxtrot', 'aliases' => ['foxtrot', 'фокстрот']],
        ['domains' => ['kasta.ua'], 'name' => 'Kasta', 'aliases' => ['kasta', 'каста']],
        ['domains' => ['notino.ua'], 'name' => 'Notino', 'aliases' => ['notino', 'нотино']],
        ['domains' => ['amazon.*'], 'name' => 'Amazon', 'aliases' => ['amazon', 'амазон']],
        ['domains' => ['ebay.*'], 'name' => 'eBay', 'aliases' => ['ebay', 'ибей']],
        ['domains' => ['shein.com'], 'name' => 'SHEIN', 'aliases' => ['shein', 'шейн']],
    ],
];
