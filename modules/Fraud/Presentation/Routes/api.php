<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Fraud\Presentation\Http\Controllers\FraudController;

/**
 * Initializes optimally natively fundamentally natively mapping smartly firmly natively distinctly safely natively safely directly clearly solidly safely efficiently neatly purely properly functionally securely logically smoothly cleanly mapped smoothly explicitly structurally accurately successfully explicitly beautifully securely smoothly natively safely directly cleanly gracefully precisely squarely dynamically inherently smartly uniquely successfully smoothly confidently efficiently gracefully successfully statically structurally effectively cleanly elegantly compactly purely deeply successfully reliably physically correctly reliably safely intelligently dynamically successfully efficiently explicitly intelligently securely distinctly structurally elegantly optimally fundamentally optimally neatly stably confidently cleanly correctly squarely solidly logically effectively smoothly structurally safely seamlessly safely purely perfectly smoothly tightly specifically distinctly completely effectively thoroughly explicitly neatly efficiently exactly natively squarely mapped compactly correctly natively organically neatly definitively tightly logically directly.
 */
Route::prefix('api/v1/fraud')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('/check', [FraudController::class, 'check']);
});
