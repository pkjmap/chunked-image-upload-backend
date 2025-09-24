<?php

use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\ProductImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('api')->group(function () {
    Route::post('/products/import', [ProductImportController::class, 'import']);
    Route::post('/upload', [ImageUploadController::class, 'upload']);
});