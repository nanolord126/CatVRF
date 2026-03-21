<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\RealEstate\Http\Controllers\B2BRealEstateController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/real-estate')->group(function () {
    Route::get('/', [B2BRealEstateController::class, 'storefronts'])->name('b2b.real-estate.index');
    Route::post('/', [B2BRealEstateController::class, 'createStorefront'])->name('b2b.real-estate.store');
    Route::post('/orders', [B2BRealEstateController::class, 'createOrder'])->name('b2b.real-estate.order.store');
    Route::get('/orders/my', [B2BRealEstateController::class, 'myB2BOrders'])->name('b2b.real-estate.orders.my');
    Route::post('/orders/{id}/approve', [B2BRealEstateController::class, 'approveOrder'])->name('b2b.real-estate.order.approve');
    Route::post('/orders/{id}/reject', [B2BRealEstateController::class, 'rejectOrder'])->name('b2b.real-estate.order.reject');
    Route::post('/{id}/verify-inn', [B2BRealEstateController::class, 'verifyInn'])->middleware('admin')->name('b2b.real-estate.verify-inn');
});
