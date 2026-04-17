<?php

declare(strict_types=1);

use App\Domains\Electronics\Http\Controllers\ElectronicProductController;
use App\Domains\Electronics\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('electronics')->group(function (): void {
    Route::get('/',             [ElectronicProductController::class, 'index']);
    Route::get('compare',       [ElectronicProductController::class, 'compare']);
    Route::get('{id}',          [ElectronicProductController::class, 'show']);

    // Search endpoints
    Route::prefix('search')->group(function (): void {
        Route::get('/',              [SearchController::class, 'search']);
        Route::get('filters',        [SearchController::class, 'getFilters']);
        Route::get('suggestions',    [SearchController::class, 'getSuggestions']);
        Route::get('popular',        [SearchController::class, 'getPopularSearches']);
    });

    // Filter configuration endpoints
    Route::prefix('types')->group(function (): void {
        Route::get('/',                 [FilterConfigController::class, 'getAllTypes']);
        Route::get('popular',           [FilterConfigController::class, 'getPopularTypes']);
        Route::get('hierarchy',         [FilterConfigController::class, 'getTypeHierarchy']);
        Route::get('{type}/config',     [FilterConfigController::class, 'getFilterConfig']);
        Route::get('{type}/patterns',   [FilterConfigController::class, 'getSearchPatterns']);
        Route::get('{type}/suggestions', [FilterConfigController::class, 'getTypeSuggestions']);
        Route::post('{type}/validate', [FilterConfigController::class, 'validateFilters']);
    });

    // Analytics endpoints
    Route::prefix('analytics')->group(function (): void {
        Route::get('/',                    [AnalyticsController::class, 'getAnalytics']);
        Route::get('sales',                [AnalyticsController::class, 'getSalesData']);
        Route::get('traffic',              [AnalyticsController::class, 'getTrafficData']);
        Route::get('conversion',           [AnalyticsController::class, 'getConversionData']);
        Route::get('top-products',         [AnalyticsController::class, 'getTopProducts']);
        Route::get('brand-stats',          [AnalyticsController::class, 'getBrandStats']);
        Route::get('category-stats',       [AnalyticsController::class, 'getCategoryStats']);
        Route::get('price-distribution',   [AnalyticsController::class, 'getPriceDistribution']);
        Route::get('inventory',            [AnalyticsController::class, 'getInventoryStats']);
        Route::get('customer-behavior',    [AnalyticsController::class, 'getCustomerBehavior']);
        Route::post('clear-cache',         [AnalyticsController::class, 'clearCache']);
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [ElectronicProductController::class, 'order']);
        Route::get('my-orders', [ElectronicProductController::class, 'myOrders']);
    });
});
