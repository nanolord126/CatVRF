<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Fashion\Http\Controllers\FashionStoreController;
use App\Domains\Fashion\Http\Controllers\FashionProductController;
use App\Domains\Fashion\Http\Controllers\FashionOrderController;
use App\Domains\Fashion\Http\Controllers\FashionReviewController;
use App\Domains\Fashion\Http\Controllers\FashionReturnController;
use App\Domains\Fashion\Http\Controllers\FashionWishlistController;
use App\Domains\Fashion\Http\Controllers\FashionOnlineStylistController;
use App\Domains\Fashion\Http\Controllers\FashionFittingRoomController;

Route::prefix('api/fashion')->name('fashion.')->middleware(['api', 'tenant'])->group(function () {
    Route::controller(FashionStoreController::class)->group(function () {
        Route::get('/stores', 'index')->name('stores.index');
        Route::get('/stores/{id}', 'show')->name('stores.show');
        Route::get('/stores/{id}/products', 'products')->name('stores.products');
        Route::get('/stores/{id}/reviews', 'reviews')->name('stores.reviews');
    });

    Route::controller(FashionProductController::class)->group(function () {
        Route::get('/products', 'index')->name('products.index');
        Route::get('/products/{id}', 'show')->name('products.show');
        Route::get('/products/{id}/reviews', 'reviews')->name('products.reviews');
        Route::get('/categories', 'categories')->name('products.categories');
        Route::get('/search', 'search')->name('products.search');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(FashionOnlineStylistController::class)->prefix('stylist')->group(function () {
            Route::get('/mens-style', 'getMensStyle')->name('stylist.mens');
            Route::get('/womens-style', 'getWomensStyle')->name('stylist.womens');
            Route::get('/womens-underwear', 'getWomensUnderwear')->name('stylist.womens-underwear');
            Route::get('/mens-shoes', 'getMensShoes')->name('stylist.mens-shoes');
            Route::get('/womens-shoes', 'getWomensShoes')->name('stylist.womens-shoes');
            Route::get('/childrens-clothing', 'getChildrensClothing')->name('stylist.childrens-clothing');
            Route::get('/childrens-shoes', 'getChildrensShoes')->name('stylist.childrens-shoes');
            Route::get('/scarves', 'getScarves')->name('stylist.scarves');
            Route::get('/headwear', 'getHeadwear')->name('stylist.headwear');
            Route::get('/care-products', 'getCareProducts')->name('stylist.care-products');
            Route::get('/umbrellas', 'getUmbrellas')->name('stylist.umbrellas');
            Route::get('/mens-accessories', 'getMensAccessories')->name('stylist.mens-accessories');
            Route::get('/womens-accessories', 'getWomensAccessories')->name('stylist.womens-accessories');
        });

        Route::controller(FashionFittingRoomController::class)->prefix('fitting')->group(function () {
            Route::post('/check-access', 'checkLingerieAccess')->name('fitting.check-access');
            Route::post('/recommendations', 'getRecommendations')->name('fitting.recommendations');
            Route::post('/measurements', 'saveMeasurements')->name('fitting.save-measurements');
        });

        // ML Service Routes
        Route::prefix('ml')->name('ml.')->group(function () {
            // Color Harmony
            Route::get('/color-harmony', function () {
                $service = app(\Modules\Fashion\Services\ML\FashionColorHarmonyService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                return response()->json($service->getRecommendationsFromBeautyHistory($userId, $tenantId));
            })->name('color-harmony');

            Route::get('/photo-session-outfit/{occasion}', function ($occasion) {
                $service = app(\Modules\Fashion\Services\ML\FashionColorHarmonyService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                return response()->json($service->getPhotoSessionOutfitSuggestions($userId, $tenantId, $occasion));
            })->name('photo-session-outfit');

            // Size Recommendation
            Route::get('/size-recommendation/{productId}', function ($productId) {
                $service = app(\Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                return response()->json($service->calculateIdealSize($userId, $tenantId, $productId));
            })->name('size-recommendation');

            Route::post('/size-feedback/{productId}', function ($productId) {
                $service = app(\Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                $feedback = request()->input('feedback');
                $actualSize = request()->input('actual_size');
                return response()->json(['success' => $service->updateSizeAccuracy($userId, $tenantId, $productId, $actualSize, $feedback)]);
            })->name('size-feedback');

            Route::get('/brand-size-accuracy/{brandId}', function ($brandId) {
                $service = app(\Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService::class);
                $tenantId = request()->get('tenant_id');
                return response()->json($service->getBrandSizeAccuracy($brandId, $tenantId));
            })->name('brand-size-accuracy');

            Route::post('/train-size-model', function () {
                $service = app(\Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService::class);
                $tenantId = request()->get('tenant_id');
                $service->trainSizeModel($tenantId);
                return response()->json(['success' => true]);
            })->name('train-size-model');

            // Cross-vertical Recommendations
            Route::get('/cross-vertical-recommendations', function () {
                $service = app(\Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                $limit = request()->input('limit', 10);
                return response()->json($service->getBeautyToFashionRecommendations($userId, $tenantId, $limit));
            })->name('cross-vertical');

            Route::get('/wardrobe-update-suggestions', function () {
                $service = app(\Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService::class);
                $userId = auth()->check() ? auth()->id() : null;
                $tenantId = request()->get('tenant_id');
                return response()->json($service->getWardrobeUpdateSuggestions($userId, $tenantId));
            })->name('wardrobe-update');
        });

        Route::controller(FashionStoreController::class)->group(function () {
            Route::post('/stores', 'store')->name('stores.store');
            Route::get('/my-store', 'myStore')->name('stores.my');
            Route::patch('/stores/{id}', 'update')->name('stores.update');
            Route::delete('/stores/{id}', 'delete')->name('stores.delete');
        });

        Route::controller(FashionProductController::class)->group(function () {
            Route::post('/products', 'store')->name('products.store');
            Route::patch('/products/{id}', 'update')->name('products.update');
            Route::delete('/products/{id}', 'delete')->name('products.delete');
            Route::patch('/products/{id}/stock', 'updateStock')->name('products.stock');
        });

        Route::controller(FashionOrderController::class)->group(function () {
            Route::get('/my-orders', 'myOrders')->name('orders.my');
            Route::post('/orders', 'store')->name('orders.store');
            Route::get('/orders/{id}', 'show')->name('orders.show');
            Route::patch('/orders/{id}', 'update')->name('orders.update');
            Route::delete('/orders/{id}', 'cancel')->name('orders.cancel');
            Route::get('/orders/{id}/history', 'history')->name('orders.history');
        });

        Route::controller(FashionReviewController::class)->group(function () {
            Route::get('/products/{id}/reviews', 'getProductReviews')->name('reviews.product');
            Route::post('/products/{id}/reviews', 'store')->name('reviews.store');
            Route::patch('/reviews/{id}', 'update')->name('reviews.update');
            Route::delete('/reviews/{id}', 'delete')->name('reviews.delete');
            Route::post('/reviews/{id}/helpful', 'markHelpful')->name('reviews.helpful');
        });

        Route::controller(FashionReturnController::class)->group(function () {
            Route::get('/my-returns', 'myReturns')->name('returns.my');
            Route::post('/orders/{id}/return', 'store')->name('returns.store');
            Route::get('/returns/{id}', 'show')->name('returns.show');
            Route::patch('/returns/{id}', 'update')->name('returns.update');
        });

        Route::controller(FashionWishlistController::class)->group(function () {
            Route::get('/wishlist', 'index')->name('wishlist.index');
            Route::post('/wishlist/{id}', 'add')->name('wishlist.add');
            Route::delete('/wishlist/{id}', 'remove')->name('wishlist.remove');
        });
    });

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::controller(FashionStoreController::class)->group(function () {
            Route::post('/stores/{id}/verify', 'verify')->name('stores.verify');
            Route::get('/stores/all', 'all')->name('stores.all');
            Route::get('/analytics/stores', 'analytics')->name('analytics.stores');
        });

        Route::controller(FashionProductController::class)->group(function () {
            Route::get('/products/all', 'all')->name('products.all');
            Route::get('/analytics/products', 'analytics')->name('analytics.products');
        });

        Route::controller(FashionOrderController::class)->group(function () {
            Route::get('/orders/all', 'all')->name('orders.all');
            Route::patch('/orders/{id}/status', 'updateStatus')->name('orders.status');
            Route::get('/analytics/orders', 'analytics')->name('analytics.orders');
        });

        Route::controller(FashionReturnController::class)->group(function () {
            Route::get('/returns/all', 'all')->name('returns.all');
            Route::patch('/returns/{id}/approve', 'approve')->name('returns.approve');
            Route::patch('/returns/{id}/reject', 'reject')->name('returns.reject');
            Route::get('/analytics/returns', 'analytics')->name('analytics.returns');
        });

        Route::controller(FashionReviewController::class)->group(function () {
            Route::get('/reviews/all', 'all')->name('reviews.all');
            Route::patch('/reviews/{id}/approve', 'approve')->name('reviews.approve');
            Route::delete('/reviews/{id}/reject', 'reject')->name('reviews.reject');
        });
    });
});
