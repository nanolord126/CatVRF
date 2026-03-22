<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * API Routes - Production Ready
 */

// Health check (no auth)
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('api.health');

// API v1 - Authenticated
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        
        // Payment routes
        Route::prefix('payments')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\PaymentController::class, 'store'])
                ->name('v1.payments.store');
            Route::get('{payment}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'show'])
                ->name('v1.payments.show');
            Route::post('{payment}/refund', [\App\Http\Controllers\Api\V1\PaymentController::class, 'refund'])
                ->name('v1.payments.refund');
        });
        
        // Wallet routes
        Route::prefix('wallets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\WalletController::class, 'index'])
                ->name('v1.wallets.index');
            Route::get('{wallet}', [\App\Http\Controllers\Api\V1\WalletController::class, 'show'])
                ->name('v1.wallets.show');
            Route::post('{wallet}/deposit', [\App\Http\Controllers\Api\V1\WalletController::class, 'deposit'])
                ->name('v1.wallets.deposit');
            Route::post('{wallet}/withdraw', [\App\Http\Controllers\Api\V1\WalletController::class, 'withdraw'])
                ->name('v1.wallets.withdraw');
        });
        
        // Promo routes
        Route::prefix('promos')->group(function () {
            Route::post('apply', [\App\Http\Controllers\Api\V1\PromoController::class, 'apply'])
                ->name('v1.promos.apply');
        });
        
        // Search routes
        Route::prefix('search')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SearchController::class, 'index'])
                ->name('v1.search.index');
        });
    });

// API v2 - Future version
Route::prefix('v2')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('payments')->group(function () {
            Route::post('init', [\App\Http\Controllers\Api\V2\PaymentController::class, 'init'])
                ->name('v2.payments.init');
            Route::get('{payment}', [\App\Http\Controllers\Api\V2\PaymentController::class, 'show'])
                ->name('v2.payments.show');
        });
    });

// Webhook routes (IP whitelisted, no auth)
Route::prefix('webhooks')
    ->middleware([\App\Http\Middleware\IpWhitelistMiddleware::class])
    ->group(function () {
        Route::post('tinkoff', [\App\Http\Controllers\Internal\WebhookController::class, 'handleTinkoff'])
            ->name('webhooks.tinkoff');
        Route::post('sber', [\App\Http\Controllers\Internal\WebhookController::class, 'handleSber'])
            ->name('webhooks.sber');
        Route::post('sbp', [\App\Http\Controllers\Internal\WebhookController::class, 'handleSbp'])
            ->name('webhooks.sbp');
    });

// Documentation (public)
Route::prefix('docs')->group(function () {
    Route::get('openapi.json', [\App\Http\Controllers\Api\OpenApiController::class, 'specification'])
        ->name('api.openapi.spec');
    Route::get('swagger', [\App\Http\Controllers\Api\OpenApiController::class, 'ui'])
        ->name('api.swagger.ui');
});

// ─── Channels (посты, подписки, реакции) ─────────────────────────────────────
require __DIR__ . '/channels.api.php';
