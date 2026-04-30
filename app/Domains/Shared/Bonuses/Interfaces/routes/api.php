<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Bonuses\Interfaces\Http\Controllers\BonusController;

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'tenant'],
    'prefix' => 'api/v1/bonuses',
], function () {
    Route::post('award', [BonusController::class, 'award'])
        ->name('bonuses.award')
        ->middleware('throttle:30,1');
});
