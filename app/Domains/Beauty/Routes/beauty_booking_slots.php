<?php declare(strict_types=1);

use App\Domains\Beauty\Controllers\BookingSlotController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenancy', 'b2c-b2b', 'rate-limit', 'fraud-check'])
    ->prefix('beauty/booking-slots')
    ->group(function () {
        Route::post('/hold', [BookingSlotController::class, 'hold']);
        Route::post('/release', [BookingSlotController::class, 'release']);
        Route::post('/confirm', [BookingSlotController::class, 'confirm']);
        Route::get('/{id}', [BookingSlotController::class, 'show']);
    });
