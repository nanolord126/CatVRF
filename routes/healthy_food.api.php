<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Healthy Food API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/healthy_food')->group(function () {
    // Placeholder routes
    Route::get('/', function () {
        return response()->json(['message' => 'Healthy Food API - Coming soon']);
    });
});