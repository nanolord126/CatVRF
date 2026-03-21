<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Sports\Http\Controllers\{
    StudioController,
    TrainerController,
    ClassController,
    BookingController,
    ReviewController,
};

Route::prefix('api')->group(function () {
    // Public routes
    Route::get('/studios', [StudioController::class, 'index']);
    Route::get('/studios/{id}', [StudioController::class, 'show']);
    Route::get('/studios/{id}/classes', [ClassController::class, 'byStudio']);
    Route::get('/studios/{id}/trainers', [TrainerController::class, 'byStudio']);
    Route::get('/studios/{id}/reviews', [ReviewController::class, 'byStudio']);
    Route::get('/trainers/{id}', [TrainerController::class, 'show']);
    Route::get('/classes/{id}', [ClassController::class, 'show']);

    // Protected routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Studio owner
        Route::post('/studios', [StudioController::class, 'store']);
        Route::patch('/studios/{id}', [StudioController::class, 'update']);
        Route::delete('/studios/{id}', [StudioController::class, 'delete']);

        // Classes
        Route::post('/studios/{studioId}/classes', [ClassController::class, 'store']);
        Route::patch('/classes/{id}', [ClassController::class, 'update']);
        Route::delete('/classes/{id}', [ClassController::class, 'delete']);

        // Bookings
        Route::post('/classes/{classId}/book', [BookingController::class, 'create']);
        Route::get('/my-bookings', [BookingController::class, 'myBookings']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        Route::patch('/bookings/{id}/attend', [BookingController::class, 'markAttended']);

        // Memberships
        Route::post('/studios/{studioId}/memberships', [StudioController::class, 'createMembership']);
        Route::patch('/memberships/{id}', [StudioController::class, 'updateMembership']);
        Route::delete('/memberships/{id}', [StudioController::class, 'deleteMembership']);

        // Purchases
        Route::post('/memberships/{membershipId}/purchase', [StudioController::class, 'purchaseMembership']);
        Route::get('/my-purchases', [StudioController::class, 'myPurchases']);
        Route::patch('/purchases/{id}/refund', [StudioController::class, 'refundPurchase']);

        // Reviews
        Route::post('/studios/{studioId}/reviews', [ReviewController::class, 'store']);
        Route::post('/trainers/{trainerId}/reviews', [ReviewController::class, 'storeForTrainer']);
        Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
        Route::patch('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'delete']);

        // Analytics
        Route::get('/studios/{id}/analytics', [StudioController::class, 'analytics']);
    });
});
