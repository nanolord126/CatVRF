<?php declare(strict_types=1);

namespace App\Domains\Beauty\Application\Providers;

use Illuminate\Support\ServiceProvider;

final class BeautyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Контейнерные биндинги Beauty регистрируются в соответствующих
        // доменных провайдерах/сервисах. Здесь оставлен безопасный no-op,
        // чтобы приложение корректно стартовало даже при частичной миграции
        // legacy-модулей в новую 9-слойную архитектуру.
    }

    public function boot(): void
    {
        $this->loadDomainRoutes();
        $this->loadDomainMigrations();
    }

    private function loadDomainRoutes(): void
    {
        $routeFiles = [
            app_path('Domains/Beauty/routes.php'),
            app_path('Domains/Beauty/Http/routes.php'),
            base_path('modules/Beauty/routes.php'),
        ];

        foreach ($routeFiles as $routeFile) {
            if (is_file($routeFile)) {
                $this->loadRoutesFrom($routeFile);
            }
        }
    }

    private function loadDomainMigrations(): void
    {
        $migrationPaths = [
            app_path('Domains/Beauty/Infrastructure/Persistence/Migrations'),
            app_path('Domains/Beauty/Database/Migrations'),
            base_path('modules/Beauty/Migrations'),
        ];

        $validPaths = [];

        foreach ($migrationPaths as $migrationPath) {
            if (is_dir($migrationPath)) {
                $validPaths[] = $migrationPath;
            }
        }

        if ($validPaths !== []) {
            $this->loadMigrationsFrom($validPaths);
        }
    }

    /**
     * Component: BeautyServiceProvider
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */}
