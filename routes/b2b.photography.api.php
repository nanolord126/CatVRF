<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Photography\Http\Controllers\B2BPhotoController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/photography')->group(function () {
    Route::get('/', [B2BPhotoController::class, 'storefronts'])->name('b2b.photography.index');
    Route::post('/', [B2BPhotoController::class, 'createStorefront'])->name('b2b.photography.store');
    Route::post('/orders', [B2BPhotoController::class, 'createOrder'])->name('b2b.photography.order.store');
    Route::get('/orders/my', [B2BPhotoController::class, 'myB2BOrders'])->name('b2b.photography.orders.my');
    Route::post('/orders/{id}/approve', [B2BPhotoController::class, 'approveOrder'])->name('b2b.photography.order.approve');
    Route::post('/orders/{id}/reject', [B2BPhotoController::class, 'rejectOrder'])->name('b2b.photography.order.reject');
    Route::post('/{id}/verify-inn', [B2BPhotoController::class, 'verifyInn'])->middleware('admin')->name('b2b.photography.verify-inn');
});
