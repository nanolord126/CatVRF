<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Freelance & Services API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/freelance')->group(function () {
    // Placeholder routes
    Route::get('/', function () {
        return response()->json(['message' => 'Freelance API - Coming soon']);
    });
});
