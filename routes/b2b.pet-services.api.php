<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\PetServices\Http\Controllers\B2BPetController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/pet-services')->group(function () {
    Route::get('/', [B2BPetController::class, 'storefronts'])->name('b2b.pet-services.index');
    Route::post('/', [B2BPetController::class, 'createStorefront'])->name('b2b.pet-services.store');
    Route::post('/orders', [B2BPetController::class, 'createOrder'])->name('b2b.pet-services.order.store');
    Route::get('/orders/my', [B2BPetController::class, 'myB2BOrders'])->name('b2b.pet-services.orders.my');
    Route::post('/orders/{id}/approve', [B2BPetController::class, 'approveOrder'])->name('b2b.pet-services.order.approve');
    Route::post('/orders/{id}/reject', [B2BPetController::class, 'rejectOrder'])->name('b2b.pet-services.order.reject');
    Route::post('/{id}/verify-inn', [B2BPetController::class, 'verifyInn'])->middleware('admin')->name('b2b.pet-services.verify-inn');
});
