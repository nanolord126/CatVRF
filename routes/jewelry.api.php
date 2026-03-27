<?php

declare(strict_types=1);

use App\Domains\Luxury\Jewelry\Http\Controllers\JewelryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('jewelry')->group(function (): void {
    Route::get('/',              [JewelryController::class, 'index']);
    Route::get('{id}',           [JewelryController::class, 'show']);
    Route::get('{id}/3d',        [JewelryController::class, 'view3D']);
    Route::get('{id}/certificate', [JewelryController::class, 'certificate']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',    [JewelryController::class, 'order']);
        Route::get('my-orders',  [JewelryController::class, 'myOrders']);
    });
});
