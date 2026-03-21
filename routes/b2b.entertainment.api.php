<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Entertainment\Http\Controllers\B2BEntertainmentController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/entertainment')->group(function () {
    Route::get('/', [B2BEntertainmentController::class, 'storefronts'])->name('b2b.entertainment.index');
    Route::post('/', [B2BEntertainmentController::class, 'createStorefront'])->name('b2b.entertainment.store');
    Route::post('/orders', [B2BEntertainmentController::class, 'createOrder'])->name('b2b.entertainment.order.store');
    Route::get('/orders/my', [B2BEntertainmentController::class, 'myB2BOrders'])->name('b2b.entertainment.orders.my');
    Route::post('/orders/{id}/approve', [B2BEntertainmentController::class, 'approveOrder'])->name('b2b.entertainment.order.approve');
    Route::post('/orders/{id}/reject', [B2BEntertainmentController::class, 'rejectOrder'])->name('b2b.entertainment.order.reject');
    Route::post('/{id}/verify-inn', [B2BEntertainmentController::class, 'verifyInn'])->middleware('admin')->name('b2b.entertainment.verify-inn');
});
