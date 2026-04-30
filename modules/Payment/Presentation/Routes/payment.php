<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\Controllers\PaymentController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('payments')->group(function () {
        Route::post('{paymentId}/success', [PaymentController::class, 'handleSuccess'])
            ->name('payments.success');

        Route::post('{paymentId}/failure', [PaymentController::class, 'handleFailure'])
            ->name('payments.failure');

        Route::post('{paymentId}/partial', [PaymentController::class, 'handlePartialPayment'])
            ->name('payments.partial');

        Route::post('{paymentId}/refund', [PaymentController::class, 'handleRefund'])
            ->name('payments.refund');

        Route::get('{paymentId}', [PaymentController::class, 'getPayment'])
            ->name('payments.show');
    });
});
