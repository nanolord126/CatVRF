<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\B2B\AnalyticsController;
use App\Http\Controllers\Api\V1\B2B\BusinessGroupController;
use App\Http\Controllers\Api\V1\B2B\DashboardController;
use App\Http\Controllers\Api\V1\B2B\InventoryController;
use App\Http\Controllers\Api\V1\B2B\MarketplaceController;
use App\Http\Controllers\Api\V1\B2B\NotificationController;
use App\Http\Controllers\Api\V1\B2B\OrderController;
use App\Http\Controllers\Api\V1\B2B\PayoutController;
use App\Http\Controllers\Api\V1\B2B\PricingRuleController;
use App\Http\Controllers\Api\V1\B2B\ProductController;
use App\Http\Controllers\Api\V1\B2B\PromoCampaignController;
use App\Http\Controllers\Api\V1\B2B\ReportController;
use App\Http\Controllers\Api\V1\B2B\SettingsController;
use App\Http\Controllers\Api\V1\B2B\StaffController;
use App\Http\Controllers\Api\V1\B2B\WalletController;
use App\Http\Controllers\Internal\B2BWebhookController;
use App\Http\Middleware\IpWhitelistMiddleware;

/**
 * B2B Marketplace API Routes
 * For business partners selling to other businesses
 * Requires business authentication
 */
Route::prefix('api/v1/b2b')
    ->middleware(['auth:sanctum', 'tenant', 'business.owner'])
    ->group(function () {

        // ========== Dashboard & Analytics ==========
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('b2b.dashboard');
        Route::get('analytics/revenue', [AnalyticsController::class, 'revenue'])
            ->name('b2b.analytics.revenue');
        Route::get('analytics/orders', [AnalyticsController::class, 'orders'])
            ->name('b2b.analytics.orders');
        Route::get('analytics/heatmap', [AnalyticsController::class, 'heatmap'])
            ->name('b2b.analytics.heatmap');

        // ========== Products / Services Management ==========
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/bulk-update', [ProductController::class, 'bulkUpdate'])
            ->name('b2b.products.bulk-update');
        Route::post('products/import', [ProductController::class, 'import'])
            ->name('b2b.products.import');
        Route::post('products/export', [ProductController::class, 'export'])
            ->name('b2b.products.export');

        // ========== Orders Management ==========
        Route::get('orders', [OrderController::class, 'index'])
            ->name('b2b.orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])
            ->name('b2b.orders.show');
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])
            ->name('b2b.orders.confirm');
        Route::post('orders/{order}/reject', [OrderController::class, 'reject'])
            ->name('b2b.orders.reject');
        Route::post('orders/{order}/ship', [OrderController::class, 'ship'])
            ->name('b2b.orders.ship');

        // ========== Inventory Management ==========
        Route::apiResource('inventory', InventoryController::class);
        Route::post('inventory/{item}/reserve', [InventoryController::class, 'reserve'])
            ->name('b2b.inventory.reserve');
        Route::post('inventory/{item}/release', [InventoryController::class, 'release'])
            ->name('b2b.inventory.release');
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])
            ->name('b2b.inventory.low-stock');

        // ========== Pricing & Promotions ==========
        Route::apiResource('pricing-rules', PricingRuleController::class);
        Route::apiResource('promo-campaigns', PromoCampaignController::class);

        // ========== Payouts & Settlements ==========
        Route::get('payouts', [PayoutController::class, 'index'])
            ->name('b2b.payouts.index');
        Route::get('payouts/{payout}', [PayoutController::class, 'show'])
            ->name('b2b.payouts.show');
        Route::post('payouts/{payout}/claim', [PayoutController::class, 'claim'])
            ->name('b2b.payouts.claim');
        Route::get('payouts/settlement/history', [PayoutController::class, 'history'])
            ->name('b2b.payouts.history');

        // ========== Wallet & Balance ==========
        Route::get('wallet', [WalletController::class, 'show'])
            ->name('b2b.wallet.show');
        Route::get('wallet/transactions', [WalletController::class, 'transactions'])
            ->name('b2b.wallet.transactions');
        Route::post('wallet/withdraw', [WalletController::class, 'withdraw'])
            ->name('b2b.wallet.withdraw');

        // ========== Business Group / Branches ==========
        Route::apiResource('business-groups', BusinessGroupController::class);
        Route::post('business-groups/{group}/switch', [BusinessGroupController::class, 'switch'])
            ->name('b2b.business-groups.switch');

        // ========== Staff Management ==========
        Route::apiResource('staff', StaffController::class);
        Route::post('staff/{staff}/roles', [StaffController::class, 'updateRoles'])
            ->name('b2b.staff.update-roles')
            ->middleware('VpnProtectionMiddleware');

        // ========== Settings ==========
        Route::get('settings', [SettingsController::class, 'show'])
            ->name('b2b.settings.show');
        Route::put('settings', [SettingsController::class, 'update'])
            ->name('b2b.settings.update')
            ->middleware('VpnProtectionMiddleware');
        Route::post('settings/api-keys', [SettingsController::class, 'generateApiKey'])
            ->name('b2b.settings.api-keys');

        // ========== Notifications & Alerts ==========
        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('b2b.notifications.index');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('b2b.notifications.read');

        // ========== Reports ==========
        Route::get('reports/sales', [ReportController::class, 'sales'])
            ->name('b2b.reports.sales');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])
            ->name('b2b.reports.inventory');
        Route::get('reports/customers', [ReportController::class, 'customers'])
            ->name('b2b.reports.customers');
        Route::post('reports/{report}/export', [ReportController::class, 'export'])
            ->name('b2b.reports.export');
    });

// B2B Public Marketplace (for buyers)
Route::prefix('api/v1/b2b/marketplace')
    ->middleware(['api', 'tenant'])
    ->group(function () {
        Route::get('suppliers', [MarketplaceController::class, 'suppliers'])
            ->name('b2b.marketplace.suppliers');
        Route::get('suppliers/{supplier}', [MarketplaceController::class, 'supplierDetail'])
            ->name('b2b.marketplace.supplier-detail');
        Route::get('products', [MarketplaceController::class, 'products'])
            ->name('b2b.marketplace.products');
        Route::get('products/{product}', [MarketplaceController::class, 'productDetail'])
            ->name('b2b.marketplace.product-detail');
        Route::get('categories', [MarketplaceController::class, 'categories'])
            ->name('b2b.marketplace.categories');
    });

// B2B Webhook endpoints
Route::prefix('webhooks/b2b')
    ->middleware([IpWhitelistMiddleware::class])
    ->group(function () {
        Route::post('order-status', [B2BWebhookController::class, 'orderStatus'])
            ->name('webhooks.b2b.order-status');
        Route::post('inventory-sync', [B2BWebhookController::class, 'inventorySync'])
            ->name('webhooks.b2b.inventory-sync');
        Route::post('payout-confirmation', [B2BWebhookController::class, 'payoutConfirmation'])
            ->name('webhooks.b2b.payout-confirmation');
    });
