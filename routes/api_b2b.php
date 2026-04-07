<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\B2B\B2BOrderController;
use App\Http\Controllers\B2B\B2BProductController;
use App\Http\Controllers\B2B\B2BStockController;
use App\Http\Controllers\B2B\B2BReportController;
use App\Http\Controllers\B2B\B2BApiKeyController;

/**
 * B2B API Routes — /api/b2b/v1/
 *
 * Middleware pipeline:
 *   1. correlation-id     — inject/validate X-Correlation-ID
 *   2. b2b.api            — validate X-B2B-API-Key, inject business_group into Request
 *   3. tenant             — tenant scoping по b2b_tenant_id
 *   4. throttle:500,1     — 500 req/min (выше чем B2C)
 *
 * Авторизация: X-B2B-API-Key заголовок (НЕ Sanctum token)
 */
Route::prefix('b2b/v1')
    ->middleware(['correlation-id', 'b2b.api', 'throttle:500,1'])
    ->name('b2b.v1.')
    ->group(static function (): void {

        // ─── Продукты (только чтение — оптовые цены) ───────────────
        Route::get('products', [B2BProductController::class, 'index'])->name('products.index');
        Route::get('products/{id}', [B2BProductController::class, 'show'])->name('products.show');

        // ─── Заказы ─────────────────────────────────────────────────
        Route::get('orders', [B2BOrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [B2BOrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{id}', [B2BOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{id}/cancel', [B2BOrderController::class, 'cancel'])->name('orders.cancel');

        // Массовые операции
        Route::post('orders/bulk', [B2BOrderController::class, 'bulkCreate'])->name('orders.bulk');
        Route::post('orders/import', [B2BOrderController::class, 'importExcel'])->name('orders.import');

        // ─── Остатки (только чтение) ─────────────────────────────────
        Route::get('stock', [B2BStockController::class, 'index'])->name('stock.index');
        Route::get('stock/{productId}', [B2BStockController::class, 'show'])->name('stock.show');

        // ─── Отчёты ──────────────────────────────────────────────────
        Route::get('reports/turnover', [B2BReportController::class, 'turnover'])->name('reports.turnover');
        Route::get('reports/credit', [B2BReportController::class, 'credit'])->name('reports.credit');
        Route::get('reports/orders', [B2BReportController::class, 'orders'])->name('reports.orders');

        // ─── Управление API-ключами ──────────────────────────────────
        Route::get('api-keys', [B2BApiKeyController::class, 'index'])->name('api-keys.index');
        Route::post('api-keys', [B2BApiKeyController::class, 'store'])->name('api-keys.store');
        Route::post('api-keys/{id}/rotate', [B2BApiKeyController::class, 'rotate'])->name('api-keys.rotate');
        Route::delete('api-keys/{id}', [B2BApiKeyController::class, 'revoke'])->name('api-keys.revoke');
    });
