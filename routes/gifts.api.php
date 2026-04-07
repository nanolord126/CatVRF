<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Gifts API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/gifts')->group(function () {
    Route::get('/', fn() => response()->json(['message' => 'Gifts API - Coming soon']));
});
