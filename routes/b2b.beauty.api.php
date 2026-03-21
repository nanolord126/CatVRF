<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Beauty\Http\Controllers\B2BBeautyController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/beauty')->group(function () {
    Route::get('/', [B2BBeautyController::class, 'storefronts'])->name('b2b.beauty.index');
    Route::post('/', [B2BBeautyController::class, 'createStorefront'])->name('b2b.beauty.store');
    Route::post('/orders', [B2BBeautyController::class, 'createOrder'])->name('b2b.beauty.order.store');
    Route::get('/orders/my', [B2BBeautyController::class, 'myB2BOrders'])->name('b2b.beauty.orders.my');
    Route::post('/orders/{id}/approve', [B2BBeautyController::class, 'approveOrder'])->name('b2b.beauty.order.approve');
    Route::post('/orders/{id}/reject', [B2BBeautyController::class, 'rejectOrder'])->name('b2b.beauty.order.reject');
    Route::post('/{id}/verify-inn', [B2BBeautyController::class, 'verifyInn'])->middleware('admin')->name('b2b.beauty.verify-inn');
});
