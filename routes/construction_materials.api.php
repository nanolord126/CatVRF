<?php

declare(strict_types=1);

use App\Domains\ConstructionMaterials\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('construction-materials')->group(function (): void {
    Route::get('/',             [MaterialController::class, 'index']);
    Route::get('{id}',          [MaterialController::class, 'show']);
    Route::post('calculate',    [MaterialController::class, 'calculate']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [MaterialController::class, 'order']);
        Route::get('my-orders', [MaterialController::class, 'myOrders']);
    });
});
