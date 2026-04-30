<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hotels\Infrastructure\Http\Controllers\BookingComWebhookController;
use Modules\Hotels\Infrastructure\Http\Controllers\OstrovokWebhookController;
use Modules\Hotels\Infrastructure\Http\Controllers\AirbnbWebhookController;

Route::middleware(['api', 'throttle:60,1'])->group(function () {
    // Booking.com webhooks
    Route::post('/webhooks/booking-com', [BookingComWebhookController::class, 'handle'])
        ->name('webhooks.booking-com');

    // Ostrovok webhooks
    Route::post('/webhooks/ostrovok', [OstrovokWebhookController::class, 'handle'])
        ->name('webhooks.ostrovok');

    // Airbnb webhooks
    Route::post('/webhooks/airbnb', [AirbnbWebhookController::class, 'handle'])
        ->name('webhooks.airbnb');
});
