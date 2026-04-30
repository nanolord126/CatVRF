<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OctaneHealthController;

Route::middleware(['auth:sanctum', 'can:view octane health'])->group(function () {
    Route::get('/octane/health', [OctaneHealthController::class, 'index']);
    Route::get('/octane/metrics', [OctaneHealthController::class, 'metrics']);
    Route::get('/octane/tables', [OctaneHealthController::class, 'tables']);
});
