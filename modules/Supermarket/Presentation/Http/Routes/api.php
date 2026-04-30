<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supermarket\Presentation\Http\Controllers\BuyerDashboardController;

Route::middleware(['auth:sanctum'])->prefix('api/buyer')->group(function () {
    Route::get('/dashboard', [BuyerDashboardController::class, 'index']);
    Route::get('/subscriptions', [BuyerDashboardController::class, 'subscriptions']);
    Route::get('/orders', [BuyerDashboardController::class, 'orders']);
    
    Route::post('/subscriptions/{id}/pause', [BuyerDashboardController::class, 'pauseSubscription']);
    Route::post('/subscriptions/{id}/resume', [BuyerDashboardController::class, 'resumeSubscription']);
    Route::post('/subscriptions/{id}/cancel', [BuyerDashboardController::class, 'cancelSubscription']);
});
