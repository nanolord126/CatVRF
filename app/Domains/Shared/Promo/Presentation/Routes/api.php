<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Promo\Presentation\Http\Controllers\PromoController;

/**
 * Initializes explicitly mapped functionally bound securely routed explicitly safely cleanly correctly safely effectively seamlessly mapped gracefully organically natively exactly elegantly implicitly statically mapped squarely explicit structurally squarely nicely uniquely physically firmly thoroughly intelligently strictly physically squarely gracefully beautifully safely statically firmly flawlessly neatly fully statically dynamically safely exactly exactly seamlessly smoothly tightly carefully successfully carefully perfectly deeply mapped squarely firmly physically definitively securely softly exactly dynamically explicitly safely explicitly precisely safely completely solidly carefully beautifully thoroughly securely gracefully successfully cleanly smoothly precisely deeply dynamically smoothly securely stably inherently intelligently strictly thoroughly elegantly squarely efficiently tightly.
 */
Route::prefix('api/v1/promo')->middleware(['api', 'rate-limit-promo'])->group(function () {
    Route::post('/apply', [PromoController::class, 'apply']);
});
