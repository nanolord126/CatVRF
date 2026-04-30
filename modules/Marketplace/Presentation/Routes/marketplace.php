<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Presentation\Http\Controllers\MarketplaceController;

/*
|--------------------------------------------------------------------------
| Marketplace API Routes
|--------------------------------------------------------------------------
|
| Маршруты для витрины маркетплейса
| Стартовая страница платформы с рекомендациями и поиском
|
*/

Route::prefix('api/marketplace')
    ->middleware(['api', 'throttle:60,1'])
    ->group(function () {

        // Главная витрина маркетплейса (стартовая страница)
        Route::get('/', [MarketplaceController::class, 'index'])
            ->name('marketplace.index');

        // Поиск позиций
        Route::get('/search', [MarketplaceController::class, 'search'])
            ->name('marketplace.search');

        // Получить позицию по UUID
        Route::get('/listings/{uuid}', [MarketplaceController::class, 'show'])
            ->name('marketplace.show');

        // Похожие позиции
        Route::get('/listings/{uuid}/similar', [MarketplaceController::class, 'show'])
            ->name('marketplace.similar');

        // Рекомендации для пользователя (требует авторизации)
        Route::get('/recommendations', [MarketplaceController::class, 'recommendations'])
            ->middleware('auth:sanctum')
            ->name('marketplace.recommendations');

        // Категории маркетплейса
        Route::get('/categories', [MarketplaceController::class, 'categories'])
            ->name('marketplace.categories');

        // Позиции по категории
        Route::get('/categories/{category}', [MarketplaceController::class, 'category'])
            ->name('marketplace.category');

        // Позиции по вертикали
        Route::get('/verticals/{vertical}', [MarketplaceController::class, 'vertical'])
            ->name('marketplace.vertical');

        // Трендовые позиции
        Route::get('/trending', [MarketplaceController::class, 'trending'])
            ->name('marketplace.trending');

        // Featured позиции
        Route::get('/featured', [MarketplaceController::class, 'featured'])
            ->name('marketplace.featured');

        // Статистика маркетплейса (admin only)
        Route::get('/stats', [MarketplaceController::class, 'stats'])
            ->middleware('auth:sanctum', 'can:admin')
            ->name('marketplace.stats');
    });

// Admin routes for marketplace management
Route::prefix('api/admin/marketplace')
    ->middleware(['api', 'auth:sanctum', 'can:admin'])
    ->group(function () {

        // TODO: Add admin routes for:
        // - Create/update/delete listings
        // - Manage aggregation rules
        // - Trigger manual sync
        // - Configure ranking
        // - Manage categories
    });
