<?php

declare(strict_types=1);

namespace Modules\BigData;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\BigData\Application\Jobs\EvaluateAlertsJob;
use Modules\BigData\Application\Jobs\MaintenanceJob;
use Modules\BigData\Application\Jobs\SelfHealJob;
use Modules\BigData\Application\Services\BigDataAlertEvaluator;
use Modules\BigData\Application\Services\BigDataMonitoringFacade;
use Modules\BigData\Domain\Interfaces\AlertEvaluatorInterface;
use Modules\BigData\Domain\Interfaces\MetricsExporterInterface;
use Modules\BigData\Domain\Interfaces\MonitoringRepositoryInterface;
use Modules\BigData\Infrastructure\Exporters\BigDataExporter;
use Modules\BigData\Infrastructure\Repositories\ClickHouseMonitoringRepository;

/**
 * BigData Observability Service Provider
 *
 * Registers monitoring, metrics, alerting, and observability services
 * following Clean Architecture / 9-layer structure:
 *
 * Domain → Application → Infrastructure → Presentation
 *
 * Bindings:
 * - MonitoringRepositoryInterface → ClickHouseMonitoringRepository
 * - AlertEvaluatorInterface → BigDataAlertEvaluator
 * - MetricsExporterInterface → BigDataExporter
 * - BigDataMonitoringFacade → singleton
 */
class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ================================================================
        // Domain Interface → Infrastructure Implementation Bindings
        // ================================================================

        $this->app->singleton(
            MonitoringRepositoryInterface::class,
            ClickHouseMonitoringRepository::class,
        );

        $this->app->singleton(
            AlertEvaluatorInterface::class,
            BigDataAlertEvaluator::class,
        );

        $this->app->singleton(
            MetricsExporterInterface::class,
            BigDataExporter::class,
        );

        // ================================================================
        // Application Services
        // ================================================================

        $this->app->singleton(BigDataMonitoringFacade::class, function ($app) {
            return new BigDataMonitoringFacade(
                repository: $app->make(MonitoringRepositoryInterface::class),
                alertEvaluator: $app->make(AlertEvaluatorInterface::class),
            );
        });

        // Alias for easy container access
        $this->app->alias(BigDataMonitoringFacade::class, 'bigdata.monitor');

        // ================================================================
        // Load Presentation Routes
        // ================================================================

        $this->loadRoutesFrom(
            __DIR__ . '/Presentation/Routes/monitoring.php',
        );
    }

    public function boot(): void
    {
        // ================================================================
        // Scheduled Tasks (only when monitoring is enabled)
        // ================================================================

        if (config('bigdata.monitoring.enabled', true)) {
            $this->registerScheduledTasks();
        }
    }

    /**
     * Register scheduled monitoring tasks
     */
    private function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Alert evaluation every minute
            $schedule->job(new EvaluateAlertsJob())
                ->everyMinute()
                ->name('bigdata:evaluate-alerts')
                ->withoutOverlapping()
                ->onOneServer()
                ->onQueue('bigdata-monitoring');

            // Self-healing check every 5 minutes
            $schedule->job(new SelfHealJob())
                ->everyFiveMinutes()
                ->name('bigdata:self-heal')
                ->withoutOverlapping()
                ->onOneServer()
                ->onQueue('bigdata-monitoring');

            // ClickHouse OPTIMIZE weekly (Sunday 03:00)
            $schedule->job(new MaintenanceJob())
                ->weekly()
                ->sundays()
                ->at('03:00')
                ->name('bigdata:maintenance')
                ->withoutOverlapping()
                ->onOneServer()
                ->onQueue('bigdata-maintenance');
        });
    }

    /**
     * Get the services provided by the provider
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            MonitoringRepositoryInterface::class,
            AlertEvaluatorInterface::class,
            MetricsExporterInterface::class,
            BigDataMonitoringFacade::class,
            'bigdata.monitor',
        ];
    }
}
