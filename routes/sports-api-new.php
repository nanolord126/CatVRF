<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Sports\Http\Controllers\AdaptiveWorkoutController;
use App\Domains\Sports\Http\Controllers\RealTimeBookingController;
use App\Domains\Sports\Http\Controllers\DynamicPricingController;
use App\Domains\Sports\Http\Controllers\LiveStreamController;
use App\Domains\Sports\Http\Controllers\FraudDetectionController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/sports')->group(function () {
    // Adaptive Workout Plans
    Route::prefix('adaptive-workouts')->group(function () {
        Route::post('/generate', [AdaptiveWorkoutController::class, 'generate']);
        Route::post('/{userId}/adjust', [AdaptiveWorkoutController::class, 'adjust']);
        Route::post('/{userId}/progress', [AdaptiveWorkoutController::class, 'trackProgress']);
        Route::get('/{userId}', [AdaptiveWorkoutController::class, 'show']);
    });

    // Real-time Booking
    Route::prefix('bookings')->group(function () {
        Route::post('/hold', [RealTimeBookingController::class, 'holdSlot']);
        Route::post('/confirm', [RealTimeBookingController::class, 'confirmBooking']);
        Route::post('/release', [RealTimeBookingController::class, 'releaseSlot']);
        Route::post('/extend-hold', [RealTimeBookingController::class, 'extendHold']);
        Route::get('/venues/{venueId}/slots', [RealTimeBookingController::class, 'getAvailableSlots']);
        Route::post('/{bookingId}/check-in/biometric', [RealTimeBookingController::class, 'verifyBiometricCheckIn']);
    });

    // Dynamic Pricing
    Route::prefix('pricing')->group(function () {
        Route::get('/venues/{venueId}/calculate', [DynamicPricingController::class, 'calculatePrice']);
        Route::post('/venues/{venueId}/flash-membership', [DynamicPricingController::class, 'createFlashMembership']);
        Route::get('/venues/{venueId}/bulk-pricing', [DynamicPricingController::class, 'getBulkPricing']);
        Route::post('/venues/{venueId}/update', [DynamicPricingController::class, 'updatePricing']);
        Route::get('/memberships/{membershipId}', [DynamicPricingController::class, 'showMembership']);
    });

    // Live Streams
    Route::prefix('live-streams')->group(function () {
        Route::post('/create', [LiveStreamController::class, 'create']);
        Route::post('/{streamId}/start', [LiveStreamController::class, 'start']);
        Route::post('/{streamId}/join', [LiveStreamController::class, 'join']);
        Route::post('/{streamId}/leave', [LiveStreamController::class, 'leave']);
        Route::post('/{streamId}/end', [LiveStreamController::class, 'end']);
        Route::get('/active', [LiveStreamController::class, 'getActiveStreams']);
        Route::get('/{streamId}/recording', [LiveStreamController::class, 'getRecording']);
    });

    // Fraud Detection
    Route::prefix('fraud')->group(function () {
        Route::post('/bookings/{bookingId}/cancellation', [FraudDetectionController::class, 'detectCancellationFraud']);
        Route::post('/bookings/{bookingId}/no-show', [FraudDetectionController::class, 'detectNoShowFraud']);
        Route::post('/users/{userId}/booking-pattern', [FraudDetectionController::class, 'detectBookingPatternFraud']);
        Route::post('/users/{userId}/penalty', [FraudDetectionController::class, 'applyFraudPenalty']);
        Route::get('/users/{userId}/fraud-score', [FraudDetectionController::class, 'getUserFraudScore']);
    });
});
