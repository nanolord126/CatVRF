<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Medical\Http\Controllers\B2BMedicalController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/medical')->group(function () {
    Route::get('/', [B2BMedicalController::class, 'storefronts'])->name('b2b.medical.index');
    Route::post('/', [B2BMedicalController::class, 'createStorefront'])->name('b2b.medical.store');
    Route::post('/orders', [B2BMedicalController::class, 'createOrder'])->name('b2b.medical.order.store');
    Route::get('/orders/my', [B2BMedicalController::class, 'myB2BOrders'])->name('b2b.medical.orders.my');
    Route::post('/orders/{id}/approve', [B2BMedicalController::class, 'approveOrder'])->name('b2b.medical.order.approve');
    Route::post('/orders/{id}/reject', [B2BMedicalController::class, 'rejectOrder'])->name('b2b.medical.order.reject');
    Route::post('/{id}/verify-inn', [B2BMedicalController::class, 'verifyInn'])->middleware('admin')->name('b2b.medical.verify-inn');
});
