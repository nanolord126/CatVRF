<?php

declare(strict_types=1);

use App\Http\Controllers\Monitoring\PrometheusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:60,1'])->group(function () {
    Route::get('/metrics', [PrometheusController::class, 'metrics'])->name('monitoring.metrics');
    Route::get('/health', [PrometheusController::class, 'health'])->name('monitoring.health');
});
