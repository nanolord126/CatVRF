<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Sports\Http\Controllers\B2BSportController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/sports')->group(function () {
    Route::get('/', [B2BSportController::class, 'storefronts'])->name('b2b.sports.index');
    Route::post('/', [B2BSportController::class, 'createStorefront'])->name('b2b.sports.store');
    Route::post('/orders', [B2BSportController::class, 'createOrder'])->name('b2b.sports.order.store');
    Route::get('/orders/my', [B2BSportController::class, 'myB2BOrders'])->name('b2b.sports.orders.my');
    Route::post('/orders/{id}/approve', [B2BSportController::class, 'approveOrder'])->name('b2b.sports.order.approve');
    Route::post('/orders/{id}/reject', [B2BSportController::class, 'rejectOrder'])->name('b2b.sports.order.reject');
    Route::post('/{id}/verify-inn', [B2BSportController::class, 'verifyInn'])->middleware('admin')->name('b2b.sports.verify-inn');
});
