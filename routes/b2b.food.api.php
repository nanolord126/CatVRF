<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Food\Http\Controllers\B2BFoodController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/food')->group(function () {
    Route::get('/', [B2BFoodController::class, 'storefronts'])->name('b2b.food.index');
    Route::post('/', [B2BFoodController::class, 'createStorefront'])->name('b2b.food.store');
    Route::post('/orders', [B2BFoodController::class, 'createOrder'])->name('b2b.food.order.store');
    Route::get('/orders/my', [B2BFoodController::class, 'myB2BOrders'])->name('b2b.food.orders.my');
    Route::post('/orders/{id}/approve', [B2BFoodController::class, 'approveOrder'])->name('b2b.food.order.approve');
    Route::post('/orders/{id}/reject', [B2BFoodController::class, 'rejectOrder'])->name('b2b.food.order.reject');
    Route::post('/{id}/verify-inn', [B2BFoodController::class, 'verifyInn'])->middleware('admin')->name('b2b.food.verify-inn');
});
