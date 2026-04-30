<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Presentation\Http\Controllers\MediaController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::prefix('api/v1/media')->group(function () {
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::post('/upload-multiple', [MediaController::class, 'uploadMultiple']);
        Route::post('/bulk-import', [MediaController::class, 'bulkImport']);
        Route::get('/{mediaId}/optimized', [MediaController::class, 'getOptimizedUrl']);
        Route::get('/{mediaId}/download', [MediaController::class, 'download']);
        Route::delete('/{mediaId}', [MediaController::class, 'delete']);
        Route::get('/by-model', [MediaController::class, 'getByModel']);
    });
});
