<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Fashion\Http\Controllers\FashionStyleConstructorController;
use App\Domains\Fashion\Http\Controllers\FashionCategorizationController;
use App\Domains\Fashion\Http\Controllers\FashionAdvancedServicesController;
use App\Domains\Fashion\Http\Controllers\FashionOnlineStylistController;

/**
 * Fashion AI Style Constructor API Routes
 * PRODUCTION MANDATORY — канон CatVRF 2026
 */
Route::middleware(['auth:sanctum'])->prefix('fashion/ai')->group(function () {
    $controller = FashionStyleConstructorController::class;

    // AI Style Analysis
    Route::post('/analyze', [$controller, 'analyzeAndRecommend'])
        ->name('fashion.ai.analyze');

    // Virtual Try-On
    Route::post('/virtual-try-on', [$controller, 'virtualTryOn'])
        ->name('fashion.ai.virtual-try-on');

    // Dynamic Pricing
    Route::post('/dynamic-pricing', [$controller, 'applyDynamicPricing'])
        ->name('fashion.ai.dynamic-pricing');

    // WebRTC Stylist Sessions
    Route::post('/webrtc/session', [$controller, 'initiateWebRTCSession'])
        ->name('fashion.ai.webrtc.initiate');

    Route::get('/webrtc/sessions', [$controller, 'getWebRTCSessionHistory'])
        ->name('fashion.ai.webrtc.history');

    // AR Preview
    Route::get('/ar-preview/{designId}/{productId}', [$controller, 'getARPreview'])
        ->name('fashion.ai.ar-preview');

    // Loyalty & Gamification
    Route::post('/loyalty/reward', [$controller, 'processLoyaltyReward'])
        ->name('fashion.ai.loyalty.reward');

    Route::get('/loyalty/balance', [$controller, 'getLoyaltyBalance'])
        ->name('fashion.ai.loyalty.balance');

    Route::get('/loyalty/nft-avatars', [$controller, 'getUserNFTAvatars'])
        ->name('fashion.ai.loyalty.nft-avatars');

    // Split Payment
    Route::post('/split-payment', [$controller, 'processSplitPayment'])
        ->name('fashion.ai.split-payment');
});

/**
 * Fashion Categorization & Filtering API Routes
 * PRODUCTION MANDATORY — канон CatVRF 2026
 */
Route::middleware(['auth:sanctum'])->prefix('fashion')->group(function () {
    $controller = FashionCategorizationController::class;

    // Product Categorization
    Route::post('/categorize', [$controller, 'autoCategorize'])
        ->name('fashion.categorize');

    Route::post('/categorize/bulk', [$controller, 'bulkRecategorize'])
        ->name('fashion.categorize.bulk');

    Route::get('/categories/hierarchy', [$controller, 'getCategoryHierarchy'])
        ->name('fashion.categories.hierarchy');

    Route::get('/categories/suggestions', [$controller, 'getCategorySuggestions'])
        ->name('fashion.categories.suggestions');

    // Product Filtering
    Route::post('/products/filter', [$controller, 'filterProducts'])
        ->name('fashion.products.filter');

    Route::get('/filters/available', [$controller, 'getAvailableFilters'])
        ->name('fashion.filters.available');

    Route::post('/filters/preferences', [$controller, 'saveFilterPreferences'])
        ->name('fashion.filters.preferences');

    Route::get('/filters/recommendations', [$controller, 'getFilterRecommendations'])
        ->name('fashion.filters.recommendations');

    // User Pattern Memory
    Route::post('/memory/interaction', [$controller, 'recordInteraction'])
        ->name('fashion.memory.interaction');

    Route::get('/memory/patterns', [$controller, 'getMemoryPatterns'])
        ->name('fashion.memory.patterns');

    Route::get('/memory/predict', [$controller, 'predictNextAction'])
        ->name('fashion.memory.predict');

    Route::get('/memory/recommendations', [$controller, 'getMemoryRecommendations'])
        ->name('fashion.memory.recommendations');

    Route::get('/memory/export', [$controller, 'exportMemoryData'])
        ->name('fashion.memory.export');
});

/**
 * Fashion Advanced Services API Routes
 * PRODUCTION MANDATORY — канон CatVRF 2026
 */
Route::middleware(['auth:sanctum'])->prefix('fashion/advanced')->group(function () {
    $controller = FashionAdvancedServicesController::class;

    // Collaborative Filtering
    Route::get('/recommendations', [$controller, 'getRecommendations'])
        ->name('fashion.advanced.recommendations');

    // Social Media Trends
    Route::post('/trends/collect', [$controller, 'collectTrends'])
        ->name('fashion.advanced.trends.collect');

    Route::post('/trends/analyze', [$controller, 'analyzeProductTrends'])
        ->name('fashion.advanced.trends.analyze');

    // Review Moderation
    Route::post('/reviews/moderate', [$controller, 'moderateReview'])
        ->name('fashion.advanced.reviews.moderate');

    Route::get('/reviews/flagged', [$controller, 'getFlaggedReviews'])
        ->name('fashion.advanced.reviews.flagged');

    // Visual Search
    Route::post('/visual-search', [$controller, 'searchByImage'])
        ->name('fashion.advanced.visual-search');

    Route::post('/visual-search/index', [$controller, 'indexProductForSearch'])
        ->name('fashion.advanced.visual-search.index');

    // Size Recommendation
    Route::post('/size/recommend', [$controller, 'recommendSize'])
        ->name('fashion.advanced.size.recommend');

    Route::post('/size/profile', [$controller, 'updateSizeProfile'])
        ->name('fashion.advanced.size.profile');

    // Inventory Forecasting
    Route::post('/inventory/forecast', [$controller, 'forecastDemand'])
        ->name('fashion.advanced.inventory.forecast');

    Route::get('/inventory/reorder', [$controller, 'getReorderRecommendations'])
        ->name('fashion.advanced.inventory.reorder');

    Route::get('/inventory/out-of-stock', [$controller, 'getOutOfStockStats'])
        ->name('fashion.advanced.inventory.out-of-stock');

    // A/B Price Testing
    Route::post('/ab-price/create', [$controller, 'createPriceTest'])
        ->name('fashion.advanced.ab-price.create');

    Route::get('/ab-price/results', [$controller, 'getPriceTestResults'])
        ->name('fashion.advanced.ab-price.results');

    Route::post('/ab-price/stop', [$controller, 'stopPriceTest'])
        ->name('fashion.advanced.ab-price.stop');

    // Email Campaigns
    Route::post('/email/create', [$controller, 'createCampaign'])
        ->name('fashion.advanced.email.create');

    Route::post('/email/send', [$controller, 'sendCampaign'])
        ->name('fashion.advanced.email.send');

    Route::get('/email/stats', [$controller, 'getCampaignStats'])
        ->name('fashion.advanced.email.stats');
});

/**
 * Fashion Online Stylist API Routes
 * PRODUCTION MANDATORY — канон CatVRF 2026
 */
Route::middleware(['auth:sanctum'])->prefix('fashion/stylist')->group(function () {
    $controller = FashionOnlineStylistController::class;

    Route::post('/consultation', [$controller, 'getStyleConsultation'])
        ->name('fashion.stylist.consultation');

    Route::get('/mens-style', [$controller, 'getMensStyle'])
        ->name('fashion.stylist.mens');

    Route::get('/womens-style', [$controller, 'getWomensStyle'])
        ->name('fashion.stylist.womens');

    Route::get('/womens-underwear', [$controller, 'getWomensUnderwear'])
        ->name('fashion.stylist.womens-underwear');

    Route::get('/mens-shoes', [$controller, 'getMensShoes'])
        ->name('fashion.stylist.mens-shoes');

    Route::get('/womens-shoes', [$controller, 'getWomensShoes'])
        ->name('fashion.stylist.womens-shoes');

    Route::get('/childrens-clothing', [$controller, 'getChildrensClothing'])
        ->name('fashion.stylist.childrens-clothing');

    Route::get('/childrens-shoes', [$controller, 'getChildrensShoes'])
        ->name('fashion.stylist.childrens-shoes');
});
