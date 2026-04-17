<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Luxury\LuxuryAIConstructorController;

/**
 * Luxury Category — Production 2026
 * Appends to API v1 middleware group (auth, tenant, rate-limit)
 *
 * @version 1.0.0
 * @author CatVRF
 */
Route::prefix('luxury')->group(function () {

    // AI-Constructor / Curation
    Route::post('/ai-curate', [LuxuryAIConstructorController::class, 'curate'])
        ->middleware('throttle:10,1') // max 10 requests per minute
        ->name('v1.luxury.ai-curate');

});
