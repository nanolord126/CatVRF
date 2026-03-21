<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Auto\Http\Controllers\B2BAutoController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/auto')->group(function () {
    Route::get('/', [B2BAutoController::class, 'storefronts'])->name('b2b.auto.index');
    Route::post('/', [B2BAutoController::class, 'createStorefront'])->name('b2b.auto.store');
    Route::post('/orders', [B2BAutoController::class, 'createOrder'])->name('b2b.auto.order.store');
    Route::get('/orders/my', [B2BAutoController::class, 'myB2BOrders'])->name('b2b.auto.orders.my');
    Route::post('/orders/{id}/approve', [B2BAutoController::class, 'approveOrder'])->name('b2b.auto.order.approve');
    Route::post('/orders/{id}/reject', [B2BAutoController::class, 'rejectOrder'])->name('b2b.auto.order.reject');
    Route::post('/{id}/verify-inn', [B2BAutoController::class, 'verifyInn'])->middleware('admin')->name('b2b.auto.verify-inn');
});
