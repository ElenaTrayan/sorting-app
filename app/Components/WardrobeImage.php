<?php

namespace App\Components;

use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Картинки раздела "Мой гардероб" (вещи и образы): лежат в wardrobe/{user_id}/ на диске local,
 * в БД — {name, extension, path}, как у фото контактов. Картинку вещи можно взять из поста:
 * файл копируется, поэтому вещь не зависит от того, что потом станет с постом.
 */
class WardrobeImage
{
    private const MAX_WIDTH = 1200;

    /**
     * @param UploadedFile $file
     * @param int $userId
     * @return array{name: string, extension: string, path: string}
     */
    public static function store(UploadedFile $file, int $userId): array
    {
        // AVIF сразу конвертируем в WebP (см. AvifImage)
        $file = AvifImage::normalize($file);

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = Str::random(20);
        $relativeDir = self::directory($userId);

        Storage::disk('local')->makeDirectory($relativeDir);
        $file->storeAs($relativeDir, $name . '.' . $extension, 'local');
        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;

        self::downsize($relativePath);

        return ['name' => $name, 'extension' => $extension, 'path' => $relativePath];
    }

    /**
     * Картинки поста для выбора: [['thumb' => путь превью, 'source' => путь копируемого файла], ...].
     * Порядок и число — как у оригиналов; среднюю/маленькую копию берём, только если они есть у всех
     * картинок поста (ключи в этих колонках свои, сопоставить можно только по порядку).
     *
     * @param Post $post
     * @return array
     */
    public static function postImages(Post $post): array
    {
        $original = array_values(UploadImageController::normalizeImages($post->original_image));
        $medium = array_values(UploadImageController::normalizeImages($post->medium_image));
        $small = array_values(UploadImageController::normalizeImages($post->small_image));

        if (!$original) {
            // у старых постов могла остаться только уменьшенная копия
            $original = $medium ?: $small;
        }
        if (count($medium) !== count($original)) {
            $medium = $original;
        }
        if (count($small) !== count($original)) {
            $small = $medium;
        }

        $images = [];
        foreach ($original as $i => $image) {
            if (empty($image['path'])) {
                continue;
            }
            $images[] = [
                'thumb' => $small[$i]['path'] ?? $image['path'],
                'source' => $medium[$i]['path'] ?? $image['path'],
            ];
        }

        return $images;
    }

    /**
     * Копия картинки поста для вещи; null — у поста нет такой картинки или файла нет на диске
     *
     * @param Post $post
     * @param int $index номер картинки в посте (см. postImages())
     * @param int $userId
     * @return array{name: string, extension: string, path: string}|null
     */
    public static function copyFromPost(Post $post, int $index, int $userId): ?array
    {
        $source = self::postImages($post)[$index]['source'] ?? null;
        $disk = Storage::disk('local');
        if ($source === null || !$disk->exists($source)) {
            return null;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION)) ?: 'jpg';
        $name = Str::random(20);
        $relativeDir = self::directory($userId);
        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;

        $disk->makeDirectory($relativeDir);
        if (!$disk->copy($source, $relativePath)) {
            return null;
        }

        self::downsize($relativePath);

        return ['name' => $name, 'extension' => $extension, 'path' => $relativePath];
    }

    /**
     * Повернуть/отразить фото (кнопки на фото в форме вещи). Результат — новый файл рядом: у картинки с тем же
     * адресом браузер показывал бы старую версию из кеша. Порядок — как в форме (wardrobe.js): сначала
     * отражение по горизонтали, потом поворот по часовой стрелке.
     *
     * @param array $imageData {name, extension, path}
     * @param int $quarterTurns поворотов на 90° по часовой стрелке (0..3)
     * @param bool $flip отразить по горизонтали
     * @return array {name, extension, path} — новый файл; исходные данные, если менять нечего или не получилось
     */
    public static function transform(array $imageData, int $quarterTurns, bool $flip): array
    {
        $quarterTurns = (($quarterTurns % 4) + 4) % 4;
        $extension = strtolower((string)($imageData['extension'] ?? ''));
        // у GIF при пересохранении пропала бы анимация
        if (($quarterTurns === 0 && !$flip) || empty($imageData['path']) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $imageData;
        }

        try {
            $disk = Storage::disk('local');
            $image = Image::make($disk->path($imageData['path']));
            // фото с телефона: браузер показывает его повёрнутым по EXIF, а при пересохранении EXIF теряется —
            // сначала разворачиваем пиксели так, как картинку видно на экране
            if ($extension !== 'png' && $extension !== 'webp') {
                $image->orientate();
            }
            if ($flip) {
                $image->flip('h');
            }
            if ($quarterTurns > 0) {
                // Intervention крутит против часовой стрелки
                $image->rotate(-90 * $quarterTurns);
            }

            $name = Str::random(20);
            $relativePath = '/' . ltrim(dirname($imageData['path']), '/\\') . '/' . $name . '.' . $extension;
            $image->save($disk->path($relativePath), 92);
            $disk->delete($imageData['path']);

            return ['name' => $name, 'extension' => $extension, 'path' => $relativePath];
        } catch (\Throwable $e) {
            // не смогли обработать — оставляем как есть
            return $imageData;
        }
    }

    public static function delete(?array $imageData): void
    {
        if (empty($imageData['path'])) {
            return;
        }

        Storage::disk('local')->delete($imageData['path']);
    }

    private static function directory(int $userId): string
    {
        return 'wardrobe/' . $userId;
    }

    // приводим к разумному размеру, как и у остальных загрузок в проекте
    private static function downsize(string $relativePath): void
    {
        try {
            $absolutePath = Storage::disk('local')->path($relativePath);
            $image = Image::make($absolutePath);
            if ($image->width() > self::MAX_WIDTH) {
                $image->resize(self::MAX_WIDTH, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($absolutePath);
            }
        } catch (\Throwable $e) {
            // не смогли обработать — оставляем как есть
        }
    }
}
