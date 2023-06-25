<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\PostsCategory;

class UploadImageController extends Controller
{
    const TEMP_IMAGE_PATH = 'app/public/temp_directory'; //temporary image path
    const IMAGE_PATH = 'images/';

    private const MAX_IMAGE_MEDIUM_WIDTH = '800';
    private const MAX_IMAGE_MEDIUM_HEIGHT = '800';
    private const MAX_IMAGE_SMALL_WIDTH = '350';
    private const MAX_IMAGE_SMALL_HEIGHT = '350';

    private $errors = [];

    //получить размер изображения - Storage::size($path);

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImageToTempDirectory(Request $request)
    {
        request()->validate([
            'files.*' => 'mimes:jpeg,png,jpg,gif,svg,webp',
        ]);

        $images = [];

        if (request()->has('files')) {
            foreach (request()->file('files') as $file) {
                $originalImageName = $file->getClientOriginalName();

                $path_info = pathinfo($originalImageName);

//                if (!File::exists($path_info)) {
//                    File::makeDirectory($path_info);
//                }

                if (empty($path_info['filename']) || empty($path_info['extension'])) {
                    return response()->json(['status' => false, 'error' => "Ошибка при загрузке изображения"]);
                }

                //удаляем из названия изображения скобки и пробелы
                $imageName = strtr($path_info['filename'], array('(' => '', ')' => '', ' ' => ''));
                $imageName = str_slug($imageName);

                $imageSize = getimagesize($file);
                //$imageSize[0] - ширина, $imageSize[1] - высота

                if ($imageSize[0] >= self::MAX_IMAGE_SMALL_WIDTH && $imageSize[1] >= self::MAX_IMAGE_SMALL_HEIGHT) {
                    $imageSmall = $this->resizeImage(
                        $file,
                        $imageName,
                        $path_info['extension'],
                        storage_path(self::TEMP_IMAGE_PATH),
                        false,
                        self::MAX_IMAGE_SMALL_WIDTH,
                        self::MAX_IMAGE_SMALL_HEIGHT
                    );
                    $small_name = $imageSmall->basename;
                }

//                return response()->json(['status' => true, 'image' => 'test']);

                if ($imageSize[0] >= self::MAX_IMAGE_MEDIUM_WIDTH || $imageSize[1] >= self::MAX_IMAGE_MEDIUM_HEIGHT) {
                    $imageMedium = $this->resizeImage(
                        $file,
                        $imageName,
                        $path_info['extension'],
                        storage_path(self::TEMP_IMAGE_PATH),
                        false,
                        self::MAX_IMAGE_MEDIUM_WIDTH,
                        self::MAX_IMAGE_MEDIUM_HEIGHT
                    );
                    $medium_name = $imageMedium->basename;
                }

                //проверяем есть ли уже файл с таким именем в папке
//                if (Storage::disk('local')->exists($imageName . '.' . $path_info['extension'])) {
//                    dd('eeeee');
//                }

                $file = Storage::putFileAs(
                    'temp_directory',
                    $file,
                    $imageName . '.' . $path_info['extension']
                );

                $images[$imageName] = [
                    'name' => $imageName,
                    'extension' => $path_info['extension'],
                    'original' => $file, // '/storage/' . $file,
                ];

                if (!empty($small_name)) {
                    $images[$imageName]['small_name'] = $small_name;
                    $images[$imageName]['small'] = 'temp_directory' . '/' . $small_name; // '/storage/temp_directory' . '/' . $imageSmall->basename,
                }

                if (!empty($medium_name)) {
                    $images[$imageName]['medium_name'] = $medium_name;
                    $images[$imageName]['medium'] = 'temp_directory' . '/' . $medium_name;
                }

//                extension:"png"
//                medium:"temp_directory/logo_700_700.png"
//                medium_name:"logo_700_700.png"
//                name:"logo"
//                original:"temp_directory/logo.png"
//                small:"temp_directory/logo_320_320.png"
//                small_name:"logo_320_320.png"
            }
        }

        return response()->json(['status' => true, 'images' => $images]);
    }

    /**
     * @param string $imagePath
     * @param string $imageName
     * @param string $imageExtension
     * @param array $imageSize
     * @return array|array[]
     */
    public function saveSmallImageToTempDirectory(
        string $imagePath,
        string $imageName,
        string $imageExtension,
        array $imageSize
    ): array
    {
        if ($imageSize[0] >= self::MAX_IMAGE_SMALL_WIDTH && $imageSize[1] >= self::MAX_IMAGE_SMALL_HEIGHT) {
            $imageSmall = $this->resizeImage(
                $imagePath,
                $imageName,
                $imageExtension,
                storage_path(self::TEMP_IMAGE_PATH),
                false,
                self::MAX_IMAGE_SMALL_WIDTH,
                self::MAX_IMAGE_SMALL_HEIGHT
            );

            $smallImageName = $imageSmall->basename;

            if (!empty($smallImageName)) {
                //s_image_name
                //s_image_path
                return [
                    's_image_name' => $smallImageName,
                    's_image_path' => 'temp_directory' . '/' . $smallImageName,
                ];
            }
        }

        return [];
    }

    /**
     * @param string $imagePath
     * @param string $imageName
     * @param string $imageExtension
     * @param array $imageSize
     * @return array|array[]
     */
    public function saveMediumImageToTempDirectory(
        string $imagePath,
        string $imageName,
        string $imageExtension,
        array $imageSize
    ): array
    {
        if ($imageSize[0] >= self::MAX_IMAGE_MEDIUM_WIDTH && $imageSize[1] >= self::MAX_IMAGE_MEDIUM_HEIGHT) {
            $imageMedium = $this->resizeImage(
                $imagePath,
                $imageName,
                $imageExtension,
                storage_path(self::TEMP_IMAGE_PATH),
                false,
                self::MAX_IMAGE_MEDIUM_WIDTH,
                self::MAX_IMAGE_MEDIUM_HEIGHT
            );

            $mediumImageName = $imageMedium->basename;

            if (!empty($mediumImageName)) {
                return [
                    'm_image_name' => $mediumImageName,
                    'm_image_path' => 'temp_directory' . '/' . $mediumImageName,
                ];
            }
        }

        return [];
    }

    public function saveParseImageToTempDirectory(string $imagePath, string $imageName, string $imageExtension)
    {
        $imageSize = getimagesize($imagePath);
        //$imageSize[0] - ширина, $imageSize[1] - высота

        $smallImage = $this->saveSmallImageToTempDirectory(
            $imagePath,
            $imageName,
            $imageExtension,
            $imageSize
        );

        $mediumImage = $this->saveMediumImageToTempDirectory(
            $imagePath,
            $imageName,
            $imageExtension,
            $imageSize
        );

        $originalImage = [
            'image_name' => $imageName,
            'image_extension' => $imageExtension,
            'image_path' => $imagePath,
        ];

        $images[$imageName] = array_merge($originalImage, $mediumImage, $smallImage);

        return $images;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function upload(Request $request)
    {
        //DevHelpersContoller::writeLogToFile('TEST');
        try {
            request()->validate([
                'files.*' => 'mimes:jpeg,png,jpg,gif,svg,webp',
            ]);

            $images = [];

            if (request()->has('files')) {
                foreach (request()->file('files') as $file) {
                    //"originalName": "246021903_272829371422961_6110518103173326510_n.jpg"
                    //"mimeType":"image/jpeg"
                    $originalImageName = $file->getClientOriginalName();

                    $path_info = pathinfo($originalImageName);
                    if (empty($path_info['filename']) || empty($path_info['extension'])) {
                        return response()->json(['status' => false, 'msg' => "Ошибка при загрузке изображения"]);
                    }
                    //array(4) {
                    // ["dirname"]=> string(1) "."
                    // ["basename"]=> string(51) "246021903_272829371422961_6110518103173326510_n.jpg"
                    // ["extension"]=> string(3) "jpg"
                    // ["filename"]=> string(47) "246021903_272829371422961_6110518103173326510_n"
                    // }

                    $imageSmall = $this->resizeImage($file, $path_info['filename'], $path_info['extension'], storage_path(self::TEMP_IMAGE_PATH), 120, 120);
//                    var_dump($filePath);
//                    var_dump($imageSmall->basename);
//                    var_dump($filePath . '/' . $imageSmall->basename);
                    //exit();

//                    $filePath = public_path('/images');
//                    $image->move($filePath, $input['imagename']);

//                    $file = Storage::disk('public')->putFileAs(
//                        'temp_directory',
//                        $file,
//                        $originalFilename
//                    );

                    //"Impossible to create the root directory \"F:\\Webprojects\\sorting\\storage\\app/public\\F:/Webprojects/sorting/storage/app/public/temp_directory\". "
                    $file = Storage::putFileAs(
                        'temp_directory',
                        $file,
                        $originalImageName
                    );

                    //http://sorting.local/storage/temp_directory/GettyImages-527424712-696x952.jpg

                    //$images[] = '/storage/' . $file;

                    //[
                    //  " /storage/temp_directory/120_120TzweP9ZVtWDTfctUWw31yw-default.jpg"
                    //]

                    $images[] = [
                        'original' => '/storage/' . $file,
                        'small' => '/storage/temp_directory' . '/' . $imageSmall->basename,
                        'name' => $path_info['filename'],
                        'extension' => $path_info['extension'],
                    ];
                }
            }


//            $uploadedFile = $request->file('userfile');
//
//            $filename = $uploadedFile->getClientOriginalName();
//
//            Storage::disk('local')->putFileAs(
//                'temp_directory/',
//                $uploadedFile,
//                $filename
//            );

            return response()->json(['Files' => $images]);
            //return response()->json(['method' => 'TEST']);
            //return response()->json(['status' => true, 'msg' => "Загрузка прошла успешно"]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'msg' => "Ошибка при загрузке изображения"]);
        }


    }

    /**
     * @param $file
     * @param $filename
     * @param $extension
     * @param $imagePath
     * @param bool $isQuadratic
     * @param int $width
     * @param int $height
     * @return \Illuminate\Http\JsonResponse|\Intervention\Image\Image
     */
    public function resizeImage($file, $filename, $extension, $imagePath, bool $isQuadratic = false, int $width = 200, int $height = 200)
    {
        try {
            $imageName = $filename . '_' . $width . '_' . $height . '.' . $extension;
            $w_h = $width . '_' . $height;

            $filePath = is_array($file) ? $file->path() : $file;
            $img = Image::make($filePath);

            $originalWidth = $img->width();
            $originalHeight = $img->height();

            if ($isQuadratic === true) {
                if ($originalHeight > $originalWidth) {
                    $height = null;
                    $cropSize = $width;
                } elseif ($originalWidth > $originalHeight) {
                    $width = null;
                    $cropSize = $height;
                } else {
                    $cropSize = $height;
                }

                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->crop($cropSize, $cropSize);

                $imageSize2 = getimagesize($img);

                //var_dump('image SIZE');
//                var_dump($imageSize2);
//                exit();

                $resizeImage = $img->save($imagePath . '/' . $imageName);
            } else {

                if ($width < 451 || $height > 451) {
                    if ($originalHeight > $originalWidth) {
                        $height = null;
                    } else {
                        $width = null;
                    }
                } else {
                    $originalHeight > $originalWidth ? $width = null : $height = null;
                }

                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $resizeImage = $img->save($imagePath . '/' . $imageName);
//                var_dump($resizeImage);
//                var_dump($imagePath);
//                var_dump($imageName);
//                var_dump($resizeImage->dirname . '/' . $resizeImage->basename);
                //exit();

//                if ($this->moveImage('/temp_directory/' . $imageName, '/temp_directory/' . '/' . $newName)) {
//                    $resizeImage->basename = $newName;
//                }
            }

            //var_dump($resizeImage);

            $imageSize2 = getimagesize($imagePath . '/' . $imageName);
            $newName = str_replace($w_h, $imageSize2[0] . '_' . $imageSize2[1], $imageName);
//                var_dump($width . '_' . $height);
//                var_dump($imageSize2[0] . '_' . $imageSize2[1]);
//                var_dump($newName);
//                $path = Storage::path($imageName);
//                var_dump($path);

            //var_dump($resizeImage->dirname . '/' . $resizeImage->basename);
            //var_dump(file_exists('D:\Webprojects\sorting\storage\app/public/temp_directory/4d21abef01a086c36f71c7d2f3a044d4_350_350.jpg'));
            //var_dump(Storage::disk('local')->exists('/temp_directory/' . $resizeImage->basename));

            if (rename($imagePath . '/' . $imageName, $imagePath . '/' . $newName)) {
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

//    public function renameFile()
//    {
//
//    }

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

        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: $categoryId;

        if (isset($imageSize) && is_array($imageSize)) {
            // "7_6_a17ac3f0262325f5c3bc30cb34fb9350_800_1337.jpg"
            $imageName = $categoryParentId . '_' . $categoryId . '_' . $imageName . '_' . $imageSize['width'] . '_' . $imageSize['height'] . '.' . $imageExtension;
        } else {
            // "7_6_a17ac3f0262325f5c3bc30cb34fb9350.jpg"
            $imageName = $categoryParentId . '_' . $categoryId . '_' . $imageName . '.' . $imageExtension;
        }

        // "/images/1/7/image_name.jpg"
        $imagePath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryParentId . '/' . $imageName;

        return [
            'image_name' => $imageName,
            'image_path' => $imagePath
        ];
    }

    /**
     * Get the size of an image
     * получить размеры изображения (высота, ширина)
     *
     * $imagePath - 'img/flag.jpg'
     *
     * @param string $imagePath
     * @return array|false
     */
    private static function getImageSize(string $imagePath)
    {
        $imageSize = getimagesize($imagePath);

        if (is_array($imageSize)) {
            return [
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            ];
        }

        return false;
    }

    /**
     * @param $image
     * @param $userId
     * @param $categoryId
     * @return array[]|\bool[][]|string[]|\string[][]
     */
    public function saveImageForPost($image, $userId, $categoryId)
    {
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: $categoryId;

        $errors = [];

        //OLD - $originalOldPath = '/temp_directory/' . $image['name'] . '.' . $image['extension'];
        $originalOldPath = '/temp_directory/' . $image['image_name'] . '.' . $image['image_extension'];

        //user_id / category parent_id / category parent_id - category_id - image_title - image_size - расширение файла
//        OLD - $originalNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//            . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
//            . $image['name'] . '.' . $image['extension'];
        $originalNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
            . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
            . $image['image_name'] . '.' . $image['image_extension'];

//        OLD - $originalNewPath = self::generateImageNameAndPath(
//            $image['name'],
//            $image['extension'],
//            $userId,
//            $categoryId,
//        );
        $originalNewPath = self::generateImageNameAndPath(
            $image['image_name'],
            $image['image_extension'],
            $userId,
            $categoryId,
        );

        //проверяем есть ли уже файл с таким именем в папке
        //OLD - $checkIfOriginalFileExists = $this->checkIfFileExists($image['name'], $image['extension'], $originalOldPath, $originalNewPath['image_path']);
        $checkIfOriginalFileExists = $this->checkIfFileExists($image['image_name'], $image['image_extension'], $originalOldPath, $originalNewPath['image_path']);

        if (!empty($checkIfOriginalFileExists)) {
            if (!empty($checkIfOriginalFileExists['new_image_path']) && !empty($checkIfOriginalFileExists['image_name'])) {
                $originalNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
                    . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
                    . $checkIfOriginalFileExists['image_name'] . '.' . $image['extension'];

                $originalOldPath = $checkIfOriginalFileExists['new_image_path'];
                $image['name'] = $checkIfOriginalFileExists['image_name'];
            } elseif (!empty($checkIfOriginalFileExists['error'])) {
                return [
                    'errors' => $checkIfOriginalFileExists['error']
                ];
            }
        }

        //OLD - $saveOriginalImage = $this->saveImage($image['name'], $image['extension'], $originalOldPath, $originalNewPath);
        $saveOriginalImage = $this->saveImage($image['image_name'], $image['image_extension'], $originalOldPath, $originalNewPath['image_path']);
        if (!empty($saveOriginalImage['error'])) {
            $errors[] = $saveOriginalImage['error'];
        }

        //OLD - if (!empty($image['medium_name'])) {
        if (!empty($image['m_image_name'])) {
//            OLD - $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                . $categoryParentId . '/' . $categoryParentId . '_'
//                . $categoryId . '_' . $image['medium_name'];
//            $mediumOldPath = '/temp_directory/' . $image['medium_name'];
            $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
                . $categoryParentId . '/' . $categoryParentId . '_'
                . $categoryId . '_' . $image['m_image_name'] . '.' . $image['image_extension'];
            $mediumOldPath = '/temp_directory/' . $image['m_image_name'] . '.' . $image['image_extension'];

            //OLD - $checkIfMediumFileExists = $this->checkIfFileExists($image['medium_name'], $image['extension'], $mediumOldPath, $mediumNewPath);
            $checkIfMediumFileExists = $this->checkIfFileExists($image['m_image_name'], $image['image_extension'], $mediumOldPath, $mediumNewPath);
            if (!empty($checkIfMediumFileExists)) {
                if (!empty($checkIfMediumFileExists['new_image_path']) && !empty($checkIfMediumFileExists['image_name'])) {
                    $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
                        . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
                        . $checkIfMediumFileExists['image_name'];

                    $mediumOldPath = $checkIfMediumFileExists['new_image_path'];
                    $image['name'] = $checkIfMediumFileExists['image_name'];
                } elseif (!empty($checkIfMediumFileExists['error'])) {
                    return [
                        'errors' => $checkIfMediumFileExists['error']
                    ];
                }
            }

            //OLD - $saveMediumImage = $this->saveImage($image['medium_name'], $image['extension'], $mediumOldPath, $mediumNewPath);
            $saveMediumImage = $this->saveImage($image['m_image_name'], $image['image_extension'], $mediumOldPath, $mediumNewPath);
            if (!empty($saveMediumImage['error'])) {
                $errors[] = $saveMediumImage['error'];
            }
        }

        //OLD - if (!empty($image['small_name'])) {
        if (!empty($image['s_image_name'])) {
//            OLD - $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
//                . $categoryParentId . '/' . $categoryParentId . '_'
//                . $categoryId . '_' . $image['small_name'];
//            $smallOldPath = '/temp_directory/' . $image['small_name'];
            $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
                . $categoryParentId . '/' . $categoryParentId . '_'
                . $categoryId . '_' . $image['s_image_name'];
            $smallOldPath = '/temp_directory/' . $image['s_image_name'];

            //OLD - $checkIfSmallFileExists = $this->checkIfFileExists($image['small_name'], $image['extension'], $smallOldPath, $smallNewPath);
            $checkIfSmallFileExists = $this->checkIfFileExists($image['s_image_name'], $image['image_extension'], $smallOldPath, $smallNewPath);
            if (!empty($checkIfSmallFileExists)) {
                if (!empty($checkIfSmallFileExists['new_image_path']) && !empty($checkIfSmallFileExists['image_name'])) {
                    $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/'
                        . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_'
                        . $checkIfSmallFileExists['image_name'];

                    $smallOldPath = $checkIfSmallFileExists['new_image_path'];
                    $image['name'] = $checkIfSmallFileExists['image_name'];
                } elseif (!empty($checkIfSmallFileExists['error'])) {
                    return [
                        'errors' => $checkIfSmallFileExists['error']
                    ];
                }
            }

            //OLD - $saveSmallImage = $this->saveImage($image['small_name'], $image['extension'], $smallOldPath, $smallNewPath);
            $saveSmallImage = $this->saveImage($image['s_image_name'], $image['image_extension'], $smallOldPath, $smallNewPath);
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

        return $imagesInfo;
    }

    public function deleteImageFromPost($image, $userId, $categoryId)
    {
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: 0;

        $image = json_decode($image, true);
        $imagePath = $image['path'];

        if (Storage::exists($imagePath)) {
            //dd('TRUE');
            return Storage::delete($imagePath);
        }

        return false;

        //dd($imagePath);
        //{"name":"w700-51209445","extension":"jpg","path":"\/images\/1\/0\/0_7_w700-51209445.jpg"}
    }

}
