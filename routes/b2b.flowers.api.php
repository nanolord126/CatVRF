<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Flowers\Http\Controllers\B2BFlowerController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/flowers')->group(function () {
    Route::get('/', [B2BFlowerController::class, 'storefronts'])->name('b2b.flowers.index');
    Route::post('/', [B2BFlowerController::class, 'createStorefront'])->name('b2b.flowers.store');
    Route::post('/orders', [B2BFlowerController::class, 'createOrder'])->name('b2b.flowers.order.store');
    Route::get('/orders/my', [B2BFlowerController::class, 'myB2BOrders'])->name('b2b.flowers.orders.my');
    Route::post('/orders/{id}/approve', [B2BFlowerController::class, 'approveOrder'])->name('b2b.flowers.order.approve');
    Route::post('/orders/{id}/reject', [B2BFlowerController::class, 'rejectOrder'])->name('b2b.flowers.order.reject');
    Route::post('/{id}/verify-inn', [B2BFlowerController::class, 'verifyInn'])->middleware('admin')->name('b2b.flowers.verify-inn');
});
