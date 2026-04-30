<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AMLScreeningController;

Route::middleware('auth:sanctum')->group(function () {
    // AML Screening endpoints
    Route::prefix('aml')->group(function () {
        Route::post('/transactions/{transactionId}/screen', [AMLScreeningController::class, 'screenTransaction'])
            ->name('aml.screen-transaction')
            ->whereNumber('transactionId');

        Route::post('/users/{userId}/screen', [AMLScreeningController::class, 'screenUser'])
            ->name('aml.screen-user')
            ->whereNumber('userId');
    });
});
