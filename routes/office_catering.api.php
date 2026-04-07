<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Office Catering API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/office_catering')->group(function () {
    // Placeholder routes
    Route::get('/', function () {
        return response()->json(['message' => 'Office Catering API - Coming soon']);
    });
});
