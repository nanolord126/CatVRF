<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\B2B\B2BOrderController;
use App\Http\Controllers\B2B\B2BProductController;
use App\Http\Controllers\B2B\B2BStockController;
use App\Http\Controllers\B2B\B2BReportController;
use App\Http\Controllers\B2B\B2BApiKeyController;
use App\Http\Controllers\B2B\B2BCartController;
use App\Http\Controllers\B2B\B2BDocumentController;
use App\Http\Controllers\B2B\B2BCompanyController;

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

        // ─── Корзина ─────────────────────────────────────────────────
        Route::get('cart', [B2BCartController::class, 'index'])->name('cart.index');
        Route::post('cart/items', [B2BCartController::class, 'addItem'])->name('cart.add');
        Route::put('cart/items/{id}', [B2BCartController::class, 'updateItem'])->name('cart.update');
        Route::delete('cart/items/{id}', [B2BCartController::class, 'removeItem'])->name('cart.remove');
        Route::delete('cart', [B2BCartController::class, 'clear'])->name('cart.clear');

        // ─── Документы ────────────────────────────────────────────────
        Route::get('documents', [B2BDocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [B2BDocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{id}', [B2BDocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{id}/sign', [B2BDocumentController::class, 'sign'])->name('documents.sign');
        Route::delete('documents/{id}', [B2BDocumentController::class, 'destroy'])->name('documents.destroy');

        // ─── Компания ─────────────────────────────────────────────────
        Route::get('company', [B2BCompanyController::class, 'show'])->name('company.show');
        Route::put('company', [B2BCompanyController::class, 'update'])->name('company.update');
        Route::get('company/branches', [B2BCompanyController::class, 'branches'])->name('company.branches');
        Route::post('company/branches', [B2BCompanyController::class, 'createBranch'])->name('company.branches.create');
        Route::put('company/branches/{id}', [B2BCompanyController::class, 'updateBranch'])->name('company.branches.update');
        Route::delete('company/branches/{id}', [B2BCompanyController::class, 'deleteBranch'])->name('company.branches.delete');
        Route::get('company/team', [B2BCompanyController::class, 'team'])->name('company.team');
        Route::post('company/team', [B2BCompanyController::class, 'addTeamMember'])->name('company.team.add');
        Route::put('company/team/{id}', [B2BCompanyController::class, 'updateTeamMember'])->name('company.team.update');
        Route::delete('company/team/{id}', [B2BCompanyController::class, 'deleteTeamMember'])->name('company.team.delete');
    });
