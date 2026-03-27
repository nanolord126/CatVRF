<?php declare(strict_types=1);

use App\Domains\EventPlanning\Entertainment\Http\Controllers\EntertainmentVenueController;
use App\Domains\EventPlanning\Entertainment\Http\Controllers\EntertainmentEventController;
use App\Domains\EventPlanning\Entertainment\Http\Controllers\EntertainerController;
use App\Domains\EventPlanning\Entertainment\Http\Controllers\BookingController;
use App\Domains\EventPlanning\Entertainment\Http\Controllers\TicketSaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['tenant'])->prefix('api/entertainment')->group(function () {
    // Public routes
    Route::get('/venues', [EntertainmentVenueController::class, 'index']);
    Route::get('/venues/{id}', [EntertainmentVenueController::class, 'show']);
    Route::get('/events', [EntertainmentEventController::class, 'index']);
    Route::get('/events/{id}', [EntertainmentEventController::class, 'show']);
    Route::get('/entertainers', [EntertainerController::class, 'index']);
    Route::get('/entertainers/{id}', [EntertainerController::class, 'show']);
    Route::get('/entertainers/{id}/events', [EntertainerController::class, 'getEvents']);

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Bookings
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/my', [BookingController::class, 'myBookings']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::patch('/bookings/{id}', [BookingController::class, 'update']);
        Route::delete('/bookings/{id}', [BookingController::class, 'cancel']);

        // Event details
        Route::get('/events/{id}/schedule', [EntertainmentEventController::class, 'getSchedule']);
        Route::get('/events/{id}/reviews', [EntertainmentEventController::class, 'getReviews']);
        Route::post('/events/{id}/review', [EntertainmentEventController::class, 'addReview']);

        // Tickets
        Route::get('/tickets/my', [TicketSaleController::class, 'myTickets']);
        Route::get('/tickets/{id}', [TicketSaleController::class, 'show']);
        Route::patch('/tickets/{id}/validate', [TicketSaleController::class, 'validateTicket']);

        // Entertainer profile
        Route::post('/entertainers/register', [EntertainerController::class, 'register']);
        Route::get('/entertainer/profile', [EntertainerController::class, 'myProfile']);
        Route::patch('/entertainer/profile', [EntertainerController::class, 'updateProfile']);
        Route::get('/entertainer/schedule', [EntertainerController::class, 'getSchedule']);
        Route::patch('/entertainer/schedule', [EntertainerController::class, 'updateSchedule']);
        Route::get('/entertainer/earnings', [EntertainerController::class, 'myEarnings']);

        // Admin routes
        Route::middleware(['admin'])->group(function () {
            // Venues
            Route::post('/venues', [EntertainmentVenueController::class, 'store']);
            Route::patch('/venues/{id}', [EntertainmentVenueController::class, 'update']);
            Route::delete('/venues/{id}', [EntertainmentVenueController::class, 'delete']);

            // Events
            Route::post('/events', [EntertainmentEventController::class, 'store']);
            Route::patch('/events/{id}', [EntertainmentEventController::class, 'update']);
            Route::delete('/events/{id}', [EntertainmentEventController::class, 'cancel']);

            // Schedules
            Route::post('/schedule/{eventId}', [EntertainmentEventController::class, 'addSchedule']);
            Route::patch('/schedule/{scheduleId}', [EntertainmentEventController::class, 'updateSchedule']);
            Route::delete('/schedule/{scheduleId}', [EntertainmentEventController::class, 'cancelSchedule']);

            // Tickets
            Route::get('/tickets/event/{eventId}', [TicketSaleController::class, 'getEventTickets']);
            Route::patch('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
            Route::patch('/bookings/{id}/expire', [BookingController::class, 'expire']);

            // Analytics
            Route::get('/analytics/venue/{venueId}', [EntertainmentVenueController::class, 'analytics']);
            Route::get('/analytics/event/{eventId}', [EntertainmentEventController::class, 'analytics']);
            Route::get('/analytics/entertainer/{entertainerId}', [EntertainerController::class, 'analytics']);
        });
    });
});
