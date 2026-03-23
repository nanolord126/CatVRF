<?php

declare(strict_types=1);

use App\Domains\Bloggers\Http\Controllers\{
    StreamController,
    ProductController,
    OrderController,
    ChatController,
    GiftController,
    StatisticsController,
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // ==================== STREAMS ====================
    Route::prefix('streams')->group(function () {
        
        // Public endpoints (any authenticated user)
        Route::get('/', [StreamController::class, 'index']);
        Route::get('{room_id}', [StreamController::class, 'show']);
        Route::post('{room_id}/viewers', [StreamController::class, 'updateViewers']);
        Route::get('{room_id}/statistics', [StreamController::class, 'getStatistics']);
        
        // Blogger endpoints (only for verified bloggers)
        Route::middleware(['can:create,stream'])->group(function () {
            Route::post('/', [StreamController::class, 'store']);
            Route::get('/my', [StreamController::class, 'getBloggerStreams']);
            Route::post('{room_id}/start', [StreamController::class, 'start']);
            Route::post('{room_id}/end', [StreamController::class, 'end']);
        });
    });

    // ==================== PRODUCTS ====================
    Route::prefix('streams/{room_id}/products')->group(function () {
        
        // Public endpoints
        Route::get('/', [ProductController::class, 'getPinned']);
        
        // Blogger endpoints
        Route::middleware(['can:create,stream'])->group(function () {
            Route::post('/', [ProductController::class, 'add']);
            Route::post('{product_id}/pin', [ProductController::class, 'pin']);
            Route::post('{product_id}/unpin', [ProductController::class, 'unpin']);
        });
    });

    // ==================== ORDERS ====================
    Route::prefix('orders')->group(function () {
        
        // User endpoints
        Route::post('/', [OrderController::class, 'store']);
        Route::get('{order_id}', [OrderController::class, 'show']);
        Route::get('/', [OrderController::class, 'getUserOrders']);
        Route::post('{order_id}/confirm-payment', [OrderController::class, 'confirmPayment']);
        Route::post('{order_id}/cancel', [OrderController::class, 'cancel']);
    });

    // ==================== CHAT ====================
    Route::prefix('streams/{room_id}/chat')->group(function () {
        
        // Public endpoints
        Route::get('/', [ChatController::class, 'getMessages']);
        
        // Authenticated endpoints
        Route::post('/', [ChatController::class, 'send']);
        Route::delete('{message_id}', [ChatController::class, 'delete']);
        
        // Blogger endpoints
        Route::middleware(['can:create,stream'])->group(function () {
            Route::post('{message_id}/pin', [ChatController::class, 'pin']);
        });
    });

    // ==================== GIFTS ====================
    Route::prefix('gifts')->group(function () {
        
        // Public endpoints
        Route::post('/streams/{room_id}/send', [GiftController::class, 'send']);
        Route::get('{gift_id}/status', [GiftController::class, 'getStatus']);
        Route::get('/user/received', [GiftController::class, 'getUserGifts']);
        Route::get('/streams/{room_id}', [GiftController::class, 'getStreamGifts']);
        
        // Authenticated user endpoints
        Route::post('{gift_id}/upgrade', [GiftController::class, 'upgrade']);
        Route::post('{gift_id}/retry-minting', [GiftController::class, 'retryMinting']);
    });

    // ==================== STATISTICS ====================
    Route::prefix('statistics')->group(function () {
        
        // Public endpoints
        Route::get('/blogger/me', [StatisticsController::class, 'getBloggerStats']);
        Route::get('/leaderboard', [StatisticsController::class, 'getLeaderboard']);
        
        // Stream statistics
        Route::get('/streams/{room_id}', [StatisticsController::class, 'getStreamStats']);
        
        // Admin endpoints
        Route::middleware(['can:viewAny,stream'])->group(function () {
            Route::get('/platform', [StatisticsController::class, 'getPlatformStats']);
        });
    });
});
