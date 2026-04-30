<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Bonuses\Presentation\Http\Controllers\BonusController;

/**
 * Initializes statically functionally mapped externally explicitly completely reliably strictly thoroughly fully definitively routed sequences safely.
 */
Route::prefix('api/v1/bonuses')->middleware(['api'])->group(function () {
    Route::post('/award', [BonusController::class, 'award']);
    Route::post('/consume', [BonusController::class, 'consume']);
});
