<?php declare(strict_types=1);

use App\Domains\Flowers\Http\Controllers\B2BFlowerController;
use App\Domains\Flowers\Http\Controllers\FlowerDeliveryController;
use App\Domains\Flowers\Http\Controllers\FlowerOrderController;
use App\Domains\Flowers\Http\Controllers\FlowerProductController;
use App\Domains\Flowers\Http\Controllers\FlowerReviewController;
use App\Domains\Flowers\Http\Controllers\FlowerShopController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('flowers')->name('flowers.')->group(function () {
    // Products
    Route::get('/products', [FlowerProductController::class, 'index'])->name('products.index');
    Route::get('/products/{id}', [FlowerProductController::class, 'show'])->name('products.show');
    Route::get('/shops/{shopId}/products', [FlowerProductController::class, 'shopProducts'])->name('products.byShop');
    Route::get('/search', [FlowerProductController::class, 'search'])->name('products.search');
    
    Route::middleware('business')->group(function () {
        Route::post('/products', [FlowerProductController::class, 'store'])->name('products.store');
        Route::put('/products/{id}', [FlowerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [FlowerProductController::class, 'destroy'])->name('products.destroy');
    });

    // Shops
    Route::get('/shops', [FlowerShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/{id}', [FlowerShopController::class, 'show'])->name('shops.show');
    Route::post('/shops', [FlowerShopController::class, 'store'])->middleware('business')->name('shops.store');
    Route::put('/shops/{id}', [FlowerShopController::class, 'update'])->middleware('business')->name('shops.update');
    Route::get('/my-shop', [FlowerShopController::class, 'myShop'])->middleware('business')->name('shops.my');

    // Orders (Consumer)
    Route::post('/orders', [FlowerOrderController::class, 'store'])->name('orders.store');
    Route::get('/my-orders', [FlowerOrderController::class, 'myOrders'])->name('orders.my');
    Route::get('/orders/{id}', [FlowerOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}/cancel', [FlowerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{id}/receipt', [FlowerOrderController::class, 'receipt'])->name('orders.receipt');

    // Deliveries
    Route::get('/deliveries/{id}/track', [FlowerDeliveryController::class, 'track'])->name('deliveries.track');
    Route::get('/my-orders/{orderId}/delivery', [FlowerDeliveryController::class, 'orderDelivery'])->name('deliveries.order');

    Route::middleware('business')->group(function () {
        Route::get('/shop/deliveries', [FlowerDeliveryController::class, 'shopDeliveries'])->name('deliveries.shop');
        Route::put('/deliveries/{id}', [FlowerDeliveryController::class, 'update'])->name('deliveries.update');
        Route::post('/deliveries/{id}/assign', [FlowerDeliveryController::class, 'assign'])->name('deliveries.assign');
    });

    // Reviews
    Route::post('/orders/{orderId}/review', [FlowerReviewController::class, 'store'])->name('reviews.store');
    Route::get('/shops/{shopId}/reviews', [FlowerReviewController::class, 'shopReviews'])->name('reviews.shop');
    Route::put('/reviews/{id}', [FlowerReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{id}', [FlowerReviewController::class, 'destroy'])->name('reviews.destroy');

    // B2B Storefronts (Authenticated via INN)
    Route::middleware('b2b')->prefix('b2b')->name('b2b.')->group(function () {
        // B2B Registration & Management
        Route::post('/register', [B2BFlowerController::class, 'register'])->withoutMiddleware('auth')->name('register');
        Route::get('/profile', [B2BFlowerController::class, 'profile'])->name('profile');
        Route::put('/profile', [B2BFlowerController::class, 'updateProfile'])->name('profile.update');

        // B2B Products & Pricing
        Route::get('/products', [B2BFlowerController::class, 'products'])->name('products');
        Route::get('/products/{id}', [B2BFlowerController::class, 'productDetail'])->name('products.show');
        Route::post('/products/{id}/inquiry', [B2BFlowerController::class, 'productInquiry'])->name('products.inquiry');

        // B2B Orders
        Route::post('/orders', [B2BFlowerController::class, 'createOrder'])->name('orders.create');
        Route::get('/orders', [B2BFlowerController::class, 'listOrders'])->name('orders.list');
        Route::get('/orders/{id}', [B2BFlowerController::class, 'orderDetail'])->name('orders.detail');
        Route::put('/orders/{id}', [B2BFlowerController::class, 'updateOrder'])->name('orders.update');
        Route::put('/orders/{id}/submit', [B2BFlowerController::class, 'submitOrder'])->name('orders.submit');
        Route::put('/orders/{id}/cancel', [B2BFlowerController::class, 'cancelOrder'])->name('orders.cancel');
        Route::get('/orders/{id}/invoice', [B2BFlowerController::class, 'orderInvoice'])->name('orders.invoice');

        // B2B Analytics
        Route::get('/analytics/orders', [B2BFlowerController::class, 'ordersAnalytics'])->name('analytics.orders');
        Route::get('/analytics/spending', [B2BFlowerController::class, 'spendingAnalytics'])->name('analytics.spending');
    });
});

// Admin routes
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/flowers')->name('admin.flowers.')->group(function () {
    Route::get('/shops', [FlowerShopController::class, 'adminList'])->name('shops.list');
    Route::get('/shops/{id}', [FlowerShopController::class, 'adminShow'])->name('shops.show');
    Route::post('/shops/{id}/verify', [FlowerShopController::class, 'verify'])->name('shops.verify');
    Route::delete('/shops/{id}', [FlowerShopController::class, 'adminDestroy'])->name('shops.delete');

    Route::get('/orders', [FlowerOrderController::class, 'adminList'])->name('orders.list');
    Route::get('/orders/{id}', [FlowerOrderController::class, 'adminShow'])->name('orders.show');
    Route::post('/orders/{id}/confirm', [FlowerOrderController::class, 'adminConfirm'])->name('orders.confirm');

    Route::get('/b2b/storefronts', [B2BFlowerController::class, 'adminStorefronts'])->name('b2b.storefronts');
    Route::post('/b2b/storefronts/{id}/verify', [B2BFlowerController::class, 'adminVerifyStorefront'])->name('b2b.verify');
    Route::delete('/b2b/storefronts/{id}', [B2BFlowerController::class, 'adminDeleteStorefront'])->name('b2b.delete');

    Route::get('/analytics', [FlowerShopController::class, 'adminAnalytics'])->name('analytics');
    Route::get('/earnings', [FlowerShopController::class, 'adminEarnings'])->name('earnings');
});
