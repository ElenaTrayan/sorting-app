<?php

namespace App\Components;

use Exception;
use Illuminate\Http\UploadedFile;

/**
 * Загрузка картинок в формате AVIF. Хранить их как есть нельзя: Intervention Image 2 (GD-драйвер) AVIF не читает,
 * getimagesize() в PHP 8.1 возвращает для него 0×0 — превью, хеши дубликатов и размеры по такому файлу не посчитать.
 * Поэтому загруженный AVIF сразу конвертируется в WebP (GD умеет читать AVIF с PHP 8.1), и дальше файл идёт
 * по обычному пути как .webp. Прозрачность сохраняется; у анимированного AVIF остаётся первый кадр.
 *
 * Посты — toWebp() (без превью и хешей картинку в пост не добавить, поэтому ошибка конвертации = ошибка загрузки),
 * гардероб, контакты, вишлист — normalize() / bytesToWebp() (не получилось — сохраняется исходный файл).
 */
class AvifImage
{
    private const WEBP_QUALITY = 90;

    public static function isAvif(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'image/avif'
            || strtolower($file->getClientOriginalExtension()) === 'avif';
    }

    /**
     * Конвертирует загруженный AVIF в WebP. Результат лежит во временном файле, который сам удалится
     * в конце запроса, — сохранить картинку к себе нужно до этого
     *
     * @param UploadedFile $file
     * @return UploadedFile - тот же файл в WebP, с тем же именем и расширением .webp
     * @throws Exception - сервер не умеет читать AVIF или файл повреждён
     */
    public static function toWebp(UploadedFile $file): UploadedFile
    {
        if (!function_exists('imagecreatefromavif')) {
            throw new Exception('Сервер не поддерживает изображения AVIF.');
        }

        $webpPath = self::saveAsWebp(@imagecreatefromavif($file->getRealPath()));
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';

        // test = true: файл создан на сервере, а не пришёл из формы — is_uploaded_file() для него не пройдёт
        return new UploadedFile($webpPath, $name, 'image/webp', null, true);
    }

    /**
     * То же, но без ошибок: не AVIF — файл возвращается как есть; AVIF, который не удалось прочитать, — тоже
     * (сохранится исходником, просто без уменьшения)
     *
     * @param UploadedFile $file
     * @return UploadedFile
     */
    public static function normalize(UploadedFile $file): UploadedFile
    {
        if (!self::isAvif($file)) {
            return $file;
        }

        try {
            return self::toWebp($file);
        } catch (\Throwable $e) {
            return $file;
        }
    }

    /**
     * Скачанная по ссылке картинка: AVIF -> WebP
     *
     * @param string $bytes - содержимое AVIF-файла
     * @return string|null - содержимое WebP; null — конвертировать не удалось
     */
    public static function bytesToWebp(string $bytes): ?string
    {
        try {
            return file_get_contents(self::saveAsWebp(@imagecreatefromstring($bytes)));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param \GdImage|false $source - прочитанный AVIF
     * @return string - путь к временному WebP-файлу (удалится в конце запроса)
     * @throws Exception
     */
    private static function saveAsWebp($source): string
    {
        if (!$source) {
            throw new Exception('Не удалось прочитать изображение AVIF.');
        }

        // без этого прозрачный фон в WebP станет чёрным
        imagealphablending($source, false);
        imagesavealpha($source, true);

        $webpPath = tempnam(sys_get_temp_dir(), 'avif');
        register_shutdown_function(static function () use ($webpPath) {
            @unlink($webpPath);
        });

        $saved = @imagewebp($source, $webpPath, self::WEBP_QUALITY);
        imagedestroy($source);

        if (!$saved || filesize($webpPath) === 0) {
            throw new Exception('Не удалось преобразовать изображение AVIF.');
        }

        return $webpPath;
    }
}
