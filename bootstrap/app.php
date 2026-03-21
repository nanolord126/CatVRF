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
