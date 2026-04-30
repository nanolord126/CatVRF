<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApplyVerticalMiddleware;
use App\Http\Middleware\B2BApiMiddleware;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\FilamentAdminIpWhitelist;
use App\Http\Middleware\FilamentMetricsMiddleware;
use App\Http\Middleware\FilamentTenantScope;
use App\Http\Middleware\MedicalComplianceMiddleware;
use App\Http\Middleware\OrderMiddleware;
use App\Http\Middleware\PiiGuardMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\TenantQuotaMiddleware;
use App\Providers\AppServiceProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            
            Route::middleware('web')
                ->prefix('monitoring')
                ->group(base_path('routes/monitoring.php'));
            
            /**
             * ══════════════════════════════════════════════════════════
             * ROADMAP МАРШРУТОВ — ОЧЕРЕДИ РЕАЛИЗАЦИИ
             * ══════════════════════════════════════════════════════════
             * Q1 — технические домены       (активно, разрабатывается)
             * Q2 — основные бизнес-вертикали (активно, начинать после Q1)
             * Q3 — все остальные             (ЗАБЛОКИРОВАНО — закомментировано)
             *
             * Подробный roadmap: config/domain_queues.php
             * ══════════════════════════════════════════════════════════
             */

            // ──────────────────────────────────────────────────────────
            // ОЧЕРЕДЬ 1 — Технические инфраструктурные маршруты (Q1)
            // ──────────────────────────────────────────────────────────
            $q1Routes = [
                'ai.api.php',             // AI vertical
                'api-analytics-v2.php',   // Analytics
                'api-3d.php',             // AI / 3D-конструктор
                'api_b2b.php',            // B2B ключевая авторизация (инфраструктура)
                'referral.api.php',       // Реферальная программа
                'wallet.api.php',         // Кошелёк
                'payment.api.php',        // Платежи
                'geo_logistics.api.php',  // Гео-логистика
                'promo.api.php',          // Промо-инфраструктура
            ];

            // ──────────────────────────────────────────────────────────
            // ОЧЕРЕДЬ 2 — Основные бизнес-вертикали (Q2)
            // ──────────────────────────────────────────────────────────
            $q2Routes = [
                // Бьюти
                'beauty.api.php',
                'b2b.beauty.api.php',
                // Гостиницы
                'hotels.api.php',
                'b2b.hotels.api.php',
                // Цветы
                'flowers.php',
                'b2b.flowers.api.php',
                // Фуд
                'food.api.php',
                'b2b.food.api.php',
                // Одежда / обувь
                'fashion.api.php',
                'cosmetics.api.php',
                'b2b.fashion.api.php',
                'b2b.fashion-retail.api.php',
                // Доставка / курьерская служба
                'grocery.api.php',
                // Машины / СТО
                'auto.api.php',
                'b2b.auto.api.php',
                // Аренда машин
                'short_term_rentals.api.php',
                // Аренда жилья и продажа
                'realestate.api.php',
                'b2b.real-estate.api.php',
                // Фитнес (услуги)
                'fitness.api.php',
                'b2b.fitness.api.php',
                // Спорт (новые AI-фичи)
                'sports-api-new.php',
                // Медицина (услуги)
                'medical.api.php',
                'b2b.medical.api.php',
                'b2b.medical-healthcare.api.php',
                // Домашний сервис (услуги)
                'home_services.api.php',
                'b2b.home-services.api.php',
                // Аптеки
                'pharmacy.api.php',
                // Мебель
                'furniture.api.php',
                // Мясные
                'meat_shops.api.php',
                // Смешанные вертикали Q2 (прод + кондитерские + мебель + запчасти + аптеки)
                'api_verticals.php',
            ];

            // ──────────────────────────────────────────────────────────
            // ОЧЕРЕДЬ 3 — Все остальные вертикали (Q3)
            // ЗАБЛОКИРОВАНО: раскомментировать только после Q1 + Q2
            // ──────────────────────────────────────────────────────────
            // $q3Routes = [
            //     // Курсы / образование
            //     'courses.api.php',
            //     'b2b.courses.api.php',
            //     // Развлечения
            //     'entertainment.api.php',
            //     'b2b.entertainment.api.php',
            //     // Логистика B2B (отдельная от доставки)
            //     'logistics.api.php',
            //     'b2b.logistics.api.php',
            //     // Питомцы
            //     'pet.api.php',
            //     'b2b.pet.api.php',
            //     'b2b.pet-services.api.php',
            //     // Спорт
            //     'sports.api.php',
            //     'b2b.sports.api.php',
            //     // Билеты
            //     'tickets.api.php',
            //     'b2b.tickets.api.php',
            //     // Путешествия
            //     'travel.api.php',
            //     'b2b.travel.api.php',
            //     'b2b.travel-tourism.api.php',
            //     // Фриланс
            //     'freelance.php',
            //     'b2b.freelance.api.php',
            //     // Фотография
            //     'photography.php',
            //     'b2b.photography.api.php',
            //     // Фермерские прямые
            //     'fresh_produce.api.php',
            //     'farm_direct.api.php',
            //     // Книги
            //     'books.api.php',
            //     // Ювелирные
            //     'jewelry.api.php',
            //     // Стройматериалы
            //     'construction_materials.api.php',
            //     // Электроника
            //     'electronics.api.php',
            //     // Детские товары
            //     'toys_kids.api.php',
            // ];

            $verticalRoutes = array_merge($q1Routes, $q2Routes);
            foreach ($verticalRoutes as $file) {
                $path = __DIR__.'/../routes/'.$file;
                if (file_exists($path)) {
                    require $path;
                }
            }

            // ML metrics routes (for drift detection monitoring)
            require __DIR__.'/../routes/ml-metrics.php';

            // Telegram webhook routes
            require __DIR__.'/../routes/telegram.php';

            // WhatsApp webhook routes
            require __DIR__.'/../routes/api/notifications.php';

            // Behavioral biometrics API routes
            require __DIR__.'/../routes/api/behavioral.php';

            // Compliance API routes (152-ФЗ, ФЗ-115, ФЗ-161, 54-ФЗ)
            require __DIR__.'/../routes/api/compliance.php';

            // Payment compliance API routes (ФЗ-161, ФЗ-115, 54-ФЗ)
            require __DIR__.'/../routes/api/payment_compliance.php';

            // Media upload API routes
            require __DIR__.'/../routes/api/media.php';

            // Video calling API routes
            require __DIR__.'/../routes/api/video.php';

            // Marketplace API routes (main marketplace storefront)
            require __DIR__.'/../modules/Marketplace/Presentation/Routes/marketplace.php';

            // Cart module routes
            if (file_exists(__DIR__.'/../modules/Cart/Presentation/Routes/cart.php')) {
                require __DIR__.'/../modules/Cart/Presentation/Routes/cart.php';
            }

            // Payment module routes
            if (file_exists(__DIR__.'/../modules/Payment/Presentation/Routes/payment.php')) {
                require __DIR__.'/../modules/Payment/Presentation/Routes/payment.php';
            }

            // Wallet module routes
            if (file_exists(__DIR__.'/../modules/Wallet/Presentation/Routes/wallet.php')) {
                require __DIR__.'/../modules/Wallet/Presentation/Routes/wallet.php';
            }

            // Auto module routes
            if (file_exists(__DIR__.'/../modules/Auto/Presentation/Routes/auto.php')) {
                require __DIR__.'/../modules/Auto/Presentation/Routes/auto.php';
            }

            // Fashion module routes
            if (file_exists(__DIR__.'/../modules/Fashion/Presentation/Routes/fashion.php')) {
                require __DIR__.'/../modules/Fashion/Presentation/Routes/fashion.php';
            }

            // Beauty module routes
            if (file_exists(__DIR__.'/../modules/BeautyMasters/Presentation/Routes/beauty.php')) {
                require __DIR__.'/../modules/BeautyMasters/Presentation/Routes/beauty.php';
            }

            // Dental module routes
            if (file_exists(__DIR__.'/../modules/Dental/Presentation/Routes/dental.php')) {
                require __DIR__.'/../modules/Dental/Presentation/Routes/dental.php';
            }

            // Flowers module routes
            if (file_exists(__DIR__.'/../modules/Flowers/Presentation/Routes/flowers.php')) {
                require __DIR__.'/../modules/Flowers/Presentation/Routes/flowers.php';
            }

            // Inventory module routes
            if (file_exists(__DIR__.'/../modules/Inventory/Presentation/Routes/inventory.php')) {
                require __DIR__.'/../modules/Inventory/Presentation/Routes/inventory.php';
            }

            // Loyalty module routes
            if (file_exists(__DIR__.'/../modules/Loyalty/Presentation/Routes/loyalty.php')) {
                require __DIR__.'/../modules/Loyalty/Presentation/Routes/loyalty.php';
            }

            // Fitness module routes
            if (file_exists(__DIR__.'/../modules/Fitness/Presentation/Routes/fitness.php')) {
                require __DIR__.'/../modules/Fitness/Presentation/Routes/fitness.php';
            }

            // Veterinary module routes
            if (file_exists(__DIR__.'/../modules/Veterinary/Presentation/Routes/veterinary.php')) {
                require __DIR__.'/../modules/Veterinary/Presentation/Routes/veterinary.php';
            }

            // Logistics API - direct inclusion for dev testing (without middleware)
            Route::prefix('logistics-test')->withoutMiddleware([SubstituteBindings::class])->group(function () {
                Route::get('/test', function () {
                    return response()->json(['message' => 'Logistics API works!', 'status' => 'ok']);
                });
                Route::get('/pickup-points', function () {
                    $pickupPoints = \Illuminate\Support\Facades\DB::table('pickup_points')
                        ->where('tenant_id', 1)
                        ->where('status', 'active')
                        ->get();

                    return response()->json([
                        'data' => $pickupPoints,
                        'meta' => ['total' => $pickupPoints->count()],
                    ]);
                });
            });

            // API v2 routes (for breaking changes)
            Route::prefix('api/v2')
                ->middleware(['api', 'throttle:api'])
                ->group(function () {
                    require __DIR__.'/../routes/api-v2.php';
                });

            // Horizon metrics routes (for queue monitoring) - only if Horizon is installed
            if (class_exists('Laravel\Horizon\Horizon')) {
                require __DIR__.'/../routes/horizon-metrics.php';
            }

            // Prometheus monitoring routes
            require __DIR__.'/../routes/monitoring.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // \App\Http\Middleware\HandleInertiaRequests::class, // Temporarily disabled for stress testing
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            SubstituteBindings::class,
            TenantQuotaMiddleware::class,
        ]);

        $middleware->alias([
            'tenant'  => EnsureUserBelongsToTenant::class,
            'b2b.api' => B2BApiMiddleware::class,
            'order' => OrderMiddleware::class,
            'filament.admin.ip' => FilamentAdminIpWhitelist::class,
            'filament.tenant.scope' => FilamentTenantScope::class,
            'medical.compliance' => MedicalComplianceMiddleware::class,
            'filament.metrics' => FilamentMetricsMiddleware::class,
            'ability' => CheckTokenAbility::class,
            'pii.guard' => PiiGuardMiddleware::class,
            'security.headers' => SecurityHeadersMiddleware::class,
            'vertical.middleware' => ApplyVerticalMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception handling
        $exceptions->render(function (Throwable $e) {
            // Log with correlation_id
            logger()->channel('audit')->error(
                'Unhandled exception',
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'correlation_id' => request()->header('X-Correlation-ID') ?? Str::uuid(),
                    'url' => request()->fullUrl(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        });
    })
    ->withProviders([
        AppServiceProvider::class,
        \App\Domains\Bonuses\BonusesServiceProvider::class,
        \Modules\Marketplace\MarketplaceServiceProvider::class,
    ])
    ->create();

return $app;
