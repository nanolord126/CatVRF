<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Music\MusicStoreController;
use App\Http\Controllers\Api\Music\MusicInstrumentController;
use App\Http\Controllers\Api\Music\MusicBookingController;
use App\Http\Controllers\Api\Music\MusicStudioController;
use App\Http\Controllers\Api\Music\MusicLessonController;
use App\Http\Controllers\Api\Music\MusicReviewController;
use Illuminate\Support\Facades\Route;

/**
 * Music & Instruments Vertical API Routes
 * Production Ready 2026 Canon.
 */
Route::prefix('v1/music')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function () {

    // Music Stores
    Route::apiResource('stores', MusicStoreController::class);
    
    // Instruments & Inventory
    Route::apiResource('instruments', MusicInstrumentController::class);
    Route::post('instruments/{instrument}/rent/{days}', [MusicInstrumentController::class, 'rent'])
        ->name('music.instruments.rent');
    
    // Bookings (Studios & Lessons)
    Route::apiResource('bookings', MusicBookingController::class)->except(['update']);
    Route::post('bookings/{booking}/cancel', [MusicBookingController::class, 'cancel'])
        ->name('music.bookings.cancel');
    Route::patch('bookings/{booking}/status/{status}', [MusicBookingController::class, 'updateStatus'])
        ->name('music.bookings.update-status');
        
    // Studios
    Route::apiResource('studios', MusicStudioController::class);
    
    // Lessons
    Route::apiResource('lessons', MusicLessonController::class);
    
    // Reviews
    Route::apiResource('reviews', MusicReviewController::class)->except(['update']);
    Route::post('reviews/{review}/verify', [MusicReviewController::class, 'verify'])
        ->name('music.reviews.verify');

});
