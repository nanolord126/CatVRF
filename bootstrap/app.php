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
            // ─── Vertical API Routes ──────────────────────────────────
            $verticalRoutes = [
                // Core verticals
                'food.api.php',
                'hotels.api.php',
                'auto.api.php',
                'courses.api.php',
                'entertainment.api.php',
                'fashion.api.php',
                'fitness.api.php',
                'home_services.api.php',
                'logistics.api.php',
                'medical.api.php',
                'pet.api.php',
                'realestate.api.php',
                'sports.api.php',
                'tickets.api.php',
                'travel.api.php',
                // Standalone verticals
                'flowers.php',
                'freelance.php',
                'photography.php',
                // Additional verticals & analytics
                'api_verticals.php',
                'api-analytics-v2.php',
                'api-3d.php',
                // B2B routes
                'b2b.auto.api.php',
                'b2b.beauty.api.php',
                'b2b.courses.api.php',
                'b2b.entertainment.api.php',
                'b2b.fashion-retail.api.php',
                'b2b.fashion.api.php',
                'b2b.fitness.api.php',
                'b2b.flowers.api.php',
                'b2b.food.api.php',
                'b2b.freelance.api.php',
                'b2b.home-services.api.php',
                'b2b.hotels.api.php',
                'b2b.logistics.api.php',
                'b2b.medical-healthcare.api.php',
                'b2b.medical.api.php',
                'b2b.pet-services.api.php',
                'b2b.pet.api.php',
                'b2b.photography.api.php',
                'b2b.real-estate.api.php',
                'b2b.sports.api.php',
                'b2b.tickets.api.php',
                'b2b.travel-tourism.api.php',
                'b2b.travel.api.php',
                // Tenant panel
                // 'tenant.php', // Legacy stub — references non-existent controllers
                // New domain verticals
                'fresh_produce.api.php',
                'books.api.php',
                'jewelry.api.php',
                'construction_materials.api.php',
                'cosmetics.api.php',
                'electronics.api.php',
                'toys_kids.api.php',
                'meat_shops.api.php',
                'pharmacy.api.php',
                'furniture.api.php',
            ];
            foreach ($verticalRoutes as $file) {
                $path = __DIR__ . '/../routes/' . $file;
                if (file_exists($path)) {
                    require $path;
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\EnforceDbTransaction::class);
        // Force Doppler Secrets Load (Production only)
        // if (($_ENV['APP_ENV'] ?? 'local') === 'production' && class_exists(\App\Services\Infrastructure\DopplerService::class)) {
        //     (new \App\Services\Infrastructure\DopplerService())->boot();
        // }
        
        // Web middleware stack
        // $middleware->web(append: [
        //     \App\Http\Middleware\HandleInertiaRequests::class,
        // ]);
        
        // Global middleware stack
        // $middleware->append(\App\Http\Middleware\FraudControlMiddleware::class);
        // $middleware->append(\App\Http\Middleware\BusinessGroupGuard::class);
        // $middleware->append(\App\Http\Middleware\TenantScoping::class);
        
        // Rate limiting middleware groups
        $middleware->group('api', [
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Trusted proxies for behind load balancers
        if (($_ENV['APP_ENV'] ?? 'local') === 'production') {
            $trustedProxies = [
                '10.0.0.0/8',      // Private network
                '172.16.0.0/12',   // Private network
                '192.168.0.0/16',  // Private network
            ];
            if (!empty($_ENV['LOAD_BALANCER_IP'])) {
                $trustedProxies[] = (string) $_ENV['LOAD_BALANCER_IP'];
            }
            $middleware->trustProxies(at: $trustedProxies);
        }
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
