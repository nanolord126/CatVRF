<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Hotels\Http\Controllers\B2BHotelController;
use Modules\Hotels\Infrastructure\Http\Controllers\BookingComWebhookController;
use Modules\Hotels\Infrastructure\Http\Controllers\OstrovokWebhookController;
use Modules\Hotels\Infrastructure\Http\Controllers\AirbnbWebhookController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/hotels')->group(function () {
    Route::get('/', [B2BHotelController::class, 'storefronts'])->name('b2b.hotels.index');
    Route::post('/', [B2BHotelController::class, 'createStorefront'])->name('b2b.hotels.store');
    Route::post('/orders', [B2BHotelController::class, 'createOrder'])->name('b2b.hotels.order.store');
    Route::get('/orders/my', [B2BHotelController::class, 'myB2BOrders'])->name('b2b.hotels.orders.my');
    Route::post('/orders/{id}/approve', [B2BHotelController::class, 'approveOrder'])->name('b2b.hotels.order.approve');
    Route::post('/orders/{id}/reject', [B2BHotelController::class, 'rejectOrder'])->name('b2b.hotels.order.reject');
    Route::post('/{id}/verify-inn', [B2BHotelController::class, 'verifyInn'])->middleware('admin')->name('b2b.hotels.verify-inn');
});

// Marketplace Integration Webhooks
Route::middleware(['api', 'throttle:60,1'])->prefix('api/hotels')->group(function () {
    Route::post('/webhooks/booking-com', [BookingComWebhookController::class, 'handle'])
        ->name('hotels.webhooks.booking-com');

    Route::post('/webhooks/ostrovok', [OstrovokWebhookController::class, 'handle'])
        ->name('hotels.webhooks.ostrovok');

    Route::post('/webhooks/airbnb', [AirbnbWebhookController::class, 'handle'])
        ->name('hotels.webhooks.airbnb');
});
