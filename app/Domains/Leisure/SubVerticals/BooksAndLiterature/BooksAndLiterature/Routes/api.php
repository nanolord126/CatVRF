<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;
use App\Domains\BooksAndLiterature\Http\Controllers\B2BBookController;
use App\Domains\BooksAndLiterature\Http\Controllers\BookController;

Route::prefix('books-and-literature')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [BookController::class, 'index']);
            Route::post('/', [BookController::class, 'store']);
            Route::get('/{id}', [BookController::class, 'show']);
            Route::put('/{id}', [BookController::class, 'update']);
            Route::delete('/{id}', [BookController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BBookController::class, 'catalog']);
                Route::post('/bulk-order', [B2BBookController::class, 'bulkOrder']);
            });
    });
