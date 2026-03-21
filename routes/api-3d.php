<?php declare(strict_types=1);

use App\Http\Controllers\API\V1\Product3DController;
use App\Http\Controllers\API\V1\Room3DController;
use App\Http\Controllers\API\V1\Vehicle3DController;
use App\Http\Controllers\API\V1\Furniture3DController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/3d')->middleware(['auth:sanctum', 'rate-limit-api'])->group(function () {
    // Product 3D Models
    Route::prefix('products')->controller(Product3DController::class)->group(function () {
        Route::get('/{productId}', 'show');
        Route::get('/{productId}/thumbnail', 'getThumbnail');
        Route::post('/{productId}/upload/{vertical}', 'upload');
        Route::get('/vertical/{verticalId}', 'index');
    });

    // Room 3D Visualization
    Route::prefix('rooms')->controller(Room3DController::class)->group(function () {
        Route::post('/{roomId}/visualize', 'visualize');
        Route::post('/property/{propertyId}/visualize', 'propertyVisualize');
    });

    // Vehicle 3D Configurator
    Route::prefix('vehicles')->controller(Vehicle3DController::class)->group(function () {
        Route::post('/{vehicleId}/visualize', 'visualize');
        Route::get('/{vehicleId}/camera-angles', 'getCameraAngles');
    });

    // Furniture AR
    Route::prefix('furniture')->controller(Furniture3DController::class)->group(function () {
        Route::post('/{furnitureId}/generate', 'generate');
        Route::post('/room/placement', 'roomPlacement');
    });
});
