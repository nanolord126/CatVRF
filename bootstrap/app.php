<?php declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/api',
        then: function (): void {
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
                $path = __DIR__ . '/../routes/' . $file;
                if (file_exists($path)) {
                    require $path;
                }
            }

            // ML metrics routes (for drift detection monitoring)
            require __DIR__ . '/../routes/ml-metrics.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // \App\Http\Middleware\HandleInertiaRequests::class, // Temporarily disabled for stress testing
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\TenantQuotaMiddleware::class,
        ]);

        $middleware->alias([
            'tenant'  => \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            'b2b.api' => \App\Http\Middleware\B2BApiMiddleware::class,
            'order' => \App\Http\Middleware\OrderMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception handling
        $exceptions->render(function (\Throwable $e) {
            // Log with correlation_id
            \Illuminate\Support\Facades\Log::channel('audit')->error(
                'Unhandled exception',
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                    'url' => request()->fullUrl(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        });
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
    ])
    ->create();

return $app;
