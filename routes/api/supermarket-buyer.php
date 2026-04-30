<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Supermarket\SupermarketCartController;
use App\Http\Controllers\Supermarket\SupermarketCheckoutController;
use App\Http\Controllers\Supermarket\SupermarketTrackingController;
use App\Http\Controllers\Supermarket\SupermarketRecommendationController;
use App\Http\Controllers\Api\Supermarket\BuyerDashboardController;
use App\Http\Controllers\Api\Supermarket\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Supermarket Buyer API Routes
|--------------------------------------------------------------------------
|
| API endpoints for buyer-facing Supermarket functionality:
| - Cart management with 20-minute reservation
| - One Page Checkout flow
| - Real-time order tracking
| - AI recommendations
| - Buyer Dashboard (Home, Orders, Subscriptions, Profile)
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Cart Routes
    Route::prefix('supermarket/cart')->group(function () {
        Route::get('/', [SupermarketCartController::class, 'index']);
        Route::post('/add', [SupermarketCartController::class, 'add']);
        Route::patch('/{itemId}', [SupermarketCartController::class, 'update']);
        Route::delete('/{itemId}', [SupermarketCartController::class, 'remove']);
        Route::delete('/', [SupermarketCartController::class, 'clear']);
    });

    // Checkout Routes
    Route::prefix('supermarket/checkout')->group(function () {
        Route::get('/delivery-slots', [SupermarketCheckoutController::class, 'getDeliverySlots']);
        Route::post('/', [SupermarketCheckoutController::class, 'process']);
    });

    // Tracking Routes
    Route::prefix('supermarket/tracking')->group(function () {
        Route::get('/{orderUuid}', [SupermarketTrackingController::class, 'show']);
    });

    // Recommendations Routes
    Route::prefix('supermarket/recommendations')->group(function () {
        Route::get('/ai', [SupermarketRecommendationController::class, 'ai']);
        Route::get('/cross-sell', [SupermarketRecommendationController::class, 'crossSell']);
    });

    // Buyer Dashboard Routes
    Route::prefix('supermarket/dashboard')->group(function () {
        Route::get('/', [BuyerDashboardController::class, 'index'])->name('supermarket.dashboard.index');
        
        Route::get('/orders', [BuyerDashboardController::class, 'orders'])->name('supermarket.dashboard.orders');
        Route::get('/orders/{uuid}', [BuyerDashboardController::class, 'order'])->name('supermarket.dashboard.order.show');
        
        Route::get('/subscriptions', [BuyerDashboardController::class, 'subscriptions'])->name('supermarket.dashboard.subscriptions');
        Route::post('/subscriptions/{id}/pause', [BuyerDashboardController::class, 'pauseSubscription'])->name('supermarket.dashboard.subscriptions.pause');
        Route::post('/subscriptions/{id}/resume', [BuyerDashboardController::class, 'resumeSubscription'])->name('supermarket.dashboard.subscriptions.resume');
        Route::post('/subscriptions/{id}/cancel', [BuyerDashboardController::class, 'cancelSubscription'])->name('supermarket.dashboard.subscriptions.cancel');
        Route::post('/subscriptions/{id}/skip', [BuyerDashboardController::class, 'skipNextDelivery'])->name('supermarket.dashboard.subscriptions.skip');
        
        Route::get('/profile', [BuyerDashboardController::class, 'profile'])->name('supermarket.dashboard.profile');
    });

    // Subscription Management Routes
    Route::prefix('supermarket/subscriptions')->group(function () {
        Route::post('/', [SubscriptionController::class, 'create'])->name('supermarket.subscriptions.create');
        Route::get('/{id}', [SubscriptionController::class, 'show'])->name('supermarket.subscriptions.show');
        Route::patch('/{id}', [SubscriptionController::class, 'update'])->name('supermarket.subscriptions.update');
    });
});
