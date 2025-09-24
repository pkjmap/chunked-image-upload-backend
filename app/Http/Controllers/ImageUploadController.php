<?php

// app/Http/Controllers/ImageUploadController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded() === false) {
            return response()->json(['error' => 'File not uploaded.'], 400);
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $file = $save->getFile();
            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $path = storage_path("app/public/uploads/{$filename}");

            // Save original
            $file->move(storage_path("app/public/uploads"), $filename);

            // Resize versions
            $sizes = [
                'large' => [1200, 1200],
                'medium' => [600, 600],
                'thumb' => [300, 300],
            ];

            foreach ($sizes as $key => [$w, $h]) {
                $resized = Image::read($path)->cover($w, $h);
                $resized->save(storage_path("app/public/uploads/{$key}_{$filename}"));
            }

            return response()->json([
                'path' => "uploads/{$filename}",
                'sizes' => collect($sizes)->mapWithKeys(fn($s, $k) => [
                    $k => "uploads/{$k}_{$filename}"
                ])
            ]);
        }

        return response()->json([
            'done' => $save->handler()->getPercentageDone()
        ]);
    }
}
