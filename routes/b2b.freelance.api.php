<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Freelance\Http\Controllers\B2BFreelanceController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/freelance')->group(function () {
    Route::get('/', [B2BFreelanceController::class, 'storefronts'])->name('b2b.freelance.index');
    Route::post('/', [B2BFreelanceController::class, 'createStorefront'])->name('b2b.freelance.store');
    Route::post('/orders', [B2BFreelanceController::class, 'createOrder'])->name('b2b.freelance.order.store');
    Route::get('/orders/my', [B2BFreelanceController::class, 'myB2BOrders'])->name('b2b.freelance.orders.my');
    Route::post('/orders/{id}/approve', [B2BFreelanceController::class, 'approveOrder'])->name('b2b.freelance.order.approve');
    Route::post('/orders/{id}/reject', [B2BFreelanceController::class, 'rejectOrder'])->name('b2b.freelance.order.reject');
    Route::post('/{id}/verify-inn', [B2BFreelanceController::class, 'verifyInn'])->middleware('admin')->name('b2b.freelance.verify-inn');
});
