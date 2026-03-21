<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\HomeServices\Http\Controllers\B2BHomeServiceController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/home-services')->group(function () {
    Route::get('/', [B2BHomeServiceController::class, 'storefronts'])->name('b2b.home-services.index');
    Route::post('/', [B2BHomeServiceController::class, 'createStorefront'])->name('b2b.home-services.store');
    Route::post('/orders', [B2BHomeServiceController::class, 'createOrder'])->name('b2b.home-services.order.store');
    Route::get('/orders/my', [B2BHomeServiceController::class, 'myB2BOrders'])->name('b2b.home-services.orders.my');
    Route::post('/orders/{id}/approve', [B2BHomeServiceController::class, 'approveOrder'])->name('b2b.home-services.order.approve');
    Route::post('/orders/{id}/reject', [B2BHomeServiceController::class, 'rejectOrder'])->name('b2b.home-services.order.reject');
    Route::post('/{id}/verify-inn', [B2BHomeServiceController::class, 'verifyInn'])->middleware('admin')->name('b2b.home-services.verify-inn');
});
