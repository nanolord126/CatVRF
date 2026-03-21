<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Hotels\Http\Controllers\HotelController;
use App\Domains\Hotels\Http\Controllers\RoomTypeController;
use App\Domains\Hotels\Http\Controllers\BookingController;
use App\Domains\Hotels\Http\Controllers\ReviewController;
use App\Domains\Hotels\Http\Controllers\PricingRuleController;

Route::prefix('api/hotels')->group(function () {
    // Public routes
    Route::get('/', [HotelController::class, 'index'])->name('hotels.index');
    Route::get('/{id}', [HotelController::class, 'show'])->name('hotels.show');
    Route::get('/{id}/rooms', [RoomTypeController::class, 'index'])->name('hotels.rooms.index');
    Route::get('/{id}/availability', [RoomTypeController::class, 'checkAvailability'])->name('hotels.availability');
    Route::get('/{id}/reviews', [ReviewController::class, 'index'])->name('hotels.reviews.index');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Guest bookings
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{id}', [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{id}', [BookingController::class, 'cancel'])->name('bookings.cancel');

        // Reviews
        Route::post('/{id}/reviews', [ReviewController::class, 'store'])->name('hotels.reviews.store');
        Route::get('/reviews/my', [ReviewController::class, 'myReviews'])->name('reviews.my');

        // Hotel owner routes
        Route::middleware('role:hotel_owner')->group(function () {
            Route::post('/', [HotelController::class, 'store'])->name('hotels.store');
            Route::patch('/{id}', [HotelController::class, 'update'])->name('hotels.update');
            
            Route::post('/{id}/rooms', [RoomTypeController::class, 'store'])->name('hotels.rooms.store');
            Route::patch('/rooms/{id}', [RoomTypeController::class, 'update'])->name('hotels.rooms.update');
            Route::delete('/rooms/{id}', [RoomTypeController::class, 'destroy'])->name('hotels.rooms.destroy');

            Route::post('/{id}/pricing-rules', [PricingRuleController::class, 'store'])->name('hotels.pricing.store');
            Route::patch('/pricing-rules/{id}', [PricingRuleController::class, 'update'])->name('hotels.pricing.update');
            Route::delete('/pricing-rules/{id}', [PricingRuleController::class, 'destroy'])->name('hotels.pricing.destroy');

            Route::get('/{id}/bookings', [BookingController::class, 'hotelBookings'])->name('hotels.bookings.index');
            Route::get('/{id}/revenue', [HotelController::class, 'revenue'])->name('hotels.revenue');
        });
    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::delete('/{id}', [HotelController::class, 'destroy'])->name('hotels.destroy');
        Route::patch('/{id}/verify', [HotelController::class, 'verify'])->name('hotels.verify');
    });
});
