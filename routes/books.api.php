<?php

declare(strict_types=1);

use App\Domains\Books\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('books')->group(function (): void {
    Route::get('/',         [BookController::class, 'index']);
    Route::get('{id}',      [BookController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::get('recommendations',  [BookController::class, 'recommendations']);
        Route::post('orders',          [BookController::class, 'order']);
        Route::get('my-orders',        [BookController::class, 'myOrders']);
    });
});
