<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\DemandForecast\Presentation\Http\Controllers\ForecastController;

Route::prefix('api/forecast')->middleware(['api', 'auth:sanctum', 'tenant', 'rate-limit-search'])->group(function () {
    Route::post('/generate', [ForecastController::class, 'forecast']);
});
