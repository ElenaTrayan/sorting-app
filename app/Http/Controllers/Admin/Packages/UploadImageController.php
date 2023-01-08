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

    private $errors = [];

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

                if ($imageSize[0] >= 320 && $imageSize[1] >= 320) {
                    $imageSmall = $this->resizeImage($file, $imageName, $path_info['extension'], storage_path(self::TEMP_IMAGE_PATH), true,320, 320);
                    $small_name = $imageSmall->basename;
                } else {

                }

//                return response()->json(['status' => true, 'image' => 'test']);

                if ($imageSize[0] >= 700 || $imageSize[1] >= 700) {
                    $imageMedium = $this->resizeImage($file, $imageName, $path_info['extension'], storage_path(self::TEMP_IMAGE_PATH), false,700, 700);
                    $medium_name = $imageMedium->basename;
                }

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
                    $images[$imageName]['medium'] ='temp_directory' . '/' . $medium_name;
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
     * @param Request $request
     * @return mixed
     */
    public function upload(Request $request)
    {
        //DevHelpersContoller::writeLogToFile('TEST');
        try {
            request()->validate([
                'files.*' => 'mimes:jpeg,png,jpg,gif,svg',
            ]);

            $images = [];

            if(request()->has('files')) {
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
                    exit();

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
    public function resizeImage($file, $filename, $extension, $imagePath, $isQuadratic = false, $width = 200, $height = 200)
    {
        try {
            $imageName = $filename . '_' . $width . '_' . $height . '.' . $extension;

            $img = Image::make($file->path());

            $originalWidth  = $img->width();
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

                $resizeImage = $img->save($imagePath . '/' . $imageName);
            } else {
                $originalHeight > $originalWidth ? $width=null : $height=null;

                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $resizeImage = $img->save($imagePath . '/' . $imageName);
            }

            return $resizeImage;

        } catch (Throwable $e) {
            return response()->json(['status' => false, 'msg' => "Ошибка при изменении размера изображения"]);
        }
    }

    public function deleteDownloadFile(Request $request)
    {
        try {
            return response()->json(['Files' => 'TEST']);
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
    private function moveImage(string $oldPath, string $newPath)
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
     * @param $image
     * @param $userId
     * @param $categoryId
     * @return array|bool
     */
    public function saveImageForPost($image, $userId, $categoryId)
    {
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId) ?: 0;

        $errors = [];

        //user_id / category parent_id / category parent_id - category_id - image_title - image_size - расширение файла
        $originalNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['name'] . '.' . $image['extension'];
        $originalOldPath = '/temp_directory/' . $image['name'] . '.' . $image['extension'];

        $saveOriginalImage = $this->saveImage($image['name'], $image['extension'], $originalOldPath, $originalNewPath);
        if (!empty($saveOriginalImage['error'])) {
            $errors[] = $saveOriginalImage['error'];
        }

        if (!empty($image['medium_name'])) {
            $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['medium_name'];
            $mediumOldPath = '/temp_directory/' . $image['medium_name'];

            $saveMediumImage = $this->saveImage($image['medium_name'], $image['extension'], $mediumOldPath, $mediumNewPath);
            if (!empty($saveMediumImage['error'])) {
                $errors[] = $saveMediumImage['error'];
            }
        }

        if (!empty($image['small_name'])) {
            $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $userId . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['small_name'];
            $smallOldPath = '/temp_directory/' . $image['small_name'];

            $saveSmallImage = $this->saveImage($image['small_name'], $image['extension'], $smallOldPath, $smallNewPath);
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

        //dd($imagePath);
        //{"name":"w700-51209445","extension":"jpg","path":"\/images\/1\/0\/0_7_w700-51209445.jpg"}
        $errors = [];

        return false;
    }

}
