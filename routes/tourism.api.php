<?php declare(strict_types=1);

use App\Domains\Travel\Http\Controllers\TourismBookingController;
use App\Domains\Travel\Http\Controllers\TourismWishlistController;
use Illuminate\Support\Facades\Route;

/**
 * Tourism API Routes
 * 
 * API endpoints for Tourism vertical with killer features:
 * - AI-personalized tours with embeddings
 * - Real-time availability hold with biometric verification
 * - Dynamic pricing + flash packages
 * - Virtual 360° tours + AR viewing
 * - Instant video-call with guides
 * - B2C quick booking + B2B corporate tours/MICE
 * - ML-fraud detection for cancellations
 * - Wallet split payment + instant cashback
 * - CRM integration at every status
 */

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/tourism')->group(function () {
    Route::prefix('bookings')->group(function () {
        Route::post('/', [TourismBookingController::class, 'store'])->name('tourism.bookings.store');
        Route::post('/confirm', [TourismBookingController::class, 'confirm'])->name('tourism.bookings.confirm');
        Route::post('/cancel', [TourismBookingController::class, 'cancel'])->name('tourism.bookings.cancel');
        Route::post('/video-call', [TourismBookingController::class, 'scheduleVideoCall'])->name('tourism.bookings.video-call');
        Route::post('/{uuid}/virtual-tour', [TourismBookingController::class, 'markVirtualTourViewed'])->name('tourism.bookings.virtual-tour');
        Route::get('/{uuid}', [TourismBookingController::class, 'show'])->name('tourism.bookings.show');
        Route::get('/', [TourismBookingController::class, 'index'])->name('tourism.bookings.index');
    });

    Route::prefix('wishlist')->group(function () {
        Route::post('/', [TourismWishlistController::class, 'store'])->name('tourism.wishlist.store');
        Route::get('/', [TourismWishlistController::class, 'index'])->name('tourism.wishlist.index');
        Route::get('/recommendations', [TourismWishlistController::class, 'recommendations'])->name('tourism.wishlist.recommendations');
        Route::get('/discount', [TourismWishlistController::class, 'discount'])->name('tourism.wishlist.discount');
        Route::delete('/{uuid}', [TourismWishlistController::class, 'destroy'])->name('tourism.wishlist.destroy');
    });
});
