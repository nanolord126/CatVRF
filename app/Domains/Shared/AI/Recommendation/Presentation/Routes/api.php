<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Recommendation\Presentation\Http\Controllers\RecommendationController;

Route::prefix('api/recommendations')->middleware(['api', 'auth:sanctum', 'tenant', 'rate-limit-search'])->group(function () {
    Route::post('/get-for-user', [RecommendationController::class, 'getRecommendations']);
});
