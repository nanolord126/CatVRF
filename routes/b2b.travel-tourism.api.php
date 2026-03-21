<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\TravelTourism\Http\Controllers\B2BTravelController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/travel-tourism')->group(function () {
    Route::get('/', [B2BTravelController::class, 'storefronts'])->name('b2b.travel-tourism.index');
    Route::post('/', [B2BTravelController::class, 'createStorefront'])->name('b2b.travel-tourism.store');
    Route::post('/orders', [B2BTravelController::class, 'createOrder'])->name('b2b.travel-tourism.order.store');
    Route::get('/orders/my', [B2BTravelController::class, 'myB2BOrders'])->name('b2b.travel-tourism.orders.my');
    Route::post('/orders/{id}/approve', [B2BTravelController::class, 'approveOrder'])->name('b2b.travel-tourism.order.approve');
    Route::post('/orders/{id}/reject', [B2BTravelController::class, 'rejectOrder'])->name('b2b.travel-tourism.order.reject');
    Route::post('/{id}/verify-inn', [B2BTravelController::class, 'verifyInn'])->middleware('admin')->name('b2b.travel-tourism.verify-inn');
});
