<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Fashion\FashionRetail\Http\Controllers\B2BFashionController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/fashion-retail')->group(function () {
    Route::get('/', [B2BFashionController::class, 'storefronts'])->name('b2b.fashion-retail.index');
    Route::post('/', [B2BFashionController::class, 'createStorefront'])->name('b2b.fashion-retail.store');
    Route::post('/orders', [B2BFashionController::class, 'createOrder'])->name('b2b.fashion-retail.order.store');
    Route::get('/orders/my', [B2BFashionController::class, 'myB2BOrders'])->name('b2b.fashion-retail.orders.my');
    Route::post('/orders/{id}/approve', [B2BFashionController::class, 'approveOrder'])->name('b2b.fashion-retail.order.approve');
    Route::post('/orders/{id}/reject', [B2BFashionController::class, 'rejectOrder'])->name('b2b.fashion-retail.order.reject');
    Route::post('/{id}/verify-inn', [B2BFashionController::class, 'verifyInn'])->middleware('admin')->name('b2b.fashion-retail.verify-inn');
});
