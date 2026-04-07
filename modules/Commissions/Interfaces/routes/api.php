<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commissions\Interfaces\Http\Controllers\CommissionController;

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'tenant'],
    'prefix' => 'api/v1/commissions',
], function () {
    Route::post('calculate', [CommissionController::class, 'calculate'])
        ->name('commissions.calculate')
        ->middleware('throttle:60,1');
});
