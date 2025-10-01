<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;



Route::get('/storage/stories/{filename}', function ($filename) {
    $path = storage_path('app/public/stories/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    $fileSize = filesize($path);
    $file = fopen($path, 'rb');
    
    // Determine MIME type dynamically
    $mime = mime_content_type($path) ?: 'video/mp4';

    $headers = [
        'Content-Type' => $mime,
        'Content-Length' => $fileSize,
        'Accept-Ranges' => 'bytes',
        'Content-Disposition' => 'inline',
    ];

    // Handle range requests (for streaming)
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        
        // Handle single range requests
        $rangeParts = explode('-', $range);
        $start = intval($rangeParts[0]);
        $end = isset($rangeParts[1]) && $rangeParts[1] !== '' 
             ? intval($rangeParts[1]) 
             : $fileSize - 1;

        // Validate range
        if ($start < 0 || $start > $end || $end >= $fileSize) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes */$fileSize");
            fclose($file);
            exit;
        }

        $length = $end - $start + 1;

        fseek($file, $start);
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Content-Length: $length");

        // Output the correct chunk of the file
        $buffer = 8192; // 8KB buffer
        while (!feof($file) && $length > 0) {
            $readLength = min($buffer, $length);
            echo fread($file, $readLength);
            $length -= $readLength;
            flush();
        }
        fclose($file);
        exit;
    }

    // Regular full file request
    return response()->stream(function() use ($file, $fileSize) {
        $buffer = 8192;
        while (!feof($file)) {
            echo fread($file, $buffer);
            flush();
        }
        fclose($file);
    }, 200, $headers);
})->where('filename', '.*');
// Route::get('/storage/stories/{filename}', function ($filename) {
//     $path = storage_path('app/public/stories/' . $filename);

//     if (!file_exists($path)) {
//         abort(404);
//     }

//     $fileSize = filesize($path);
//     $file = fopen($path, 'rb');
//     $headers = [
//         'Content-Type' => 'video/mp4',
//         'Content-Length' => $fileSize,
//         'Accept-Ranges' => 'bytes',
//         'Content-Disposition' => 'inline',
//     ];

//     // Handle range requests (for streaming)
//     if (isset($_SERVER['HTTP_RANGE'])) {
//         $range = $_SERVER['HTTP_RANGE'];
//         $range = str_replace('bytes=', '', $range);
//         list($start, $end) = explode('-', $range);

//         $start = intval($start);
//         $end = $end ? intval($end) : $fileSize - 1;

//         fseek($file, $start);
//         $length = $end - $start + 1;

//         header('HTTP/1.1 206 Partial Content');
//         header("Content-Range: bytes $start-$end/$fileSize");
//         header("Content-Length: $length");

//         return response()->stream(function() use ($file, $length) {
//             echo fread($file, $length);
//             fclose($file);
//         }, 206, $headers);
//     }

//     return response()->stream(function() use ($file, $fileSize) {
//         echo fread($file, $fileSize);
//         fclose($file);
//     }, 200, $headers);
// })->where('filename', '.*');

Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');


Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:clear');

    return "Cache cleared successfully";
 });

 Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    
    return response()->json([
        'message' => 'Storage link created successfully',
        'output' => Artisan::output() // Optional: Get any output from the command
    ]);
});