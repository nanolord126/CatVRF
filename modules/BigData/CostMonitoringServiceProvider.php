<?php

declare(strict_types=1);

namespace Modules\BigData;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Modules\BigData\Application\Jobs\AutoOptimizeJob;
use Modules\BigData\Application\Jobs\BudgetEnforcementJob;
use Modules\BigData\Application\Jobs\ImportBillingDataJob;
use Modules\BigData\Application\Listeners\CostNotificationListener;
use Modules\BigData\Application\Services\BigDataCostFacade;
use Modules\BigData\Domain\Enums\CloudProvider;
use Modules\BigData\Domain\Events\BudgetExceeded;
use Modules\BigData\Domain\Events\CostAnomalyDetected;
use Modules\BigData\Domain\Events\OptimizationApplied;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;
use Modules\BigData\Domain\Interfaces\CostRepositoryInterface;
use Modules\BigData\Domain\ValueObjects\BudgetThreshold;
use Modules\BigData\Infrastructure\Adapters\AWSCostExplorerAdapter;
use Modules\BigData\Infrastructure\Adapters\AzureCostManagementAdapter;
use Modules\BigData\Infrastructure\Adapters\GCPBillingAdapter;
use Modules\BigData\Infrastructure\Adapters\SelfHostedBillingAdapter;
use Modules\BigData\Infrastructure\Exporters\BigDataCostExporter;
use Modules\BigData\Infrastructure\Repositories\ClickHouseCostRepository;

/**
 * Cost Monitoring Service Provider
 *
 * Registers FinOps / Cost Monitoring services, binds interfaces,
 * loads routes, and schedules billing/optimization jobs.
 */
final class CostMonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!config('bigdata.cost.enabled', true)) {
            return;
        }

        // ================================================================
        // Domain Interface → Infrastructure Implementation Bindings
        // ================================================================

        $this->app->singleton(
            CostRepositoryInterface::class,
            ClickHouseCostRepository::class,
        );

        // ================================================================
        // Budget Threshold Value Object
        // ================================================================

        $this->app->singleton(BudgetThreshold::class, function () {
            return BudgetThreshold::fromConfig();
        });

        // ================================================================
        // Cloud Billing Adapters (multi-cloud)
        // ================================================================

        $this->registerBillingAdapters();

        // ================================================================
        // Application Services
        // ================================================================

        $this->app->singleton(BigDataCostFacade::class, function ($app) {
            return new BigDataCostFacade(
                repository: $app->make(CostRepositoryInterface::class),
                budgetThreshold: $app->make(BudgetThreshold::class),
            );
        });

        // Alias for easy container access
        $this->app->alias(BigDataCostFacade::class, 'bigdata.cost');

        // Cost metrics exporter
        $this->app->singleton(BigDataCostExporter::class, function ($app) {
            return new BigDataCostExporter(
                repository: $app->make(CostRepositoryInterface::class),
            );
        });

        // ================================================================
        // Load Presentation Routes
        // ================================================================

        $this->loadRoutesFrom(
            __DIR__ . '/Presentation/Routes/cost.php',
        );
    }

    public function boot(): void
    {
        if (!config('bigdata.cost.enabled', true)) {
            return;
        }

        // ================================================================
        // Event Listeners (notifications)
        // ================================================================

        $this->registerEventListeners();

        // ================================================================
        // Scheduled Tasks
        // ================================================================

        $this->registerScheduledTasks();
    }

    /**
     * Register cloud billing adapter singletons for each provider.
     */
    private function registerBillingAdapters(): void
    {
        // AWS
        $this->app->singleton('bigdata.billing.aws', function () {
            return new AWSCostExplorerAdapter(
                accessKeyId: config('bigdata.cost.aws_access_key_id', ''),
                secretAccessKey: config('bigdata.cost.aws_secret_access_key', ''),
                region: config('bigdata.cost.aws_region', 'us-east-1'),
                roleArn: config('bigdata.cost.aws_role_arn'),
            );
        });

        // GCP
        $this->app->singleton('bigdata.billing.gcp', function () {
            return new GCPBillingAdapter(
                projectId: config('bigdata.cost.gcp_project_id', ''),
                billingAccountId: config('bigdata.cost.gcp_billing_account_id', ''),
                accessToken: config('bigdata.cost.gcp_access_token', ''),
            );
        });

        // Azure
        $this->app->singleton('bigdata.billing.azure', function () {
            return new AzureCostManagementAdapter(
                subscriptionId: config('bigdata.cost.azure_subscription_id', ''),
                tenantId: config('bigdata.cost.azure_tenant_id', ''),
                clientId: config('bigdata.cost.azure_client_id', ''),
                clientSecret: config('bigdata.cost.azure_client_secret', ''),
            );
        });

        // Self-hosted
        $this->app->singleton('bigdata.billing.self_hosted', function () {
            return new SelfHostedBillingAdapter(
                computeCostPerHour: (float) config('bigdata.cost.self_hosted_compute_cost_per_hour', 0.10),
                storageCostPerGbMonth: (float) config('bigdata.cost.self_hosted_storage_cost_per_gb_month', 0.023),
                networkCostPerGb: (float) config('bigdata.cost.self_hosted_network_cost_per_gb', 0.01),
            );
        });
    }

    /**
     * Register event listeners for cost notifications.
     */
    private function registerEventListeners(): void
    {
        Event::listen(BudgetExceeded::class, [CostNotificationListener::class, 'handleBudgetExceeded']);
        Event::listen(CostAnomalyDetected::class, [CostNotificationListener::class, 'handleCostAnomaly']);
        Event::listen(OptimizationApplied::class, [CostNotificationListener::class, 'handleOptimizationApplied']);
    }

    /**
     * Register scheduled tasks for billing import, budget enforcement, and auto-optimization.
     */
    private function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $provider = config('bigdata.cost.cloud_provider', 'self_hosted');

            // Import billing data hourly
            $schedule->job(new ImportBillingDataJob($provider))
                ->hourly()
                ->name('bigdata:import-billing')
                ->withoutOverlapping()
                ->onOneServer()
                ->onQueue('bigdata-cost');

            // Budget enforcement every 30 minutes
            $schedule->job(new BudgetEnforcementJob())
                ->everyThirtyMinutes()
                ->name('bigdata:budget-enforcement')
                ->withoutOverlapping()
                ->onOneServer()
                ->onQueue('bigdata-cost');

            // Auto-optimization daily at 06:00
            if (config('bigdata.cost.auto_optimize_enabled', true)) {
                $schedule->job(new AutoOptimizeJob())
                    ->dailyAt('06:00')
                    ->name('bigdata:auto-optimize')
                    ->withoutOverlapping()
                    ->onOneServer()
                    ->onQueue('bigdata-cost');
            }
        });
    }
}
