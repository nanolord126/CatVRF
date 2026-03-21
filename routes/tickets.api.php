<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Tickets\Http\Controllers\{
    EventController,
    TicketTypeController,
    TicketSaleController,
    TicketController,
    EventReviewController,
};

Route::prefix('api')->group(function () {
    // Public routes
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::get('/events/{id}/ticket-types', [TicketTypeController::class, 'byEvent']);
    Route::get('/events/{id}/reviews', [EventReviewController::class, 'byEvent']);
    Route::get('/events/categories', [EventController::class, 'categories']);

    // Protected routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Event organizer
        Route::post('/events', [EventController::class, 'store']);
        Route::patch('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'delete']);

        // Ticket types
        Route::post('/events/{eventId}/ticket-types', [TicketTypeController::class, 'store']);
        Route::patch('/ticket-types/{id}', [TicketTypeController::class, 'update']);
        Route::delete('/ticket-types/{id}', [TicketTypeController::class, 'delete']);

        // Ticket sales
        Route::post('/events/{eventId}/buy-tickets', [TicketSaleController::class, 'purchase']);
        Route::get('/my-tickets', [TicketController::class, 'myTickets']);
        Route::get('/tickets/{id}', [TicketController::class, 'show']);
        Route::post('/tickets/{id}/download', [TicketController::class, 'download']);

        // Checkin
        Route::post('/events/{eventId}/checkin', [TicketController::class, 'checkin']);

        // Sales management
        Route::get('/events/{eventId}/sales', [TicketSaleController::class, 'eventSales']);
        Route::patch('/sales/{id}/refund', [TicketSaleController::class, 'refund']);
        Route::get('/sales/{id}', [TicketSaleController::class, 'show']);

        // Reviews
        Route::post('/events/{eventId}/reviews', [EventReviewController::class, 'store']);
        Route::get('/reviews/my', [EventReviewController::class, 'myReviews']);
        Route::patch('/reviews/{id}', [EventReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [EventReviewController::class, 'delete']);

        // Analytics
        Route::get('/events/{id}/analytics', [EventController::class, 'analytics']);
    });
});
