<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Flowers\Presentation\Http\Controllers\FlowersController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('flowers')->group(function () {
        Route::prefix('orders')->group(function () {
            Route::post('/', [FlowersController::class, 'createOrder'])
                ->name('flowers.orders.create');

            Route::get('{orderId}', [FlowersController::class, 'getOrder'])
                ->name('flowers.orders.show');

            Route::post('{orderId}/status', [FlowersController::class, 'updateOrderStatus'])
                ->name('flowers.orders.status');

            Route::post('{orderId}/assign-florist', [FlowersController::class, 'assignFlorist'])
                ->name('flowers.orders.assign-florist');

            Route::post('{orderId}/cancel', [FlowersController::class, 'cancelOrder'])
                ->name('flowers.orders.cancel');

            Route::post('{orderId}/deliver', [FlowersController::class, 'markAsDelivered'])
                ->name('flowers.orders.deliver');
        });

        Route::get('florists/available', [FlowersController::class, 'getAvailableFlorists'])
            ->name('flowers.florists.available');

        Route::get('delivery-slots', [FlowersController::class, 'getDeliverySlots'])
            ->name('flowers.delivery-slots');
    });
});
