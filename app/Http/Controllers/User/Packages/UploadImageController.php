<?php

namespace App\Http\Controllers\User\Packages;

use App\Components\AvifImage;
use App\Http\Controllers\User\DevHelpersContoller;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use App\Models\PostsCategory;
use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use \Exception;

class UploadImageController extends Controller
{
    const TEMP_IMAGE_PATH = 'app/public/temp_directory/'; //temporary image path
    const IMAGE_PATH = 'images/';

    // метка временного имени: "photo--k3x9q2" — файл с таким же именем уже лежит во временной папке (другая
    // вкладка с формой), см. stageFileToTempDirectory(). "--" в обычных именах не бывает (str_slug схлопывает
    // дефисы), поэтому метку можно безошибочно убрать при сохранении в пост (resolveFreeTargetNames)
    private const TEMP_NAME_MARKER = '--';
    private const TEMP_NAME_MARKER_PATTERN = '/--[a-z0-9]{6}$/';

    // public — по ним же пересобирает medium/small PostController::afterPostImageReplaced()
    public const MAX_IMAGE_MEDIUM_WIDTH = '800';
    public const MAX_IMAGE_MEDIUM_HEIGHT = '800';
    public const MAX_IMAGE_SMALL_WIDTH = '350';
    public const MAX_IMAGE_SMALL_HEIGHT = '350';

    // Дубликатом считаем только когда совпадают ОБА разных алгоритма хеша плюс похожие
    // пропорции кадра. Одного DifferenceHash/PerceptualHash по отдельности недостаточно —
    // на реальных данных сайта (много инфографики/карточек с текстом на плоском фоне)
    // они регулярно путают между собой визуально никак не связанные изображения:
    // проверено на выборке 250 случайных изображений, у одного DifferenceHash (порог 15)
    // было ~2 ложных совпадения на каждую новую загрузку по всему сайту, у комбинации
    // из трёх условий ниже — ~0.05 (в ~45 раз реже), при этом настоящие дубликаты
    // (то же фото в другом размере/качестве) уверенно проходят с запасом (расстояния 0-3)
    const DUPLICATE_DIFF_HASH_THRESHOLD = 10;
    const DUPLICATE_PERCEPTUAL_HASH_THRESHOLD = 14;
    const DUPLICATE_MAX_ASPECT_RATIO = 1.3;

    private array $errors = [];
    private int $userId;

    /* Error messages */
    private const EM_ERROR_LOADING_IMAGE = 'Ошибка при загрузке изображения.';
    private const EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY = 'Ошибка при сохранении изображения во временную папку.';
    private const EM_NO_FILES_PROVIDED_FOR_UPLOAD = 'No files were provided for upload.';
    //получить размер изображения - Storage::size($path);

    //TODO ОШИБКА:
    //Can't write image data to path (D:\Webprojects\sorting\storage\app/public/temp_directory1/398524193-649704540366648-8345501453876316855-n_350_350.jpg)

    /** uploadImageToTempDirectory -> uploadImagesToTempDirectory
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImagesToTempDirectory(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            if (!request()->hasFile('files')) {
                throw new Exception(self::EM_NO_FILES_PROVIDED_FOR_UPLOAD);
            }

            $images = [];
            // картинки, уже загруженные на форме этой вкладки (её sessionStorage "images") — только их файлы
            // можно переиспользовать при повторной загрузке, см. stageFileToTempDirectory()
            $ownStagedNames = array_values(array_filter((array)request()->input('staged_names', []), 'is_string'));

            // Картинку, похожая на которую уже стоит на этой же форме (ещё не сохранённую), сразу на форму не
            // ставим: она остаётся во временной папке, а форма спрашивает в окне сравнения, разные ли это картинки
            // (form_duplicates, admin.js showNextFormDuplicate). Дубли среди картинок уже добавленных постов
            // ищутся при сохранении поста (PostController::store)
            $rejected = [];
            $formDuplicates = [];
            $formHashes = $this->stagedImageHashes($ownStagedNames);

            foreach (request()->file('files') as $file) {
                // каждый файл — со своей причиной отказа, остальные файлы загрузки идут дальше
                $problem = $this->uploadProblem($file);
                if ($problem !== null) {
                    $rejected[] = ['file' => $file->getClientOriginalName(), 'message' => $problem];
                    continue;
                }

                try {
                    $staged = $this->stageFileToTempDirectory($file, $ownStagedNames);
                } catch (\Throwable $e) {
                    // в лог — с именем файла и местом ошибки, на форму — текст ошибки
                    Log::warning('Загрузка картинки во временную папку не удалась', [
                        'file' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'error' => get_class($e) . ': ' . $e->getMessage(),
                        'at' => $e->getFile() . ':' . $e->getLine(),
                    ]);
                    $rejected[] = ['file' => $file->getClientOriginalName(), 'message' => 'не удалось обработать картинку (' . $e->getMessage() . ')'];
                    continue;
                }

                // тот же файл повторно загрузили на эту же форму — он уже проверен и стоит на ней
                if (!in_array($staged['name'], $ownStagedNames, true)) {
                    $match = $this->stagedDuplicateOf($staged['name'], $staged['data'], $formHashes);
                    if ($match !== null) {
                        $formDuplicates[] = $this->formDuplicateInfo($file->getClientOriginalName(), $staged, $match);
                        continue;
                    }
                }

                $images[$staged['name']] = $staged['data'];
            }

            return response()->json([
                'status' => true,
                // (object): пустой список должен уйти как {}, а не [] — форма складывает его со своим объектом картинок
                'images' => (object)$images,
                'rejected' => $rejected,
                'form_duplicates' => $formDuplicates,
            ]);

        } catch (\Throwable $exception) {
            Log::warning('Загрузка картинок во временную папку не удалась', [
                'error' => get_class($exception) . ': ' . $exception->getMessage(),
                'at' => $exception->getFile() . ':' . $exception->getLine(),
            ]);

            return response()->json(['status' => false, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * Почему загруженный файл нельзя принять как картинку (раньше была общая проверка mimes на весь запрос —
     * один неподходящий файл давал "The given data was invalid." без указания, какой и почему)
     *
     * @param UploadedFile $file
     * @return string|null текст для формы; null — файл подходит
     */
    private function uploadProblem(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            // не дошёл до сервера целиком: больше upload_max_filesize, оборвалась передача и т.п.
            return 'файл не получен сервером (' . $file->getErrorMessage() . ')';
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['file' => $file],
            ['file' => 'mimes:jpeg,png,jpg,gif,svg,webp,avif']
        );
        if ($validator->fails()) {
            // формат смотрим по содержимому, а не по расширению: ".jpg" может оказаться чем угодно
            return 'формат не поддерживается — по содержимому это ' . ($file->getMimeType() ?: 'неизвестный тип')
                . ', а не JPG/PNG/GIF/WebP/AVIF/SVG';
        }

        return null;
    }

    /**
     * Хеши и размеры картинки для сравнения "та же ли это картинка" (см. DUPLICATE_*_THRESHOLD)
     *
     * @param string $absolutePath
     * @return array|null ['phash', 'perceptual', 'width', 'height', 'file_size']; null — не картинка или хеш не посчитать
     */
    private function imageFingerprint(string $absolutePath): ?array
    {
        $size = file_exists($absolutePath) ? @getimagesize($absolutePath) : false;
        if (!$size || empty($size[0]) || empty($size[1])) {
            return null;
        }

        try {
            return [
                'phash' => self::computeImageHash($absolutePath),
                'perceptual' => self::computePerceptualHash($absolutePath),
                'width' => $size[0],
                'height' => $size[1],
                'file_size' => filesize($absolutePath) ?: 0,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Данные для окна сравнения на форме создания (admin.js showNextFormDuplicate): только что загруженная
     * картинка и похожая на неё, которая уже стоит на форме
     *
     * @param string $fileName имя загруженного файла — для подписи
     * @param array $staged ['name' => ..., 'data' => ...] из stageFileToTempDirectory()
     * @param array $match отпечаток той, что уже на форме (stagedImageHashes()), и новой — ['existing', 'new']
     * @return array
     */
    private function formDuplicateInfo(string $fileName, array $staged, array $match): array
    {
        $new = $match['new'];
        $existing = $match['existing'];
        $newArea = $new['width'] * $new['height'];
        $oldArea = $existing['width'] * $existing['height'];

        return [
            'file' => $fileName,
            // имя и данные новой картинки — форма добавит её, если скажут "это разные"
            'name' => $staged['name'],
            'image' => $staged['data'],
            'new_image' => [
                'thumbnail_url' => '/storage/' . $staged['data']['image_path'],
                'width' => $new['width'],
                'height' => $new['height'],
                'file_size' => $new['file_size'],
            ],
            'existing' => [
                'name' => $existing['name'],
                'thumbnail_url' => '/storage/' . $existing['path'],
                'width' => $existing['width'],
                'height' => $existing['height'],
                'file_size' => $existing['file_size'],
            ],
            // лучше качество — больше пикселей, при равном разрешении — больший файл
            'new_is_better' => $newArea !== $oldArea ? $newArea > $oldArea : $new['file_size'] > $existing['file_size'],
        ];
    }

    /**
     * Отпечатки картинок, уже стоящих на форме создания (их файлы — во временной папке пользователя)
     *
     * @param string[] $stagedNames имена картинок формы
     * @return array[] см. imageFingerprint(), плюс 'name' (имя картинки на форме) и 'path' (от корня storage)
     */
    private function stagedImageHashes(array $stagedNames): array
    {
        if (!$stagedNames) {
            return [];
        }

        $disk = Storage::disk('local');
        $hashes = [];
        foreach ($disk->files('temp_directory/' . Auth::id()) as $path) {
            // оригинал называется ровно как картинка на форме; у small/medium копий в имени ещё и размер
            $name = pathinfo($path, PATHINFO_FILENAME);
            if (!in_array($name, $stagedNames, true)) {
                continue;
            }
            $fingerprint = $this->imageFingerprint($disk->path($path));
            if ($fingerprint !== null) {
                $hashes[] = $fingerprint + ['name' => $name, 'path' => $path];
            }
        }

        return $hashes;
    }

    /**
     * Похожая картинка, которая уже стоит на этой же форме создания. С картинками уже добавленных постов здесь
     * не сравниваем — это делается при сохранении поста (PostController::findDuplicatesBeforeCreate).
     * Картинку, у которой похожей нет, запоминает в $formHashes — следующие файлы той же загрузки
     * сравниваются и с ней
     *
     * @param string $name имя только что загруженной картинки
     * @param array $image её данные (image_path, ...)
     * @param array[] $formHashes отпечатки картинок формы, см. stagedImageHashes()
     * @return array|null ['existing' => отпечаток той, что на форме, 'new' => отпечаток новой]; null — похожей нет
     */
    private function stagedDuplicateOf(string $name, array $image, array &$formHashes): ?array
    {
        $fingerprint = $this->imageFingerprint(Storage::disk('local')->path('/' . $image['image_path']));
        if ($fingerprint === null) {
            return null; // хеш не посчитать — как и при сохранении поста, загрузку из-за этого не блокируем
        }

        foreach ($formHashes as $other) {
            if ($this->isSameImage($fingerprint, $other)) {
                return ['existing' => $other, 'new' => $fingerprint];
            }
        }

        $formHashes[] = $fingerprint + ['name' => $name, 'path' => $image['image_path']];

        return null;
    }

    /**
     * Те же условия "это одна и та же картинка", что и в findDuplicateByHash(): похожие пропорции и оба хеша
     *
     * @param array $a см. imageFingerprint()
     * @param array $b
     * @return bool
     */
    private function isSameImage(array $a, array $b): bool
    {
        $aspectA = $a['width'] / $a['height'];
        $aspectB = $b['width'] / $b['height'];
        if (max($aspectA, $aspectB) / min($aspectA, $aspectB) > self::DUPLICATE_MAX_ASPECT_RATIO) {
            return false;
        }

        return Hash::fromBits($a['phash'])->distance(Hash::fromBits($b['phash'])) <= self::DUPLICATE_DIFF_HASH_THRESHOLD
            && Hash::fromBits($a['perceptual'])->distance(Hash::fromBits($b['perceptual'])) <= self::DUPLICATE_PERCEPTUAL_HASH_THRESHOLD;
    }

    /**
     * Сохраняет один загруженный файл во временную папку пользователя и генерирует
     * для него small/medium копии. Вынесено из uploadImagesToTempDirectory(), чтобы
     * этой же логикой мог пользоваться и одиночный "загрузить и сразу привязать к посту".
     *
     * Временная папка у пользователя одна на все вкладки, поэтому файл с тем же именем может принадлежать
     * форме в другой вкладке (или остаться после неудачного создания поста). Переиспользуем его только если
     * это тот же файл, уже загруженный на этой же форме ($ownStagedNames) — тогда превью не задваивается.
     * Иначе — новое имя с временной меткой ("photo--k3x9q2"), чтобы вкладки не перезаписывали и не удаляли
     * файлы друг друга; при сохранении в пост метка убирается (resolveFreeTargetNames)
     *
     * @param UploadedFile $file
     * @param string[] $ownStagedNames - имена картинок, уже загруженных на форме этой вкладки
     * @return array{name: string, data: array}
     * @throws Exception
     */
    public function stageFileToTempDirectory(UploadedFile $file, array $ownStagedNames = []): array
    {
        // AVIF сразу конвертируем в WebP (почему — см. AvifImage) и дальше работаем с ним как с обычным .webp
        if (AvifImage::isAvif($file)) {
            $file = AvifImage::toWebp($file);
        }

        //Returns the original file name
        $originalImageName = $file->getClientOriginalName();

        $path_info = pathinfo($originalImageName);
        if (empty($path_info['filename']) || empty($path_info['extension'])) {
            throw new Exception(self::EM_ERROR_LOADING_IMAGE);
        }

        $this->userId = Auth::id();

        //Очищаем имя файла от лишних символов
        $imageName = $this->sanitizeFileName($path_info['filename']);
        $imageNameWithExtension = $imageName . '.' . $path_info['extension'];
        $tempPathForImage = 'temp_directory' . '/' . $this->userId;

        $disk = Storage::disk('local');
        if ($disk->exists($tempPathForImage . '/' . $imageNameWithExtension)) {
            // тот же файл повторно загрузили на этой же форме — перезаписываем его тем же содержимым
            // под тем же именем, на форме превью не задваивается
            $sameFileOnThisForm = in_array($imageName, $ownStagedNames, true)
                && filesize($file) === $disk->size($tempPathForImage . '/' . $imageNameWithExtension);

            if (!$sameFileOnThisForm) {
                $baseName = $imageName;
                do {
                    $imageName = $baseName . self::TEMP_NAME_MARKER . strtolower(Str::random(6));
                    $imageNameWithExtension = $imageName . '.' . $path_info['extension'];
                } while ($disk->exists($tempPathForImage . '/' . $imageNameWithExtension));
            }
        }

        //сохраняем оригинальное изображение во временную папку
        $path = Storage::putFileAs($tempPathForImage, $file, $imageNameWithExtension);

        if (empty($path)) {
            throw new Exception(self::EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY);
        }

        $data = $this->saveSmallAndMediumImageSizesToTempDirectory(
            $path,
            $imageName,
            $path_info['extension'],
            $tempPathForImage . '/'
        );
        // дата съёмки из EXIF — форма создания подставит её в поле "Дата" пина (event-date.js)
        $takenAt = self::exifTakenAt(Storage::path($path));
        if ($takenAt !== null) {
            $data['taken_at'] = $takenAt;
        }

        return [
            'name' => $imageName,
            'data' => $data,
        ];
    }

    /**
     * Дата съёмки фото из EXIF (DateTimeOriginal, иначе DateTimeDigitized) — её пишут камера и телефон.
     * У картинок из интернета и мессенджеров EXIF обычно вырезан — тогда null
     *
     * @param string $absolutePath
     * @return string|null 'Y-m-d'
     */
    public static function exifTakenAt(string $absolutePath): ?string
    {
        if (!function_exists('exif_read_data') || !is_file($absolutePath)) {
            return null;
        }

        try {
            $exif = @exif_read_data($absolutePath, 'EXIF');
        } catch (\Throwable $e) {
            return null;
        }

        // "2024:03:12 14:22:05"; камеры без часов пишут "0000:00:00 00:00:00"
        foreach (['DateTimeOriginal', 'DateTimeDigitized'] as $key) {
            if (!empty($exif[$key]) && preg_match('/^(\d{4}):(\d{2}):(\d{2})/', (string)$exif[$key], $m)
                && (int)$m[1] >= 1900 && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return $m[1] . '-' . $m[2] . '-' . $m[3];
            }
        }

        return null;
    }

    /**
     * уменьшаем изображение (s_image) до MAX_IMAGE_SMALL_WIDTH и MAX_IMAGE_SMALL_HEIGHT
     * (например, 350_528)
     *
     * @param string $imagePath
     * @param string $imageName
     * @param string $imageExtension
     * @param array $imageSize
     * @param string $tempPathForImage
     * @return array|array[]
     */
    public function saveSmallImageToTempDirectory(
        string $imagePath,
        string $imageName,
        string $imageExtension,
        array $imageSize,
        string $tempPathForImage
    ): array
    {
        if ($imageSize[0] >= self::MAX_IMAGE_SMALL_WIDTH && $imageSize[1] >= self::MAX_IMAGE_SMALL_HEIGHT) {
            $imageSmall = $this->resizeImage(
                $imagePath,
                $imageName,
                $imageExtension,
                false,
                self::MAX_IMAGE_SMALL_WIDTH,
                self::MAX_IMAGE_SMALL_HEIGHT
            );

            $smallImageName = $imageSmall->filename;

            if (!empty($smallImageName)) {
                return [
                    's_image_name' => $smallImageName,
                    's_image_path' => $tempPathForImage,
                ];
            }
        }

        return [];
    }

    /**
     * уменьшаем изображение (m_image) до MAX_IMAGE_MEDIUM_WIDTH и MAX_IMAGE_MEDIUM_HEIGHT
     * (например, 800_1422)
     *
     * @param string $imagePath
     * @param string $imageName
     * @param string $imageExtension
     * @param array $imageSize
     * @param string $tempPathForImage
     * @return array|array[]
     */
    public function saveMediumImageToTempDirectory(
        string $imagePath,
        string $imageName,
        string $imageExtension,
        array $imageSize,
        string $tempPathForImage
    ): array
    {
        if ($imageSize[0] >= self::MAX_IMAGE_MEDIUM_WIDTH && $imageSize[1] >= self::MAX_IMAGE_MEDIUM_HEIGHT) {
            $imageMedium = $this->resizeImage(
                $imagePath,
                $imageName,
                $imageExtension,
                false,
                self::MAX_IMAGE_MEDIUM_WIDTH,
                self::MAX_IMAGE_MEDIUM_HEIGHT
            );

            $mediumImageName = $imageMedium->filename;

            if (!empty($mediumImageName)) {
                return [
                    'm_image_name' => $mediumImageName,
                    'm_image_path' => $tempPathForImage,
                ];
            }
        }

        return [];
    }

    /**
     * Save small and medium image sizes to temp directory
     *
     * @param string $imagePath - example: temp_directory/1/jysk-205344.jpg
     * @param string $imageName - example: jysk-205344
     * @param string $imageExtension - example: jpg
     * @param string $tempPathForImage - example: temp_directory/1
     * @return array
     */
    private function saveSmallAndMediumImageSizesToTempDirectory(
        string $imagePath,
        string $imageName,
        string $imageExtension,
        string $tempPathForImage
    ): array
    {
        $originalImage = [
            'image_name' => $imageName,
            'image_extension' => $imageExtension,
            'image_path' => $imagePath,
        ];

        //Получаем путь к изображению
        $imagePath = Storage::path($imagePath);
        //получаем размер изображения: $imageSize[0] - ширина, $imageSize[1] - высота
        $imageSize = self::imageSize($imagePath);
        if (!$imageSize) {
            throw new Exception('Не удалось прочитать размер картинки — файл повреждён или это не картинка');
        }

        /* уменьшаем изображение (s_image) до MAX_IMAGE_SMALL_WIDTH и MAX_IMAGE_SMALL_HEIGHT
        (например, 350_528) */
        $smallImage = $this->saveSmallImageToTempDirectory(
            $imagePath,
            $imageName,
            $imageExtension,
            $imageSize,
            $tempPathForImage
        );

        if (!empty($smallImage)) {
            $originalImage = array_merge($originalImage, $smallImage);
        }

        //уменьшаем изображение (m_image) до MAX_IMAGE_MEDIUM_WIDTH и MAX_IMAGE_MEDIUM_HEIGHT
        // (например, 800_1422)
        $mediumImage = $this->saveMediumImageToTempDirectory(
            $imagePath,
            $imageName,
            $imageExtension,
            $imageSize,
            $tempPathForImage
        );

        if (!empty($mediumImage)) {
            $originalImage = array_merge($originalImage, $mediumImage);
        }

        return $originalImage;
    }

    /**
     * @param string $imageUrl
     * @param array $imageInfo
     * @return \Illuminate\Http\JsonResponse|string[]
     */
    public function saveParseImageToTempDirectory(
        string $imageUrl,
        array $imageInfo
    ): array|\Illuminate\Http\JsonResponse
    {
        try {
            $this->userId = Auth::id();

            //удаляем из названия изображения скобки и пробелы
            $imageName = $this->sanitizeFileName($imageInfo['name']);

            $tempPathForImage = 'temp_directory/' . $this->userId;

            //dd( Storage::disk('temp_directory'));
            //dd(Storage::path($imagePath));
            //$imagePath = '../public/storage/temp_directory/' . $this->userId . '/' . $imageName . '.' . $imageInfo['extension'];
            $imagePath = $tempPathForImage . '/' . $imageName . '.' . $imageInfo['extension'];
            $imageSavePath = Storage::disk('local')->path($imagePath);
            //dd($imagePath);

            $response = Http::get($imageUrl);
            $saveToTempDirectory = file_put_contents($imageSavePath, $response);
            if (!$saveToTempDirectory) {
                throw new Exception(self::EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY);
            }

            //$path = Storage::putFileAs(
            //                        'temp_directory/' . $this->userId,
            //                        $file,
            //                        $imageName . '.' . $path_info['extension']
            //                    );
            //
            //                    if (empty($path)) {
            //                        throw new Exception(self::EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY);
            //                    }

            $images[$imageName] = $this->saveSmallAndMediumImageSizesToTempDirectory(
                $imagePath,
                $imageInfo['name'],
                $imageInfo['extension'],
                $tempPathForImage
            );

            //dd($images);

            return $images;

        } catch (Exception $exception) {
            //TODO - Добавить запись ошибок в логи в БД

            return response()->json(['status' => false, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * @param string $filePath
     * @param string $filename
     * @param string $extension
     * @param bool $isQuadratic - если изображение должно быть квадратным
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    public function resizeImage(
        string $filePath,
        string $filename,
        string $extension,
       // string $imagePath,
        bool $isQuadratic = false,
        int $width = 200,
        int $height = 200
    ): \Intervention\Image\Image
    {
        try {
            //var_dump($filePath);
            //D:\Webprojects\sorting\storage\app/public\temp_directory/1/89b158b01ec4ffbdb076024c67a49652.jpg
            //$isQuadratic = true;

            $w_h = $width . '_' . $height;
            $imageName = $filename . '_' . $w_h . '.' . $extension;

            $img = Image::make($filePath);
            $originalWidth = $img->width();
            $originalHeight = $img->height();

            if ($originalHeight > $originalWidth) {
                $height = null;
                $cropSize = ($isQuadratic === true) ? $width : null;
            } elseif ($originalHeight < $originalWidth) {
                $width = null;
                $cropSize = ($isQuadratic === true) ? $height : null;
            } else {
                $cropSize = ($isQuadratic === true) ? $height : null;
            }

            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });

            if (!empty($cropSize)) {
                $img->crop($cropSize, $cropSize);
            }

            //dd(storage_path(self::TEMP_IMAGE_PATH . $this->userId));
            //D:\Webprojects\sorting\storage\app/public/temp_directory/1
            //dd(storage_path());
            //D:\Webprojects\sorting\storage

            $resizeImage = $img->save(storage_path(self::TEMP_IMAGE_PATH . $this->userId) . '/' . $imageName);

            $imageSize2 = getimagesize(storage_path(self::TEMP_IMAGE_PATH . $this->userId) . '/' . $imageName);
            $newName = str_replace($w_h, $imageSize2[0] . '_' . $imageSize2[1], $imageName);

//                $path = Storage::path($imageName);
//                var_dump($path);

            //var_dump($resizeImage->dirname . '/' . $resizeImage->basename);
            //var_dump(file_exists('D:\Webprojects\sorting\storage\app/public/temp_directory/4d21abef01a086c36f71c7d2f3a044d4_350_350.jpg'));
            //var_dump(Storage::disk('local')->exists('/temp_directory/' . $resizeImage->basename));

            if (rename(
                storage_path(self::TEMP_IMAGE_PATH . $this->userId) . '/' . $imageName,
                storage_path(self::TEMP_IMAGE_PATH . $this->userId) . '/' . $newName
            )) {
                // важно обновить не только basename, но и filename/extension/dirname разом —
                // это отдельные публичные свойства (Intervention\Image\File), которые сами
                // по себе не пересчитываются; иначе ->filename ниже (используется как
                // s_image_name/m_image_name) остаётся старым и не совпадает с реальным
                // именем файла на диске после переименования под фактические размеры
                $resizeImage->setFileInfoFromPath(
                    storage_path(self::TEMP_IMAGE_PATH . $this->userId) . '/' . $newName
                );
            }

            return $resizeImage;

        } catch (\Throwable $e) {
            // раньше здесь возвращался JsonResponse — при типе возврата Image это давало TypeError,
            // и настоящая причина (битый файл, нехватка памяти, нет прав на запись) терялась
            throw new Exception('Ошибка при изменении размера изображения: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Удаление файла из временной папки пользователя (temp_directory/{userId}) — крестик на превью
     * формы создания поста, отмена при найденном дубликате, убранное видео. Вместе с картинкой удаляются
     * её уменьшенные копии (name_350_438.jpg, name_800_1000.jpg).
     *
     * Раньше файлы искались по шаблону temp_directory/name.ext — без папки пользователя, куда они на самом
     * деле сохраняются, — ничего не находилось, ответ был "успешно удалено", а файлы копились.
     *
     * @param Request $request JSON {name, extension}
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDownloadFile(Request $request)
    {
        try {
            $json = json_decode($request->getContent());
            $name = (string)($json->name ?? '');
            $extension = (string)($json->extension ?? '');

            // имя — только сам файл, без путей: удалить можно лишь своё и лишь во временной папке
            if ($name === '' || $extension === '' || preg_match('/[\/\\\\]|\.\./', $name . $extension)) {
                return response()->json(['status' => false, 'msg' => 'Ошибка при удалении изображения']);
            }

            if (!self::deleteStagedImageFiles($name, $extension, (int)Auth::id())) {
                return response()->json(['status' => false, 'msg' => 'Ошибка при удалении изображения']);
            }

            // файла уже нет (например, почищен temp:clean) — для формы это тоже успех
            return response()->json(['status' => true, 'msg' => 'Изображение успешно удалено']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'msg' => 'Ошибка при удалении изображения']);
        }
    }

    /**
     * Замена картинки во временной папке её отредактированной версией (обрезка/поворот в окне редактора на
     * форме создания поста, admin.js openImageEditor). Имя и расширение остаются прежними — на форме
     * (sessionStorage "images", порядок превью) ничего не переименовывается; старые уменьшенные копии
     * удаляются (в их именах размеры, после обрезки они другие) и делаются заново
     *
     * @param Request $request name, extension, file
     * @return \Illuminate\Http\JsonResponse {status, image} — данные картинки, как в uploadImagesToTempDirectory()
     */
    public function replaceTempImage(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpeg,png,jpg,webp',
            ]);

            $name = (string)$request->input('name', '');
            $extension = (string)$request->input('extension', '');

            // имя — только сам файл, без путей: заменить можно лишь своё и лишь во временной папке
            if ($name === '' || $extension === '' || preg_match('/[\/\\\\]|\.\./', $name . $extension)) {
                throw new Exception(self::EM_ERROR_LOADING_IMAGE);
            }

            $this->userId = Auth::id();
            $tempPathForImage = 'temp_directory/' . $this->userId;
            $imageNameWithExtension = $name . '.' . $extension;

            if (!Storage::disk('local')->exists($tempPathForImage . '/' . $imageNameWithExtension)) {
                throw new Exception('Картинка не найдена во временной папке — загрузите её заново');
            }

            self::deleteStagedImageFiles($name, $extension, (int)$this->userId);

            $path = Storage::putFileAs($tempPathForImage, $request->file('file'), $imageNameWithExtension);
            if (empty($path)) {
                throw new Exception(self::EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY);
            }

            $data = $this->saveSmallAndMediumImageSizesToTempDirectory($path, $name, $extension, $tempPathForImage . '/');

            return response()->json(['status' => true, 'image' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'error' => 'Не удалось сохранить картинку']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Удаляет из временной папки пользователя загруженную картинку со всеми её размерами
     * (<имя>.jpg, <имя>_350_465.jpg, <имя>_800_1063.jpg). Имя и расширение — только сам файл,
     * без путей (проверяет вызывающий код).
     *
     * @param string $name
     * @param string $extension
     * @param int $userId
     * @return bool false — какой-то из файлов не удалился; файлов уже нет — тоже true
     */
    public static function deleteStagedImageFiles(string $name, string $extension, int $userId): bool
    {
        $pattern = '/^' . preg_quote($name, '/') . '(_\d{2,6}_\d{2,6})?\.' . preg_quote($extension, '/') . '$/';
        $disk = Storage::disk('local');

        foreach ($disk->files('temp_directory/' . $userId) as $file) {
            if (preg_match($pattern, basename($file)) && !$disk->delete($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Копия оригинала картинки поста до правок на странице редактирования (обрезка, поворот, отражение) —
     * по ней "Вернуть оригинал" (PostController::restoreImage). Копия одна — самая первая версия:
     * следующие правки её не перезаписывают
     *
     * @param Post $post
     * @param string $name имя картинки в посте (ключ original_image, с расширением)
     * @return string путь на диске local
     */
    /**
     * getimagesize() без предупреждений. У JPEG с мелкими дефектами ("Corrupt JPEG data: 2 extraneous bytes
     * before marker" — частое дело у картинок из интернета) размер читается правильно, но PHP выдаёт warning,
     * а Laravel превращает его в исключение — и такая картинка не загружалась. Браузеры и GD (при
     * gd.jpeg_ignore_warning=1, по умолчанию) такие файлы открывают нормально
     *
     * @param string $absolutePath
     * @return array|false как у getimagesize()
     */
    public static function imageSize(string $absolutePath)
    {
        return @getimagesize($absolutePath);
    }

    public static function imageBackupPath(Post $post, string $name): string
    {
        return 'image_backups/' . $post->user_id . '/' . $post->id . '/' . $name;
    }

    /**
     * Сохранить копию оригинала перед первой правкой (если копии ещё нет)
     *
     * @param Post $post
     * @param string $name
     * @param string $originalPath путь оригинала на диске local
     * @return void
     * @throws Exception копию сохранить не удалось — править картинку без возможности отката не будем
     */
    public static function backupPostImage(Post $post, string $name, string $originalPath): void
    {
        $disk = Storage::disk('local');
        $backupPath = self::imageBackupPath($post, $name);
        if ($disk->exists($backupPath)) {
            return;
        }
        if (!$disk->copy($originalPath, $backupPath)) {
            throw new Exception('Не удалось сохранить копию оригинала — картинка не изменена');
        }
    }

    public static function hasImageBackup(Post $post, string $name): bool
    {
        return Storage::disk('local')->exists(self::imageBackupPath($post, $name));
    }

    /**
     * Удалить копии оригиналов: одной картинки или (без $name) всех картинок поста
     *
     * @param Post $post
     * @param string|null $name
     * @return void
     */
    public static function deleteImageBackups(Post $post, ?string $name = null): void
    {
        $disk = Storage::disk('local');
        if ($name !== null) {
            $disk->delete(self::imageBackupPath($post, $name));
            return;
        }
        $disk->deleteDirectory('image_backups/' . $post->user_id . '/' . $post->id);
    }

    /**
     * Переместить изображение из временной папки в нужную
     *
     * @param string $oldPath
     * @param string $newPath
     * @return bool|string
     */
    public function moveImage(string $oldPath, string $newPath)
    {
        if (Storage::disk('local')->exists($oldPath) !== true) {
            return 'Ошибка при перемещении файла: файл ' . $oldPath . ' не существует';
        }

        try {
            if (Storage::move($oldPath, $newPath) === true) {
                return true;
            }
        } catch (\Throwable $e) {
            // Flysystem бросает FileExistsException, если файл назначения уже есть
            return 'Ошибка при перемещении файла: ' . $e->getMessage();
        }

        return 'Ошибка при перемещении файла: не удалось переместить файл' . $oldPath . ' в ' . $newPath;
    }

    /**
     * Переместить изображение из временной папки в нужную
     * и получить путь к перемещенному изображению, имя и тип
     *
     * ["extension"] => "jpg"
     *
     * @param string $imageName
     * @param string $imageExtension
     * @param string $oldPath
     * @param string $newPath
     * @return array
     */
    private function saveImage(string $imageName, string $imageExtension, string $oldPath, string $newPath)
    {
        $moveImage = $this->moveImage($oldPath, $newPath);

        // moveImage() возвращает либо true, либо строку с текстом ошибки — а непустая
        // строка тоже truthy в PHP, поэтому раньше ошибка молча принималась за успех
        if ($moveImage === true) {
            return [
                'name' => $imageName,
                'extension' => $imageExtension,
                'path' => $newPath,
            ];
        }

        return [
            'error' => $moveImage
        ];
    }

    /**
     * Подбирает имена для переноса картинки из временной папки в постоянную так, чтобы ни один
     * из размеров не наткнулся на уже существующий файл: "116" → "116-1" → "116-2"...
     * Суффикс один на весь комплект: "116_800_800" → "116-1_800_800".
     *
     * Раньше на этом месте был checkIfFileExists(): при совпадении имени он переносил временный
     * оригинал в корень temp_directory, а результат не использовался — оригинал терялся,
     * medium/small переносились без него, а повторная попытка падала с FileExistsException.
     *
     * @param array $image элемент images формы (image_name, image_extension, m_image_name, s_image_name)
     * @param $userId
     * @param $categoryId
     * @return array ['image_name' => ..., 'm_image_name' => ..., 's_image_name' => ...] — без префикса категории
     */
    private function resolveFreeTargetNames(array $image, $userId, $categoryId): array
    {
        // временную метку ("photo--k3x9q2", см. stageFileToTempDirectory) в пост не переносим —
        // в постоянной папке занятость имени решают суффиксы ниже
        $stagedName = $image['image_name'];
        $baseName = preg_replace(self::TEMP_NAME_MARKER_PATTERN, '', $stagedName);
        $disk = Storage::disk('local');

        for ($i = 0; ; $i++) {
            $suffix = $i === 0 ? '' : '-' . $i;
            $names = ['image_name' => $baseName . $suffix];

            foreach (['m_image_name', 's_image_name'] as $key) {
                if (empty($image[$key])) {
                    continue;
                }
                // "116_800_800" → "116-1_800_800", "116--k3x9q2_800_800" → "116_800_800"
                $names[$key] = strpos($image[$key], $stagedName) === 0
                    ? $baseName . $suffix . substr($image[$key], strlen($stagedName))
                    : $image[$key] . $suffix;
            }

            $taken = false;
            foreach ($names as $name) {
                $target = self::generateImageNameAndPath($name, $image['image_extension'], $userId, $categoryId);
                if ($disk->exists($target['image_path'] . '/' . $target['image_name'])) {
                    $taken = true;
                    break;
                }
            }

            if (!$taken) {
                return $names;
            }
        }
    }

//    /**
//     * Example: 7_6_a17ac3f0262325f5c3bc30cb34fb9350.jpg
//     * categoryParentId_categoryId_imageName.imageExtension
//     *
//     * @param $categoryId
//     * @param $imageName
//     * @param $imageExtension
//     * @return string
//     */
//    public function generateImageName($categoryId, $imageName, $imageExtension): string
//    {
//        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: $categoryId;
//
//        return $categoryParentId . '_' . $categoryId . '_' . $imageName . '.' . $imageExtension;
//    }

    /**
     * $oldPath - путь к файлу (файл может быть в папке или во временной папке - temp_directory)
     *
     * @param string $imageName
     * @param string $imageExtension
     * @param $userId
     * @param $categoryId
     * @param bool $isOriginalImage
     * @param string $oldPath
     * @return string[]
     */
    public static function generateImageNameAndPath(
        string $imageName,
        string $imageExtension,
        $userId,
        $categoryId,
        bool $isOriginalImage = true,
        string $oldPath = ''
    ): array
    {
        if (!$isOriginalImage && !empty($oldPath)) {
            $imageSize = self::getImageSize($oldPath);
        }

        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: '0';
        $today = date("Y-m-d");

        $imageName = $categoryParentId . '_' . $categoryId . '_' . $imageName;

        if (isset($imageSize) && is_array($imageSize)) {
            // "7_6_a17ac3f0262325f5c3bc30cb34fb9350_800_1337.jpg"
            $imageName .= '_' . $imageSize['width'] . '_' . $imageSize['height'] . '.' . $imageExtension;
            //var_dump('imageName = ' . $imageName);
        } else {
            // "7_6_a17ac3f0262325f5c3bc30cb34fb9350.jpg"
            $imageName .= '.' . $imageExtension;
            //var_dump('TEST = ' . $imageName);
        }

        // / user_id / category parent_id / date today / category parent_id - category_id - image_title - image_size - расширение файла
        // "/images/1/7/2020-02-11/image_name.jpg"
        // $imagePath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryId . '/' . $today . '/' . $imageName;
        $imagePath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryId . '/' . $today;

        return [
            'image_name' => $imageName,
            'image_path' => $imagePath
        ];
    }

    /**
     * Очищает имя файла, удаляя числа в скобках, текст "— копия", скобки и пробелы.
     *
     * Этот метод принимает строку, представляющую имя файла, и выполняет следующие действия:
     * - Удаляет числа в скобках, например, (1), (2) и т.д.
     * - Удаляет текст "— копия".
     * - Заменяет пробелы на дефисы.
     * - Удаляет круглые скобки.
     * - Преобразует оставшееся имя файла в URL-дружественный формат.
     *
     * @param string $fileName Имя файла для очистки.
     * @return string Очищенное и отформатированное имя файла.
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Удаляем числа в скобках, например (1) или (2) и т.д.
        $fileName = preg_replace('/\s*\(\d+\)/', '', $fileName);
        // Удаляем "— копия", скобки и пробелы
        $fileName = strtr($fileName, array('— копия' => '', ' ' => '-', '(' => '', ')' => ''));

        return str_slug($fileName, '-');
    }

    /**
     * Get the size of an image
     * получить размеры изображения (высота, ширина)
     *
     * $imagePath - 'img/flag.jpg'
     *
     * @param string $imagePath
     * @return array
     */
    private static function getImageSize(string $imagePath): array
    {
        //$img = Image::make($filePath);
        //
        //            $originalWidth = $img->width();
        //            $originalHeight = $img->height();

        $imageSize = self::imageSize($imagePath);

        if (is_array($imageSize)) {
            return [
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            ];
        }

        return [];
    }

    /**
     * @param array $image
     * @param string $userId
     * @param string $categoryId
     * @param bool $allowDuplicateChoice Если true и найден похожий дубликат — не отклоняем
     *        автоматически, а возвращаем 'duplicate_choice' (новая картинка лучше — предлагаем
     *        заменить старую) либо 'duplicate_reject_choice' (новая не лучше — предлагаем
     *        всё равно добавить, вдруг это ложное совпадение). См. PostController::addImage().
     *        Остальные вызывающие места такого выбора не показывают, поэтому у них
     *        (по умолчанию) любой найденный дубликат просто отклоняется — но в 'errors'-ответе
     *        всё равно есть 'staged_image'/'duplicate', чтобы вызывающий код мог сам предложить
     *        пользователю повторить с $forceAdd = true (см. PostController::createPost()).
     * @param bool $forceAdd Пользователь уже подтвердил, что это не дубликат (или что дубликат
     *        не страшен) — полностью пропускаем проверку на дубликат и сохраняем как обычно.
     * @return array|array[]|\bool[][]|string[]|\string[][]
     */
    public function saveImageForPost(array $image, string $userId, string $categoryId, bool $allowDuplicateChoice = false, bool $forceAdd = false): array
    {
        //TODO добавить сохранение в папку с сегоднешней датой
        // с  category parent_id ещё не понятно нужно ли это
        // если не пустой category parent_id:
        // / user_id / category parent_id / date today / category parent_id - category_id - image_title - image_size - расширение файла
        // если пустой category parent_id:
        // / user_id / 0 / date today / category parent_id - category_id - image_title - image_size - расширение файла
        //$categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: '0';

        $errors = [];

        /*
{
  ["image_name"]=>
  string(22) "pxl-20220517-092114902"
  ["image_extension"]=>
  string(3) "jpg"
  ["image_path"]=>
  string(43) "temp_directory/1/pxl-20220517-092114902.jpg"
  ["s_image_name"]=>
  string(34) "pxl-20220517-092114902_350_465.jpg"
  ["s_image_path"]=>
  string(51) "temp_directory/1/pxl-20220517-092114902_350_465.jpg"
  ["m_image_name"]=>
  string(35) "pxl-20220517-092114902_800_1063.jpg"
  ["m_image_path"]=>
  string(52) "temp_directory/1/pxl-20220517-092114902_800_1063.jpg"
}
         * */

        if (!empty($image) &&
            !empty($image['image_path']) &&
            !empty($image['image_name']) &&
            !empty($image['image_extension'])
        ) {
            $originalOldPath = '/' . $image['image_path'];
            // /temp_directory/1/faceapp-1684793403208.jpg

            // считаем хеш/размеры всегда (даже при forceAdd) — иначе принудительно
            // добавленная картинка сама не попадёт в image_hashes и не будет учитываться
            // при поиске дублей в будущем
            $originalAbsolutePath = Storage::disk('local')->path($originalOldPath);
            $newImageSize = file_exists($originalAbsolutePath) ? self::imageSize($originalAbsolutePath) : false;
            $newWidth = $newImageSize[0] ?? 0;
            $newHeight = $newImageSize[1] ?? 0;
            $phash = null;
            $perceptualHash = null;

            if ($newImageSize) {
                try {
                    $phash = self::computeImageHash($originalAbsolutePath);
                    $perceptualHash = self::computePerceptualHash($originalAbsolutePath);
                } catch (\Throwable $e) {
                    $phash = null;
                    $perceptualHash = null; // не смогли посчитать хеш — не блокируем загрузку из-за этого
                }
            }

            // === проверка на визуальный дубликат уже существующих изображений ===
            if (!$forceAdd) {
                if ($phash !== null && $perceptualHash !== null) {
                    $duplicate = self::findDuplicateByHash($phash, $perceptualHash, $newWidth, $newHeight, (int)$userId);

                    if ($duplicate !== null) {
                        $duplicateArea = $duplicate->width * $duplicate->height;
                        $newArea = $newWidth * $newHeight;
                        $newIsBigger = $newArea > $duplicateArea;

                        if (!$allowDuplicateChoice) {
                            // здесь показать выбор некому — просто отклоняем, но отдаём
                            // 'staged_image'/'duplicate', чтобы вызывающий код (например,
                            // PostController::createPost()) сам мог предложить повтор с forceAdd
                            return [
                                'errors' => [$newIsBigger
                                    ? 'Похожее изображение меньшего размера уже есть в посте #' . $duplicate->post_id . '.'
                                    : 'Такое изображение уже есть (пост #' . $duplicate->post_id . '), и оно того же размера или больше.'],
                                'duplicate' => $duplicate,
                                'staged_image' => $image,
                                'new_width' => $newWidth,
                                'new_height' => $newHeight,
                            ];
                        }

                        if ($newIsBigger) {
                            // новая картинка лучше — но решение, что делать со старой,
                            // оставляем пользователю (см. PostController::addImage())
                            return [
                                'duplicate_choice' => $duplicate,
                                'staged_image' => $image,
                                'new_width' => $newWidth,
                                'new_height' => $newHeight,
                            ];
                        }

                        // новая картинка не лучше — но вдруг это ложное совпадение (такое бывает,
                        // см. DUPLICATE_*_THRESHOLD): даём пользователю решить самому
                        return [
                            'duplicate_reject_choice' => $duplicate,
                            'staged_image' => $image,
                            'new_width' => $newWidth,
                            'new_height' => $newHeight,
                        ];
                    }
                }
            }
            // === конец проверки на дубликат ===

            // имена в постоянной папке: если там уже есть файл с таким именем (другая картинка
            // с тем же именем, загруженная сегодня), берём "<имя>-1", "<имя>-2"... — сразу для
            // всех размеров, чтобы original/medium/small остались одним комплектом.
            // Пути во временной папке ($image[...]) при этом не меняются.
            $targetNames = $this->resolveFreeTargetNames($image, $userId, $categoryId);

            //user_id / category parent_id / category parent_id - category_id - image_title - image_size - расширение файла
            $originalNewPath = self::generateImageNameAndPath(
                $targetNames['image_name'],
                $image['image_extension'],
                $userId,
                $categoryId,
            );
            //["image_name"]=> string(29) "0_0_faceapp-1684793403208.jpg"
            // ["image_path"]=> string(22) "/images/1/0/2024-06-01"

//            if (!empty($checkIfOriginalFileExists)) {
//                if (!empty($checkIfOriginalFileExists['new_image_path']) &&
//                    !empty($checkIfOriginalFileExists['image_name'])
//                ) {
//                    $originalNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                        . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
//                        . $checkIfOriginalFileExists['image_name'] . '.' . $image['extension'];
//
//                    $originalOldPath = $checkIfOriginalFileExists['new_image_path'];
//                    $image['image_name'] = $checkIfOriginalFileExists['image_name'];
//                } elseif (!empty($checkIfOriginalFileExists['error'])) {
//                    return [
//                        'errors' => $checkIfOriginalFileExists['error']
//                    ];
//                }
//            }

            $saveOriginalImage = $this->saveImage(
                $originalNewPath['image_name'],
                $image['image_extension'],
                $originalOldPath,
                $originalNewPath['image_path'] . '/' . $originalNewPath['image_name']
            );

            if (!empty($saveOriginalImage['error'])) {
                $errors[] = $saveOriginalImage['error'];
            }

            if (!empty($image['m_image_name'])) {
                // путь до временного файла — по аналогии с $smallOldPath ниже: обязательно с $userId,
                // иначе он не совпадает с тем, куда uploadImagesToTempDirectory реально его сохраняет
                $mediumOldPath = '/temp_directory/' . $userId . '/' . $image['m_image_name'] . '.' . $image['image_extension'];
                $mediumNewPath = self::generateImageNameAndPath(
                    $targetNames['m_image_name'],
                    $image['image_extension'],
                    $userId,
                    $categoryId,
                );

//                    '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                    . $categoryParentId . '/' . $categoryParentId . '_'
//                    . $categoryId . '_' . $image['m_image_name'] . '.' . $image['image_extension'];
//                $mediumOldPath = '/' . $image['m_image_path']; // '/temp_directory/' . $image['m_image_name'] . '.' . $image['image_extension'];

//                if (!empty($checkIfMediumFileExists)) {
//                    if (!empty($checkIfMediumFileExists['new_image_path'])
//                        && !empty($checkIfMediumFileExists['image_name'])
//                    ) {
//                        $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                            . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
//                            . $checkIfMediumFileExists['image_name'];
//
//                        $mediumOldPath = $checkIfMediumFileExists['new_image_path'];
//                        $image['m_image_name'] = $checkIfMediumFileExists['image_name'];
//                    } elseif (!empty($checkIfMediumFileExists['error'])) {
//                        return [
//                            'errors' => $checkIfMediumFileExists['error']
//                        ];
//                    }
//                }

                $saveMediumImage = $this->saveImage(
                    $mediumNewPath['image_name'],
                    $image['image_extension'],
                    $mediumOldPath,
                    $mediumNewPath['image_path'] . '/' . $mediumNewPath['image_name']
                );

                if (!empty($saveMediumImage['error'])) {
                    $errors[] = $saveMediumImage['error'];
                }
            }

            //OLD - if (!empty($image['small_name'])) {
            if (!empty($image['s_image_name'])) {
                $smallNewPath = self::generateImageNameAndPath(
                    $targetNames['s_image_name'],
                    $image['image_extension'],
                    $userId,
                    $categoryId,
                );
                $smallOldPath = '/temp_directory/' . $userId . '/' . $image['s_image_name']  . '.' . $image['image_extension'];

//                if (!empty($checkIfSmallFileExists)) {
//                    if (!empty($checkIfSmallFileExists['new_image_path'])
//                        && !empty($checkIfSmallFileExists['image_name'])
//                    ) {
//                        $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                            . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
//                            . $checkIfSmallFileExists['image_name'];
//
//                        $smallOldPath = $checkIfSmallFileExists['new_image_path'];
//                        $image['s_image_name'] = $checkIfSmallFileExists['image_name'];
//                    } elseif (!empty($checkIfSmallFileExists['error'])) {
//                        return [
//                            'errors' => $checkIfSmallFileExists['error']
//                        ];
//                    }
//                }

                $saveSmallImage = $this->saveImage(
                    $smallNewPath['image_name'],
                    $image['image_extension'],
                    $smallOldPath,
                    $smallNewPath['image_path'] . '/' . $smallNewPath['image_name']
                );

                if (!empty($saveSmallImage['error'])) {
                    $errors[] = $saveSmallImage['error'];
                }
            }

            if (!empty($errors)) {
                return [
                    'errors' => $errors
                ];
            }

            $imagesInfo = [
                'original_image' => $saveOriginalImage,
                // для сохранения записи в image_hashes после привязки картинки к посту
                // (post_id тут ещё может быть неизвестен — сохраняет уже вызывающий код)
                'phash' => $phash,
                'perceptual_hash' => $perceptualHash,
                'width' => $newWidth,
                'height' => $newHeight,
            ];

            if (!empty($saveMediumImage)) {
                $imagesInfo['medium_image'] = $saveMediumImage;
            }

            if (!empty($saveSmallImage)) {
                $imagesInfo['small_image'] = $saveSmallImage;
            }

        }

        //dd($imagesInfo ?? 'test');
        // [
        //  "original_image" => array:3 [
        //    "name" => "angelina-jolie-plastic-surgery-before-and-after-photo"
        //    "extension" => "jpg"
        //    "path" => "/images/1/0/0_0_angelina-jolie-plastic-surgery-before-and-after-photo.jpg"
        //  ]
        //]

        return $imagesInfo ?? [];
    }

    /**
     * Удалить у поста original_image, medium_image, small_image по имени изображения (ключ в JSON)
     *
     * @param $post
     * @param $imageName
     * @return array|bool
     */
    public function deletePostImages($post, $imageName = null)
    {
        //TODO переписать через try-catch и Exception
        $errors = [];

        // medium/small хранятся под своими именами (например, photo_800_600.jpg для
        // оригинала photo.jpg) — сравнивать с полным $imageName (с расширением) нельзя,
        // расширение "разрывает" совпадение; сравниваем по базовому имени без расширения
        $imageBaseName = !empty($imageName) ? pathinfo($imageName, PATHINFO_FILENAME) : null;

        try {
            if (!empty($post->original_image)) {
                $originalImage = json_decode($post->original_image, true);

                if (isset($originalImage['name'])) {
                    //var_dump('NO');
                    //TODO если одно изображение - старый вариант - потом нужно будет убрать
                } else {
                    //$image['name']
                    //dd('========');
                    foreach ($originalImage as $key => $image) {
                        //var_dump($key);
                        //var_dump($image);
                        if (!empty($imageName)) {
                            if ($key === $imageName) {
                                if (empty($image['path'])) {
                                    throw new \Exception('Ошибка при удалении original_image: пустой параметр path.');
                                }

                                $deleteImageByPath = $this->deleteImageByPath($image['path']);
                                if ($deleteImageByPath !== true) {
                                    //$errors[] = 'Ошибка при удалении original_image: ' . $post->original_image;
                                    throw new \Exception('Ошибка при удалении original_image: ' . $post->original_image);
                                }

                                unset($originalImage[$key]);
                            }
                        } else {
                            if (empty($image['path'])) {
                                throw new \Exception('Ошибка при удалении original_image: пустой параметр path.');
                            }

                            $deleteImageByPath = $this->deleteImageByPath($image['path']);
                            if ($deleteImageByPath !== true) {
                                //$errors[] = 'Ошибка при удалении original_image: ' . $post->original_image;
                                throw new \Exception('Ошибка при удалении original_image: ' . $post->original_image);
                            }

                            unset($originalImage[$key]);
                        }
                    }

                    //обновить пост в БД
                    $post->original_image = json_encode($originalImage);
                    $postSave = $post->save();
                    if ($postSave !== true) {
                        throw new \Exception('Ошибка при обновлении original_image в БД: ' . $originalImage);
                    }
                }
            }

            if (!empty($post->medium_image)) {
                $mediumImage = json_decode($post['medium_image'], true);
                if (isset($mediumImage['name'])) {
                    //TODO если одно изображение - старый вариант - потом нужно будет убрать
                } else {
                    foreach ($mediumImage as $key => $image) {
                        if (!empty($imageName)) {
                            if (strripos($key, $imageBaseName) !== false) {
                                if (empty($image['path'])) {
                                    throw new \Exception('Ошибка при удалении medium_image: пустой параметр path.');
                                }

                                $deleteImageByPath = (new UploadImageController)->deleteImageByPath($image['path']);
                                if ($deleteImageByPath !== true) {
                                    //$errors[] = 'Ошибка при удалении medium_image: ' . $post->medium_image;
                                    throw new \Exception('Ошибка при удалении medium_image: ' . $post->original_image);
                                }

                                unset($mediumImage[$key]);
                            }
                        } else {
                            if (empty($image['path'])) {
                                throw new \Exception('Ошибка при удалении medium_image: пустой параметр path.');
                            }

                            $deleteImageByPath = (new UploadImageController)->deleteImageByPath($image['path']);
                            if ($deleteImageByPath !== true) {
                                //$errors[] = 'Ошибка при удалении medium_image: ' . $post->medium_image;
                                throw new \Exception('Ошибка при удалении medium_image: ' . $post->original_image);
                            }

                            unset($mediumImage[$key]);
                        }
                    }

                    //обновить пост в БД
                    $post->medium_image = json_encode($mediumImage);
                    $postSave = $post->save();
                    if ($postSave !== true) {
                        throw new \Exception('Ошибка при обновлении medium_image в БД: ' . $mediumImage);
                    }
                }
            }

            if (!empty($post->small_image)) {
                $smallImage = json_decode($post['small_image'], true);
                if (isset($smallImage['name'])) {
                    //TODO если одно изображение - старый вариант - потом нужно будет убрать
                } else {
                    foreach ($smallImage as $key => $image) {
                        if (!empty($imageName)) {
                            if (strripos($key, $imageBaseName) !== false) {
                                if (empty($image['path'])) {
                                    throw new \Exception('Ошибка при удалении small_image: пустой параметр path.');
                                }

                                $deleteImageByPath = (new UploadImageController)->deleteImageByPath($image['path']);
                                if ($deleteImageByPath !== true) {
                                    //$errors[] = 'Ошибка при удалении small_image: ' . $post->small_image;
                                    throw new \Exception('Ошибка при удалении small_image: ' . $post->original_image);
                                }

                                unset($smallImage[$key]);
                            }
                        } else {
                            if (empty($image['path'])) {
                                throw new \Exception('Ошибка при удалении small_image: пустой параметр path.');
                            }

                            $deleteImageByPath = (new UploadImageController)->deleteImageByPath($image['path']);
                            if ($deleteImageByPath !== true) {
                                //$errors[] = 'Ошибка при удалении small_image: ' . $post->small_image;
                                throw new \Exception('Ошибка при удалении small_image: ' . $post->original_image);
                            }

                            unset($smallImage[$key]);
                        }
                    }
                }

                //обновить пост в БД
                $post->small_image = json_encode($smallImage);
                $postSave = $post->save();
                if ($postSave !== true) {
                    throw new \Exception('Ошибка при обновлении small_image в БД: ' . $smallImage);
                }
            }

            // копии оригиналов до правок (обрезка/поворот на странице редактирования) — больше не нужны
            if ($post instanceof Post && $post->id) {
                self::deleteImageBackups($post, $imageName ?: null);
            }

            return true;

        } catch (\Exception $exception) {
            //TODO - Добавить запись ошибок в логи в БД

            return [
                'errors' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Удалить изображение по пути
     *
     * @param $imagePath
     * @return bool
     */
    public function deleteImageByPath($imagePath): bool
    {
        //$categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: 0;

        //$image = json_decode($image, true);
        //$imagePath = $image['path'];

        if (Storage::exists($imagePath)) {
            //dd('TRUE');
            return Storage::delete($imagePath);
        }

        return false;

        //dd($imagePath);
        //{"name":"w700-51209445","extension":"jpg","path":"\/images\/1\/0\/0_7_w700-51209445.jpg"}
    }

    /**
     * original_image/medium_image/small_image у поста хранятся в двух форматах:
     * либо один объект {name, extension, path} (старый вариант, один файл на пост),
     * либо коллекция таких объектов, keyed по имени файла (несколько файлов на пост).
     * Приводит оба варианта к единому виду — коллекции, keyed по имени файла.
     *
     * @param string|null $json
     * @return array
     */
    public static function normalizeImages(?string $json): array
    {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (empty($decoded) || !is_array($decoded)) {
            return [];
        }

        if (isset($decoded['path']) && isset($decoded['name'])) {
            return [$decoded['name'] => $decoded];
        }

        return $decoded;
    }

    /**
     * Считает перцептивный хеш изображения (DifferenceHash) по абсолютному пути к файлу.
     *
     * @param string $absolutePath
     * @return string двоичная строка (0/1)
     */
    public static function computeImageHash(string $absolutePath): string
    {
        $hasher = new ImageHash();

        return $hasher->hash($absolutePath)->toBits();
    }

    /**
     * Считает второй, независимый хеш (PerceptualHash/DCT) того же изображения —
     * используется вместе с computeImageHash(), см. комментарий у DUPLICATE_*_THRESHOLD.
     *
     * @param string $absolutePath
     * @return string двоичная строка (0/1)
     */
    public static function computePerceptualHash(string $absolutePath): string
    {
        $hasher = new ImageHash(new PerceptualHash());

        return $hasher->hash($absolutePath)->toBits();
    }

    /**
     * Ищет среди уже сохранённых изображений (таблица image_hashes) визуальный дубликат.
     * Перебор в PHP — при текущих объёмах (тысячи, не миллионы изображений) это быстрее,
     * чем городить отдельную индексацию под Hamming distance.
     *
     * Дубликатом считаем только когда совпадают ОБА хеша и похожи пропорции кадра —
     * см. комментарий у DUPLICATE_*_THRESHOLD насчёт того, почему одного хеша мало.
     *
     * @param string $diffHash
     * @param string $perceptualHash
     * @param int $width
     * @param int $height
     * @param int $userId ищем только среди постов этого пользователя — чужие картинки
     *        не должны ни блокировать загрузку, ни показываться (номер чужого поста)
     * @return object|null объект {id, post_id, image_name, path, width, height, phash, perceptual_hash, distance} либо null
     */
    public static function findDuplicateByHash(string $diffHash, string $perceptualHash, int $width, int $height, int $userId)
    {
        $needleDiff = Hash::fromBits($diffHash);
        $needlePerceptual = Hash::fromBits($perceptualHash);
        $needleAspect = $height > 0 ? $width / $height : 0;

        $closest = null;
        $closestDistance = null;

        $candidates = DB::table('image_hashes')
            ->join('posts', 'posts.id', '=', 'image_hashes.post_id')
            ->where('posts.user_id', $userId)
            // картинки постов из корзины дубликатами не считаем: такой пост не открыть и не сравнить
            ->whereNull('posts.deleted_at')
            ->select('image_hashes.*')
            ->get();

        foreach ($candidates as $candidate) {
            // старые записи без второго хеша (до бэкфилла) в сравнении не участвуют —
            // одного хеша недостаточно, см. комментарий у констант
            if (empty($candidate->perceptual_hash) || empty($candidate->width) || empty($candidate->height)) {
                continue;
            }

            $candidateAspect = $candidate->width / $candidate->height;
            $aspectRatio = ($needleAspect > 0 && $candidateAspect > 0)
                ? max($needleAspect, $candidateAspect) / min($needleAspect, $candidateAspect)
                : 999;

            if ($aspectRatio > self::DUPLICATE_MAX_ASPECT_RATIO) {
                continue;
            }

            $diffDistance = $needleDiff->distance(Hash::fromBits($candidate->phash));
            if ($diffDistance > self::DUPLICATE_DIFF_HASH_THRESHOLD) {
                continue;
            }

            $perceptualDistance = $needlePerceptual->distance(Hash::fromBits($candidate->perceptual_hash));
            if ($perceptualDistance > self::DUPLICATE_PERCEPTUAL_HASH_THRESHOLD) {
                continue;
            }

            if ($closestDistance === null || $diffDistance < $closestDistance) {
                $closest = $candidate;
                $closestDistance = $diffDistance;
            }
        }

        if ($closest !== null) {
            $closest->distance = $closestDistance;
        }

        return $closest;
    }

    /**
     * Сохраняет хеш изображения после того, как оно окончательно привязано к посту.
     *
     * @param int $postId
     * @param string $imageName
     * @param string $path
     * @param int $width
     * @param int $height
     * @param string $phash
     * @param string|null $perceptualHash
     * @return void
     */
    public static function storeImageHash(int $postId, string $imageName, string $path, int $width, int $height, string $phash, ?string $perceptualHash = null): void
    {
        DB::table('image_hashes')->insert([
            'post_id' => $postId,
            'image_name' => $imageName,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'phash' => $phash,
            'perceptual_hash' => $perceptualHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Проверка загруженной во временную папку картинки на дубликат — без побочных эффектов
     * (файл остаётся на месте, в отличие от saveImageForPost(), который сразу переносит
     * картинку в постоянную папку). Нужна при создании поста, чтобы сначала спросить
     * пользователя обо всех совпадениях и только потом что-то сохранять.
     *
     * @param array $image элемент из images формы создания (image_name, image_path, ...)
     * @param int $userId
     * @return array|null ['duplicate' => object (см. findDuplicateByHash), 'width', 'height', 'file_size'] либо null
     */
    public static function findDuplicateForStagedImage(array $image, int $userId): ?array
    {
        if (empty($image['image_path'])) {
            return null;
        }

        $absolutePath = Storage::disk('local')->path('/' . $image['image_path']);
        $size = file_exists($absolutePath) ? self::imageSize($absolutePath) : false;
        if (!$size) {
            return null;
        }

        try {
            $phash = self::computeImageHash($absolutePath);
            $perceptualHash = self::computePerceptualHash($absolutePath);
        } catch (\Throwable $e) {
            return null; // не смогли посчитать хеш — как и в saveImageForPost(), не блокируем загрузку
        }

        $duplicate = self::findDuplicateByHash($phash, $perceptualHash, $size[0], $size[1], $userId);
        if ($duplicate === null) {
            return null;
        }

        return [
            'duplicate' => $duplicate,
            'width' => $size[0],
            'height' => $size[1],
            'file_size' => filesize($absolutePath) ?: 0,
        ];
    }

    /**
     * Заменяет (или просто удаляет, если $saved = null) одну картинку поста вместе со всеми её
     * размерами: файлы старой удаляются с диска, новая встаёт на её место в original/medium/small
     * (порядок важен — первая картинка это обложка), хеш в image_hashes переписывается.
     * В отличие от deletePostImages(), понимает и старый формат "одна картинка" ({name, path}).
     *
     * @param Post $post
     * @param string $oldName ключ картинки в original_image (он же image_name в image_hashes)
     * @param array|null $saved результат saveImageForPost() для новой картинки
     * @return void
     */
    public function swapPostImage(Post $post, string $oldName, ?array $saved): void
    {
        // medium/small лежат под ключами вида "<имя>_800_1063" / "<имя>_350_465.jpg"
        $baseName = pathinfo($oldName, PATHINFO_FILENAME);
        $isOldEntry = function ($key, $column) use ($oldName, $baseName) {
            if ($column === 'original_image') {
                return $key === $oldName;
            }
            return (bool)preg_match('/^' . preg_quote($baseName, '/') . '(_\d+_\d+)?(\.\w+)?$/u', $key);
        };

        foreach (['original_image', 'medium_image', 'small_image'] as $column) {
            $items = self::normalizeImages($post->getAttribute($column));
            $newEntry = $saved[$column] ?? null;

            $result = [];
            $inserted = false;
            foreach ($items as $key => $item) {
                if (!$isOldEntry((string)$key, $column)) {
                    $result[$key] = $item;
                    continue;
                }
                if (!empty($item['path'])) {
                    $this->deleteImageByPath($item['path']);
                }
                if ($newEntry && !$inserted) {
                    $result[$newEntry['name']] = $newEntry;
                    $inserted = true;
                }
            }
            if ($newEntry && !$inserted) {
                $result[$newEntry['name']] = $newEntry;
            }

            $post->setAttribute($column, empty($result) ? null : json_encode($result));
        }

        $post->save();

        self::deleteImageHash($post->id, $oldName);
        if (!empty($saved['phash'])) {
            self::storeImageHash(
                $post->id,
                $saved['original_image']['name'],
                $saved['original_image']['path'],
                $saved['width'],
                $saved['height'],
                $saved['phash'],
                $saved['perceptual_hash'] ?? null
            );
        }
    }

    /**
     * Удаляет запись о хеше изображения (при удалении изображения у поста).
     *
     * @param int $postId
     * @param string $imageName
     * @return void
     */
    public static function deleteImageHash(int $postId, string $imageName): void
    {
        DB::table('image_hashes')
            ->where('post_id', $postId)
            ->where('image_name', $imageName)
            ->delete();
    }

    /**
     * Пересчитывает хеши и размеры изображения после того, как его содержимое изменилось
     * (поворот/отражение на странице редактирования) — имя и путь те же.
     *
     * @param int $postId
     * @param string $imageName
     * @param string $absolutePath
     * @return void
     */
    public static function refreshImageHash(int $postId, string $imageName, string $absolutePath): void
    {
        try {
            $size = self::imageSize($absolutePath);
            if (!$size) {
                return;
            }

            DB::table('image_hashes')
                ->where('post_id', $postId)
                ->where('image_name', $imageName)
                ->update([
                    'width' => $size[0],
                    'height' => $size[1],
                    'phash' => self::computeImageHash($absolutePath),
                    'perceptual_hash' => self::computePerceptualHash($absolutePath),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            // не смогли посчитать хеш — как и при загрузке, на саму картинку это не влияет
        }
    }

    /**
     * Обновляет ссылку на изображение при переименовании — сам хеш не меняется,
     * это то же самое изображение, просто под новым именем/путём.
     *
     * @param int $postId
     * @param string $oldImageName
     * @param string $newImageName
     * @param string $newPath
     * @return void
     */
    public static function updateImageHashReference(int $postId, string $oldImageName, string $newImageName, string $newPath): void
    {
        DB::table('image_hashes')
            ->where('post_id', $postId)
            ->where('image_name', $oldImageName)
            ->update([
                'image_name' => $newImageName,
                'path' => $newPath,
                'updated_at' => now(),
            ]);
    }

}
