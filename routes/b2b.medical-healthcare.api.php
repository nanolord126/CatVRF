<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Medical\MedicalHealthcare\Http\Controllers\B2BMedicalController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/medical-healthcare')->group(function () {
    Route::get('/', [B2BMedicalController::class, 'storefronts'])->name('b2b.medical-healthcare.index');
    Route::post('/', [B2BMedicalController::class, 'createStorefront'])->name('b2b.medical-healthcare.store');
    Route::post('/orders', [B2BMedicalController::class, 'createOrder'])->name('b2b.medical-healthcare.order.store');
    Route::get('/orders/my', [B2BMedicalController::class, 'myB2BOrders'])->name('b2b.medical-healthcare.orders.my');
    Route::post('/orders/{id}/approve', [B2BMedicalController::class, 'approveOrder'])->name('b2b.medical-healthcare.order.approve');
    Route::post('/orders/{id}/reject', [B2BMedicalController::class, 'rejectOrder'])->name('b2b.medical-healthcare.order.reject');
    Route::post('/{id}/verify-inn', [B2BMedicalController::class, 'verifyInn'])->middleware('admin')->name('b2b.medical-healthcare.verify-inn');
});
