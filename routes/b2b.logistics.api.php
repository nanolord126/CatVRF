<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Logistics\Http\Controllers\B2BLogisticsController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/logistics')->group(function () {
    Route::get('/', [B2BLogisticsController::class, 'storefronts'])->name('b2b.logistics.index');
    Route::post('/', [B2BLogisticsController::class, 'createStorefront'])->name('b2b.logistics.store');
    Route::post('/orders', [B2BLogisticsController::class, 'createOrder'])->name('b2b.logistics.order.store');
    Route::get('/orders/my', [B2BLogisticsController::class, 'myB2BOrders'])->name('b2b.logistics.orders.my');
    Route::post('/orders/{id}/approve', [B2BLogisticsController::class, 'approveOrder'])->name('b2b.logistics.order.approve');
    Route::post('/orders/{id}/reject', [B2BLogisticsController::class, 'rejectOrder'])->name('b2b.logistics.order.reject');
    Route::post('/{id}/verify-inn', [B2BLogisticsController::class, 'verifyInn'])->middleware('admin')->name('b2b.logistics.verify-inn');
});
