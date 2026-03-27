<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Sports\Fitness\Http\Controllers\B2BFitnessController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/fitness')->group(function () {
    Route::get('/', [B2BFitnessController::class, 'storefronts'])->name('b2b.fitness.index');
    Route::post('/', [B2BFitnessController::class, 'createStorefront'])->name('b2b.fitness.store');
    Route::post('/orders', [B2BFitnessController::class, 'createOrder'])->name('b2b.fitness.order.store');
    Route::get('/orders/my', [B2BFitnessController::class, 'myB2BOrders'])->name('b2b.fitness.orders.my');
    Route::post('/orders/{id}/approve', [B2BFitnessController::class, 'approveOrder'])->name('b2b.fitness.order.approve');
    Route::post('/orders/{id}/reject', [B2BFitnessController::class, 'rejectOrder'])->name('b2b.fitness.order.reject');
    Route::post('/{id}/verify-inn', [B2BFitnessController::class, 'verifyInn'])->middleware('admin')->name('b2b.fitness.verify-inn');
});
