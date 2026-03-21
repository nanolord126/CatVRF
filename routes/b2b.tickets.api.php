<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Tickets\Http\Controllers\B2BTicketController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/tickets')->group(function () {
    Route::get('/', [B2BTicketController::class, 'storefronts'])->name('b2b.tickets.index');
    Route::post('/', [B2BTicketController::class, 'createStorefront'])->name('b2b.tickets.store');
    Route::post('/orders', [B2BTicketController::class, 'createOrder'])->name('b2b.tickets.order.store');
    Route::get('/orders/my', [B2BTicketController::class, 'myB2BOrders'])->name('b2b.tickets.orders.my');
    Route::post('/orders/{id}/approve', [B2BTicketController::class, 'approveOrder'])->name('b2b.tickets.order.approve');
    Route::post('/orders/{id}/reject', [B2BTicketController::class, 'rejectOrder'])->name('b2b.tickets.order.reject');
    Route::post('/{id}/verify-inn', [B2BTicketController::class, 'verifyInn'])->middleware('admin')->name('b2b.tickets.verify-inn');
});
