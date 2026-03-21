<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Courses\Http\Controllers\B2BCourseController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/b2b/courses')->group(function () {
    Route::get('/', [B2BCourseController::class, 'storefronts'])->name('b2b.courses.index');
    Route::post('/', [B2BCourseController::class, 'createStorefront'])->name('b2b.courses.store');
    Route::post('/orders', [B2BCourseController::class, 'createOrder'])->name('b2b.courses.order.store');
    Route::get('/orders/my', [B2BCourseController::class, 'myB2BOrders'])->name('b2b.courses.orders.my');
    Route::post('/orders/{id}/approve', [B2BCourseController::class, 'approveOrder'])->name('b2b.courses.order.approve');
    Route::post('/orders/{id}/reject', [B2BCourseController::class, 'rejectOrder'])->name('b2b.courses.order.reject');
    Route::post('/{id}/verify-inn', [B2BCourseController::class, 'verifyInn'])->middleware('admin')->name('b2b.courses.verify-inn');
});
