<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * B2B Marketplace API Routes
 * For business partners selling to other businesses
 * Requires business authentication
 */

Route::prefix('api/v1/b2b')
    ->middleware(['auth:sanctum', 'tenant', 'business.owner'])
    ->group(function () {
        
        // ========== Dashboard & Analytics ==========
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\B2B\DashboardController::class, 'index'])
            ->name('b2b.dashboard');
        Route::get('analytics/revenue', [\App\Http\Controllers\Api\V1\B2B\AnalyticsController::class, 'revenue'])
            ->name('b2b.analytics.revenue');
        Route::get('analytics/orders', [\App\Http\Controllers\Api\V1\B2B\AnalyticsController::class, 'orders'])
            ->name('b2b.analytics.orders');
        Route::get('analytics/heatmap', [\App\Http\Controllers\Api\V1\B2B\AnalyticsController::class, 'heatmap'])
            ->name('b2b.analytics.heatmap');

        // ========== Products / Services Management ==========
        Route::apiResource('products', \App\Http\Controllers\Api\V1\B2B\ProductController::class);
        Route::post('products/{product}/bulk-update', [\App\Http\Controllers\Api\V1\B2B\ProductController::class, 'bulkUpdate'])
            ->name('b2b.products.bulk-update');
        Route::post('products/import', [\App\Http\Controllers\Api\V1\B2B\ProductController::class, 'import'])
            ->name('b2b.products.import');
        Route::post('products/export', [\App\Http\Controllers\Api\V1\B2B\ProductController::class, 'export'])
            ->name('b2b.products.export');

        // ========== Orders Management ==========
        Route::get('orders', [\App\Http\Controllers\Api\V1\B2B\OrderController::class, 'index'])
            ->name('b2b.orders.index');
        Route::get('orders/{order}', [\App\Http\Controllers\Api\V1\B2B\OrderController::class, 'show'])
            ->name('b2b.orders.show');
        Route::post('orders/{order}/confirm', [\App\Http\Controllers\Api\V1\B2B\OrderController::class, 'confirm'])
            ->name('b2b.orders.confirm');
        Route::post('orders/{order}/reject', [\App\Http\Controllers\Api\V1\B2B\OrderController::class, 'reject'])
            ->name('b2b.orders.reject');
        Route::post('orders/{order}/ship', [\App\Http\Controllers\Api\V1\B2B\OrderController::class, 'ship'])
            ->name('b2b.orders.ship');

        // ========== Inventory Management ==========
        Route::apiResource('inventory', \App\Http\Controllers\Api\V1\B2B\InventoryController::class);
        Route::post('inventory/{item}/reserve', [\App\Http\Controllers\Api\V1\B2B\InventoryController::class, 'reserve'])
            ->name('b2b.inventory.reserve');
        Route::post('inventory/{item}/release', [\App\Http\Controllers\Api\V1\B2B\InventoryController::class, 'release'])
            ->name('b2b.inventory.release');
        Route::get('inventory/low-stock', [\App\Http\Controllers\Api\V1\B2B\InventoryController::class, 'lowStock'])
            ->name('b2b.inventory.low-stock');

        // ========== Pricing & Promotions ==========
        Route::apiResource('pricing-rules', \App\Http\Controllers\Api\V1\B2B\PricingRuleController::class);
        Route::apiResource('promo-campaigns', \App\Http\Controllers\Api\V1\B2B\PromoCampaignController::class);

        // ========== Payouts & Settlements ==========
        Route::get('payouts', [\App\Http\Controllers\Api\V1\B2B\PayoutController::class, 'index'])
            ->name('b2b.payouts.index');
        Route::get('payouts/{payout}', [\App\Http\Controllers\Api\V1\B2B\PayoutController::class, 'show'])
            ->name('b2b.payouts.show');
        Route::post('payouts/{payout}/claim', [\App\Http\Controllers\Api\V1\B2B\PayoutController::class, 'claim'])
            ->name('b2b.payouts.claim');
        Route::get('payouts/settlement/history', [\App\Http\Controllers\Api\V1\B2B\PayoutController::class, 'history'])
            ->name('b2b.payouts.history');

        // ========== Wallet & Balance ==========
        Route::get('wallet', [\App\Http\Controllers\Api\V1\B2B\WalletController::class, 'show'])
            ->name('b2b.wallet.show');
        Route::get('wallet/transactions', [\App\Http\Controllers\Api\V1\B2B\WalletController::class, 'transactions'])
            ->name('b2b.wallet.transactions');
        Route::post('wallet/withdraw', [\App\Http\Controllers\Api\V1\B2B\WalletController::class, 'withdraw'])
            ->name('b2b.wallet.withdraw');

        // ========== Business Group / Branches ==========
        Route::apiResource('business-groups', \App\Http\Controllers\Api\V1\B2B\BusinessGroupController::class);
        Route::post('business-groups/{group}/switch', [\App\Http\Controllers\Api\V1\B2B\BusinessGroupController::class, 'switch'])
            ->name('b2b.business-groups.switch');

        // ========== Staff Management ==========
        Route::apiResource('staff', \App\Http\Controllers\Api\V1\B2B\StaffController::class);
        Route::post('staff/{staff}/roles', [\App\Http\Controllers\Api\V1\B2B\StaffController::class, 'updateRoles'])
            ->name('b2b.staff.update-roles');

        // ========== Settings ==========
        Route::get('settings', [\App\Http\Controllers\Api\V1\B2B\SettingsController::class, 'show'])
            ->name('b2b.settings.show');
        Route::put('settings', [\App\Http\Controllers\Api\V1\B2B\SettingsController::class, 'update'])
            ->name('b2b.settings.update');
        Route::post('settings/api-keys', [\App\Http\Controllers\Api\V1\B2B\SettingsController::class, 'generateApiKey'])
            ->name('b2b.settings.api-keys');

        // ========== Notifications & Alerts ==========
        Route::get('notifications', [\App\Http\Controllers\Api\V1\B2B\NotificationController::class, 'index'])
            ->name('b2b.notifications.index');
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\V1\B2B\NotificationController::class, 'markAsRead'])
            ->name('b2b.notifications.read');

        // ========== Reports ==========
        Route::get('reports/sales', [\App\Http\Controllers\Api\V1\B2B\ReportController::class, 'sales'])
            ->name('b2b.reports.sales');
        Route::get('reports/inventory', [\App\Http\Controllers\Api\V1\B2B\ReportController::class, 'inventory'])
            ->name('b2b.reports.inventory');
        Route::get('reports/customers', [\App\Http\Controllers\Api\V1\B2B\ReportController::class, 'customers'])
            ->name('b2b.reports.customers');
        Route::post('reports/{report}/export', [\App\Http\Controllers\Api\V1\B2B\ReportController::class, 'export'])
            ->name('b2b.reports.export');
    });

// B2B Public Marketplace (for buyers)
Route::prefix('api/v1/b2b/marketplace')
    ->middleware(['api', 'tenant'])
    ->group(function () {
        Route::get('suppliers', [\App\Http\Controllers\Api\V1\B2B\MarketplaceController::class, 'suppliers'])
            ->name('b2b.marketplace.suppliers');
        Route::get('suppliers/{supplier}', [\App\Http\Controllers\Api\V1\B2B\MarketplaceController::class, 'supplierDetail'])
            ->name('b2b.marketplace.supplier-detail');
        Route::get('products', [\App\Http\Controllers\Api\V1\B2B\MarketplaceController::class, 'products'])
            ->name('b2b.marketplace.products');
        Route::get('products/{product}', [\App\Http\Controllers\Api\V1\B2B\MarketplaceController::class, 'productDetail'])
            ->name('b2b.marketplace.product-detail');
        Route::get('categories', [\App\Http\Controllers\Api\V1\B2B\MarketplaceController::class, 'categories'])
            ->name('b2b.marketplace.categories');
    });

// B2B Webhook endpoints
Route::prefix('webhooks/b2b')
    ->middleware([\App\Http\Middleware\IpWhitelistMiddleware::class])
    ->group(function () {
        Route::post('order-status', [\App\Http\Controllers\Internal\B2BWebhookController::class, 'orderStatus'])
            ->name('webhooks.b2b.order-status');
        Route::post('inventory-sync', [\App\Http\Controllers\Internal\B2BWebhookController::class, 'inventorySync'])
            ->name('webhooks.b2b.inventory-sync');
        Route::post('payout-confirmation', [\App\Http\Controllers\Internal\B2BWebhookController::class, 'payoutConfirmation'])
            ->name('webhooks.b2b.payout-confirmation');
    });
