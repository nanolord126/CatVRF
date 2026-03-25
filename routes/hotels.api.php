<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Hotels\BookingController;

/**
 * Hotels & Accommodation API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/hotels')->group(function () {
    // Hotels listings
    Route::get('/', [BookingController::class, 'listHotels'])
        ->name('api.hotels.list');
    
    Route::get('/{hotel}', [BookingController::class, 'showHotel'])
        ->name('api.hotels.show');
    
    Route::get('/{hotel}/rooms', [BookingController::class, 'listRooms'])
        ->name('api.hotels.rooms.list');
    
    Route::get('/{hotel}/availability', [BookingController::class, 'checkAvailability'])
        ->name('api.hotels.availability');
    
    Route::get('/{hotel}/reviews', [BookingController::class, 'listReviews'])
        ->name('api.hotels.reviews.list');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/hotels')->group(function () {
    // Bookings
    Route::post('/bookings', [BookingController::class, 'store'])
        ->name('api.hotels.bookings.store')
        ->middleware('throttle:30,1');
    
    Route::get('/bookings', [BookingController::class, 'listUserBookings'])
        ->name('api.hotels.bookings.list');
    
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->name('api.hotels.bookings.show');
    
    Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])
        ->name('api.hotels.bookings.check-in')
        ->middleware('throttle:30,1');
    
    Route::post('/bookings/{booking}/check-out', [BookingController::class, 'checkOut'])
        ->name('api.hotels.bookings.check-out')
        ->middleware('throttle:30,1');
    
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('api.hotels.bookings.cancel')
        ->middleware('throttle:30,1');
    
    // Reviews
    Route::post('/{hotel}/reviews', [BookingController::class, 'createReview'])
        ->name('api.hotels.reviews.store')
        ->middleware('throttle:20,1');
});

// ===== HOTEL OWNER ENDPOINTS (Auth + Hotel Owner) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'role:hotel_owner', 'throttle:60,1'])->prefix('api/v1/hotels')->group(function () {
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

// ===== ADMIN ENDPOINTS (Auth + Admin) =====
Route::middleware(['api', 'auth:sanctum', 'role:admin', 'throttle:60,1'])->prefix('api/v1/hotels')->group(function () {
    Route::delete('/{id}', [HotelController::class, 'destroy'])->name('hotels.destroy');
    Route::patch('/{id}/verify', [HotelController::class, 'verify'])->name('hotels.verify');
});
