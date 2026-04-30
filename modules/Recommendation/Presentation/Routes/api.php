<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Recommendation\Presentation\Http\Controllers\RecommendationController;
use Modules\Recommendation\Presentation\Http\Controllers\SellerRecommendationController;

Route::prefix('api/recommendations')->middleware(['api', 'auth:sanctum', 'tenant', 'rate-limit-search'])->group(function () {
    Route::post('/', [RecommendationController::class, 'getRecommendations']);
    Route::post('/track/impression', [RecommendationController::class, 'trackImpression']);
    Route::post('/track/click', [RecommendationController::class, 'trackClick']);
    Route::post('/track/conversion', [RecommendationController::class, 'trackConversion']);
    Route::get('/metrics', [RecommendationController::class, 'getPerformanceMetrics']);
    Route::post('/cache/invalidate', [RecommendationController::class, 'invalidateCache']);
    Route::get('/model/health', [RecommendationController::class, 'getModelHealth']);
});

Route::prefix('api/recommendations/seller')->middleware(['api', 'auth:sanctum', 'tenant'])->group(function () {
    Route::get('/promote', [SellerRecommendationController::class, 'getPromotions']);
    Route::get('/metrics', [SellerRecommendationController::class, 'getMetrics']);
});
