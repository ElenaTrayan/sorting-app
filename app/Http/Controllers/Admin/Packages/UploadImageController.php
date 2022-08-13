<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadImageController extends Controller
{
    const TEMP_IMAGE_PATH = 'app/public/temp_directory'; //temporary image path
    const IMAGE_PATH = '';

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

            if(request()->has('files')){
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
//                    exit();

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
     * @param int $width
     * @param int $height
     * @return \Illuminate\Http\JsonResponse|\Intervention\Image\Image
     */
    public function resizeImage($file, $filename, $extension, $imagePath, $width = 200, $height = 200)
    {
        try {
            $imageName = $filename . '_' . $width . '_' . $height . '.' . $extension;

            $img = Image::make($file->path());
            $imageSmall = $img->resize($width, $height, function ($const) {
                $const->aspectRatio();
            })->save($imagePath . '/' . $imageName);

            return $imageSmall;

        } catch (Throwable $e) {
            return response()->json(['status' => false, 'msg' => "Ошибка при удалении изображения"]);
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
