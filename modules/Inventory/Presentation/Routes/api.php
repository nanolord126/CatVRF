<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Presentation\Http\Controllers\InventoryController;

Route::prefix('api/inventory')->middleware(['api', 'auth:sanctum', 'tenant'])->group(function () {
    Route::post('/reserve', [InventoryController::class, 'reserve']);
});
