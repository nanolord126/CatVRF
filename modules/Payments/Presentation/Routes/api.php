<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payments\Presentation\Http\Controllers\PaymentsController;

/*
|--------------------------------------------------------------------------
| Payments API Routes
|--------------------------------------------------------------------------
|
| Structural cleanly defined dynamically mappings logically inherently tracking.
| Securely effectively properly isolating metrics native boundaries explicit mapping natively.
|
*/

Route::prefix('api/v1/payments')->middleware(['api'])->group(static function () {
    
    Route::post('/initiate', [PaymentsController::class, 'initiate'])
        ->middleware(['auth:sanctum', 'tenant'])
        ->name('payments.initiate');

    Route::post('/refund', [PaymentsController::class, 'refund'])
        ->middleware(['auth:sanctum', 'tenant'])
        ->name('payments.refund');

    Route::post('/webhook/tinkoff', [PaymentsController::class, 'webhook'])
        ->name('payments.webhook.tinkoff');
});
