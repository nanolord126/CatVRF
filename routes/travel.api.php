<?php declare(strict_types=1);

use App\Domains\Travel\Http\Controllers\TravelAgencyController;
use App\Domains\Travel\Http\Controllers\TravelTourController;
use App\Domains\Travel\Http\Controllers\TravelBookingController;
use App\Domains\Travel\Http\Controllers\TravelFlightController;
use App\Domains\Travel\Http\Controllers\TravelTransportationController;
use App\Domains\Travel\Http\Controllers\TravelReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/travel')->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('/agencies', [TravelAgencyController::class, 'index']);
        Route::get('/agencies/{id}', [TravelAgencyController::class, 'show']);
        Route::get('/agencies/{id}/tours', [TravelAgencyController::class, 'getTours']);
        Route::get('/tours', [TravelTourController::class, 'index']);
        Route::get('/tours/{id}', [TravelTourController::class, 'show']);
        Route::get('/tours/{id}/reviews', [TravelTourController::class, 'getReviews']);
        Route::get('/accommodations', [TravelAgencyController::class, 'indexAccommodations']);
        Route::get('/accommodations/{id}', [TravelAgencyController::class, 'showAccommodation']);
        Route::get('/flights', [TravelFlightController::class, 'index']);
        Route::get('/flights/{id}', [TravelFlightController::class, 'show']);
        Route::get('/transportation', [TravelTransportationController::class, 'index']);
        Route::get('/transportation/{id}', [TravelTransportationController::class, 'show']);
        Route::get('/search', [TravelAgencyController::class, 'search']);
        Route::get('/guides', [TravelAgencyController::class, 'indexGuides']);
        Route::get('/guides/{id}', [TravelAgencyController::class, 'showGuide']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/bookings', [TravelBookingController::class, 'store']);
        Route::get('/bookings/{id}', [TravelBookingController::class, 'show']);
        Route::put('/bookings/{id}', [TravelBookingController::class, 'update']);
        Route::delete('/bookings/{id}', [TravelBookingController::class, 'destroy']);
        Route::get('/my-bookings', [TravelBookingController::class, 'userBookings']);
        Route::post('/bookings/{id}/complete', [TravelBookingController::class, 'complete']);
        Route::post('/bookings/{id}/cancel', [TravelBookingController::class, 'cancel']);

        Route::post('/reviews', [TravelReviewController::class, 'store']);
        Route::put('/reviews/{id}', [TravelReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [TravelReviewController::class, 'destroy']);
        Route::get('/my-reviews', [TravelReviewController::class, 'userReviews']);
    });

    Route::middleware(['auth:sanctum', 'can:manage_travel_agency'])->group(function () {
        Route::post('/agencies', [TravelAgencyController::class, 'store']);
        Route::put('/agencies/{id}', [TravelAgencyController::class, 'update']);
        Route::delete('/agencies/{id}', [TravelAgencyController::class, 'destroy']);
        Route::post('/agencies/{id}/restore', [TravelAgencyController::class, 'restore']);

        Route::post('/tours', [TravelTourController::class, 'store']);
        Route::put('/tours/{id}', [TravelTourController::class, 'update']);
        Route::delete('/tours/{id}', [TravelTourController::class, 'destroy']);
        Route::post('/tours/{id}/restore', [TravelTourController::class, 'restore']);

        Route::post('/flights', [TravelFlightController::class, 'store']);
        Route::put('/flights/{id}', [TravelFlightController::class, 'update']);
        Route::delete('/flights/{id}', [TravelFlightController::class, 'destroy']);

        Route::post('/transportation', [TravelTransportationController::class, 'store']);
        Route::put('/transportation/{id}', [TravelTransportationController::class, 'update']);
        Route::delete('/transportation/{id}', [TravelTransportationController::class, 'destroy']);

        Route::get('/analytics', [TravelAgencyController::class, 'analytics']);
        Route::get('/earnings', [TravelAgencyController::class, 'earnings']);
        Route::get('/bookings-list', [TravelAgencyController::class, 'bookingsList']);
    });

    Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {
        Route::post('/agencies/{id}/verify', [TravelAgencyController::class, 'verify']);
        Route::post('/agencies/{id}/reject', [TravelAgencyController::class, 'reject']);
        Route::get('/admin/agencies', [TravelAgencyController::class, 'allAgencies']);
        Route::post('/reviews/{id}/approve', [TravelReviewController::class, 'approve']);
        Route::post('/reviews/{id}/reject', [TravelReviewController::class, 'rejectReview']);
    });
});
