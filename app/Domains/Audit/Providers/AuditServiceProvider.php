<?php

declare(strict_types=1);

namespace App\Domains\Audit\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Audit\Services\AuditService;
use App\Domains\Audit\Listeners\AuditModelListener;
use App\Domains\Audit\Events\ModelCreatedEvent;
use App\Domains\Audit\Events\ModelUpdatedEvent;
use App\Domains\Audit\Events\ModelDeletedEvent;
use Illuminate\Console\Scheduling\Schedule;

/**
 * AuditServiceProvider — Service provider for Audit Vertical.
 * Registers services, event listeners, and scheduled tasks.
 */
final class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService(
                bus: $app->make(\Illuminate\Contracts\Bus\Dispatcher::class),
                db: $app->make(\Illuminate\Database\DatabaseManager::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
                request: $app->make(\Illuminate\Http\Request::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event subscriber
        $this->app->events->subscribe(AuditModelListener::class);

        // Load migrations
        $this->loadMigrationsFrom(database_path('migrations'));

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/audit.php');

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/audit.php' => config_path('audit.php'),
            ], 'audit-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'audit-migrations');
        }
    }

    /**
     * Configure scheduled tasks.
     */
    public function schedule(Schedule $schedule): void
    {
        $pruneSchedule = config('audit.prune_schedule', '0 0 * * *');

        if ($pruneSchedule) {
            $schedule->command('audit:prune')->cron($pruneSchedule)
                ->description('Prune old audit logs based on retention policy');
        }
    }
}
