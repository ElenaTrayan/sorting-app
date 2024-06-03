<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\PostsCategory;
use \Exception;

class UploadImageController extends Controller
{
    const TEMP_IMAGE_PATH = 'app/public/temp_directory/'; //temporary image path
    const IMAGE_PATH = 'images/';

    private const MAX_IMAGE_MEDIUM_WIDTH = '800';
    private const MAX_IMAGE_MEDIUM_HEIGHT = '800';
    private const MAX_IMAGE_SMALL_WIDTH = '350';
    private const MAX_IMAGE_SMALL_HEIGHT = '350';

    private array $errors = [];
    private int $userId;

    /* Error messages */
    private const EM_ERROR_LOADING_IMAGE = 'Ошибка при загрузке изображения.';
    private const EM_FILE_EXIST_IN_TEMP_DERICTORY = 'Невозможно завершить операцию. ' .
    'Файл с таким именем уже существует во временной папке. Пожалуйста, переименуйте текущий файл.';
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
            request()->validate([
                'files.*' => 'mimes:jpeg,png,jpg,gif,svg,webp',
            ]);

            if (!request()->has('files')) {
                throw new Exception(self::EM_NO_FILES_PROVIDED_FOR_UPLOAD);
            }

            $images = [];

            foreach (request()->file('files') as $file) {
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

                /* проверяем есть ли уже файл с таким именем в папке */
                /* exists возвращает true/false */
                if (Storage::disk('local')->exists($tempPathForImage . '/' . $imageNameWithExtension)) {
                    //get uploaded file size in bytes
                    $uploadedFileSize = filesize($file);

                    //get the size of an existing file in bytes
                    $existingFileSize = Storage::disk('local')->size(
                        $tempPathForImage . '/' . $imageNameWithExtension
                    );

                    if ($uploadedFileSize === $existingFileSize) {
                        throw new Exception(self::EM_FILE_EXIST_IN_TEMP_DERICTORY);
                    } else {
                        //TODO Возвращаем ответ на форму и показываем пользователю существующий файл с таким же именем
                        $imageName = $imageName . '-' . time();
                    }
                }

                //сохраняем оригинальное изображение во временную папку
                $path = Storage::putFileAs($tempPathForImage, $file, $imageNameWithExtension);

                if (empty($path)) {
                    throw new Exception(self::EM_ERROR_WHEN_SAVING_IMG_TO_TEMP_DIRECTORY);
                }

                $images[$imageName] = $this->saveSmallAndMediumImageSizesToTempDirectory(
                    $path,
                    $imageName,
                    $path_info['extension'],
                    $tempPathForImage  . '/'
                );
            }

            return response()->json(['status' => true, 'images' => $images]);

        } catch (Exception $exception) {
            //TODO - Переопределить Exception, как в Praxis
            //TODO - Добавить запись ошибок в логи в БД

//            var_dump('TEST Exception');
//
//            var_dump($exception->getTraceAsString());
//            exit();

            return response()->json(['status' => false, 'error' => $exception->getMessage()]);
        }
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
        $imageSize = getimagesize($imagePath);

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
            dd($exception->getMessage());

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
                $resizeImage->basename = $newName;
            }

            return $resizeImage;

        } catch (Throwable $e) {
            return response()->json(['status' => false, 'msg' => "Ошибка при изменении размера изображения"]);
        }
    }

    /**
     * удаление изображений из временной папки
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function deleteDownloadFile(Request $request)
    {
        try {
            $content = $request->getContent();
            $json = json_decode($content);

            if (!empty($json->name) && !empty($json->extension)) {
                $name = $json->name;
                $extension = $json->extension;
                $pattern = '/^temp_directory\/'. $name . '\.' . $extension . '$/';
                $pattern2 = '/^temp_directory\/'. $name . '+\_[0-9]{2,6}+\_[0-9]{2,6}+\.' . $extension . '$/';

                //получаем все файлы из папки temp_directory
                $files = Storage::allFiles('temp_directory');

                $images = 0;
                $deletedImages = 0;
                foreach ($files as $file) {
                    if (preg_match($pattern, $file) || preg_match($pattern2, $file)) {
                        //var_dump('Проверка пройдена успешно!');
                        $images += 1;

                        if (Storage::delete($file) === true) {
                            $deletedImages += 1;
                            //var_dump('Изображение удалено');
                        } else {
                            //var_dump('Ошибка при удалении изображения');
                        }

                    }
//                    else {
//                        var_dump('Проверка не пройдена!');
//                    }
                }

                if ($images === $deletedImages) {
                    return response()->json(['status' => true, 'msg' => "Изображение успешно удалено"]);
                } else {
                    return response()->json(['status' => false, 'msg' => "Ошибка при удалении изображения"]);
                }

            }

        } catch (Throwable $e) {
            return response()->json(['status' => false, 'msg' => "Ошибка при удалении изображения"]);
        }
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

        if (Storage::move($oldPath, $newPath) === true) {
            return true;
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

        if ($moveImage) {
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
     * @param $imageName
     * @param $imageExtension
     * @param $originalOldPath
     * @param $originalNewPath
     * @return false|string[]
     */
    private function checkIfFileExists($imageName, $imageExtension, $originalOldPath, $originalNewPath)
    {
        if (Storage::disk('local')->exists($originalNewPath)) {
            $existsFileSize = Storage::size($originalNewPath);
            $fileSize = Storage::size($originalOldPath);
            var_dump($existsFileSize);
            dd($fileSize);

            if ($existsFileSize === $fileSize) {
                return [
                    'error' => 'Файл уже существует!'
                ];
            } else {
                //добавляем к имени файла время Unix
                $imageName =  time() . '_'. $imageName;
                $newImagePath = '/temp_directory/' . $imageName . '.' . $imageExtension;

                $moveImage = $this->moveImage($originalOldPath, $newImagePath);

                if ($moveImage) {
                    return [
                        'image_name' => $imageName,
                        'new_image_path' => $newImagePath
                    ];
                } elseif (is_string($moveImage)) {
                    return [
                        'error' => $moveImage
                    ];
                }
            }
        }

        return false;
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

        $imageSize = getimagesize($imagePath);

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
     * @return array|array[]|\bool[][]|string[]|\string[][]
     */
    public function saveImageForPost(array $image, string $userId, string $categoryId): array
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

            //user_id / category parent_id / category parent_id - category_id - image_title - image_size - расширение файла
            $originalNewPath = self::generateImageNameAndPath(
                $image['image_name'],
                $image['image_extension'],
                $userId,
                $categoryId,
            );
            //["image_name"]=> string(29) "0_0_faceapp-1684793403208.jpg"
            // ["image_path"]=> string(22) "/images/1/0/2024-06-01"

            //var_dump($originalOldPath);
            //var_dump($originalNewPath['image_path'] . '/' . $originalNewPath['image_name']);

            //проверяем есть ли уже файл с таким именем в папке
            $checkIfOriginalFileExists = $this->checkIfFileExists(
                $originalNewPath['image_name'],
                $image['image_extension'],
                $originalOldPath,
                $originalNewPath['image_path'] . '/' . $originalNewPath['image_name']
            );

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
                $mediumOldPath = '/temp_directory/' . $image['m_image_name'] . '.' . $image['image_extension'];
                $mediumNewPath = self::generateImageNameAndPath(
                    $image['m_image_name'],
                    $image['image_extension'],
                    $userId,
                    $categoryId,
                );

                var_dump($mediumOldPath);
                dd($mediumNewPath);

//                    '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                    . $categoryParentId . '/' . $categoryParentId . '_'
//                    . $categoryId . '_' . $image['m_image_name'] . '.' . $image['image_extension'];
//                $mediumOldPath = '/' . $image['m_image_path']; // '/temp_directory/' . $image['m_image_name'] . '.' . $image['image_extension'];

                $checkIfMediumFileExists = $this->checkIfFileExists(
                    $mediumNewPath['image_name'],
                    $image['image_extension'],
                    $mediumOldPath,
                    $mediumNewPath['image_path'] . '/' . $mediumNewPath['image_name']
                );

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
                    $image['s_image_name'],
                    $image['image_extension'],
                    $userId,
                    $categoryId,
                );
                $smallOldPath = '/temp_directory/' . $userId . '/' . $image['s_image_name']  . '.' . $image['image_extension'];

                $checkIfSmallFileExists = $this->checkIfFileExists(
                    $smallNewPath['image_name'],
                    $image['image_extension'],
                    $smallOldPath,
                    $smallNewPath['image_path'] . '/' . $smallNewPath['image_name']
                );

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
                            if (strripos($key, $imageName) !== false) {
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
                            if (strripos($key, $imageName) !== false) {
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

}
