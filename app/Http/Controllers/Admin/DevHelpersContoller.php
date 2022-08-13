<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class DevHelpersContoller extends Controller
{
    private const FILE_PATH = '../storage/app/public/files/logs.txt';

    //asset('storage/files/' . 'log.txt');

    /**
     * @param $value
     * @param string $filePath
     * @return bool|int
     */
    static function writeLogToFile($value, $filePath = self::FILE_PATH)
    {
        //echo asset('storage/file.txt');

//        $filename = 'F:\Webprojects\sorting\storage\app\public\images\145566930_112457234156843_544307427863653309_n.jpg';
//        $filename = '../storage/app/public/images/145566930_112457234156843_544307427863653309_n.jpg';
//        //$filename = '\sorting\storage\app\public\images\145566930_112457234156843_544307427863653309_n.jpg';
//
//        if (file_exists($filename)) {
//            echo "Файл $filename существует";
//        } else {
//            echo "Файл $filename не существует";
//        }
//
//        exit();

//        Storage::disk('local')->put('example.txt', 'Contents');

        $log = date('Y-m-d H:i:s') . ' ' . print_r($value, true);
        file_put_contents($filePath, $log . PHP_EOL, FILE_APPEND);

        return true;
    }
}
