<?php

namespace App\Components;

/**
 * Распознавание текста на изображении через OCR.space (https://ocr.space/ocrapi) —
 * бесплатный сервис (до 25 000 запросов/месяц на бесплатном ключе, без привязки карты),
 * используется вместо Google Cloud Vision, для которого на проекте не был включён
 * биллинг. Ключ — services.ocr_space.key (см. config/services.php), берётся из
 * .env: OCR_SPACE_API_KEY (получить свой бесплатный ключ: https://ocr.space/ocrapi/freekey).
 */
class ImageReader
{
    private const ENDPOINT = 'https://api.ocr.space/parse/image';

    /**
     * @param string $imageContent Сырые байты изображения (например, file_get_contents() загруженного файла)
     * @return string Распознанный текст (пустая строка, если текста не найдено)
     * @throws \Throwable Любая ошибка обращения к OCR.space — пробрасывается вызывающему коду,
     *                     чтобы он мог показать пользователю понятное сообщение об ошибке
     */
    public function recognizeText(string $imageContent): string
    {
        $apiKey = config('services.ocr_space.key');
        if (empty($apiKey)) {
            throw new \RuntimeException('Не задан OCR_SPACE_API_KEY в .env (бесплатный ключ: https://ocr.space/ocrapi/freekey)');
        }

        // OCR.space принимает файл через multipart — проще всего отдать через CURLFile,
        // а для этого байты изображения (у нас их прислал браузер, не путь на диске)
        // нужно сначала временно положить на диск
        $tmpPath = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
        file_put_contents($tmpPath, $imageContent);

        try {
            $ch = curl_init(self::ENDPOINT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new \CURLFile($tmpPath, 'image/png', 'image.png'),
                'language' => 'rus', // приложение русскоязычное; при необходимости сделать настраиваемым
                'OCREngine' => 2,    // engine 2 точнее распознаёт кириллицу и скриншоты с мелким текстом
                'scale' => 'true',
                'isTable' => 'false',
            ]);

            $raw = curl_exec($ch);
            if ($raw === false) {
                throw new \RuntimeException('Не удалось обратиться к OCR.space: ' . curl_error($ch));
            }
            curl_close($ch);
        } finally {
            @unlink($tmpPath);
        }

        $response = json_decode($raw, true);
        if (!is_array($response)) {
            throw new \RuntimeException('OCR.space вернул некорректный ответ');
        }

        // ошибки уровня самого запроса (например, "ключ throttled" при перегрузке) приходят
        // в другом формате — {"error": "...", "details": "..."} — без ParsedResults вообще,
        // отдельно от ошибок уровня "не смогли обработать конкретное изображение"
        // (IsErroredOnProcessing + ErrorMessage). Не проверять первый формат — значит молча
        // получать "текст не найден" вместо реальной ошибки.
        if (!empty($response['error'])) {
            throw new \RuntimeException($response['error']);
        }

        if (!empty($response['IsErroredOnProcessing'])) {
            $errorMessage = $response['ErrorMessage'] ?? 'неизвестная ошибка';
            throw new \RuntimeException(is_array($errorMessage) ? implode('; ', $errorMessage) : $errorMessage);
        }

        $texts = [];
        foreach ($response['ParsedResults'] ?? [] as $result) {
            if (!empty($result['ParsedText'])) {
                $texts[] = trim($result['ParsedText']);
            }
        }

        $text = trim(implode("\n", $texts));

        // соцсети часто дописывают в шапке поста кнопку "Translate"/"See translation" —
        // OCR цепляет её вместе с текстом самого поста, обрезаем всё начиная с неё
        $text = preg_replace('/\bTranslat\w*.*/isu', '', $text);

        return trim($text);
    }
}
