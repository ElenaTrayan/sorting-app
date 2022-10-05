<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadImageController extends Controller
{
    const TEMP_IMAGE_PATH = 'app/public/temp_directory'; //temporary image path
    const IMAGE_PATH = 'images/';

    public function uploadImageToTempDirectory(Request $request)
    {
        request()->validate([
            'files.*' => 'mimes:jpeg,png,jpg,gif,svg',
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

                $imageSmall = $this->resizeImage($file, $imageName, $path_info['extension'], storage_path(self::TEMP_IMAGE_PATH), true,120, 120);

                $imageMedium = $this->resizeImage($file, $imageName, $path_info['extension'], storage_path(self::TEMP_IMAGE_PATH), true,320, 320);

                $file = Storage::putFileAs(
                    'temp_directory',
                    $file,
                    $imageName . '.' . $path_info['extension']
                );

                $images[$imageName] = [
                    'name' => $imageName,
                    'small_name' => $imageSmall->basename,
                    'medium_name' => $imageMedium->basename,
                    'extension' => $path_info['extension'],
                    'original' => $file, // '/storage/' . $file,
                    'medium' => 'temp_directory' . '/' . $imageMedium->basename,
                    'small' => 'temp_directory' . '/' . $imageSmall->basename, // '/storage/temp_directory' . '/' . $imageSmall->basename,
                ];
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

}