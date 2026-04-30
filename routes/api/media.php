<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MediaController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Media Upload Routes
    Route::post('/media/upload-image', [MediaController::class, 'uploadImage']);
    Route::post('/media/upload-multiple', [MediaController::class, 'uploadMultipleImages']);
    Route::post('/media/upload-document', [MediaController::class, 'uploadDocument']);
    Route::post('/media/bulk-upload-zip', [MediaController::class, 'bulkUploadZip']);
    
    // Media Download/Delete Routes
    Route::get('/media/download/{mediaId}', [MediaController::class, 'download']);
    Route::delete('/media/{mediaId}', [MediaController::class, 'delete']);
});
