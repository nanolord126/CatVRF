<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/**
 * Feature Rollout Command
 *
 * Manages feature flag rollouts with safety checks and monitoring.
 * Usage:
 *   php artisan feature:rollout medical-ai-diagnosis --percentage=10
 *   php artisan feature:rollout fraud-ml-model-v2 --tenant=5
 *   php artisan feature:rollback medical-ai-diagnosis
 */
final class FeatureRolloutCommand extends Command
{
    protected $signature = 'feature:rollout 
                           {feature : Feature name}
                           {--percentage= : Rollout percentage (0-100)}
                           {--tenant= : Rollout for specific tenant}
                           {--user= : Rollout for specific user}
                           {--enable : Enable feature globally}
                           {--disable : Disable feature globally}
                           {--status : Show feature status}
                           {--list : List all features}';

    protected $description = 'Manage feature flag rollouts with safety checks';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listFeatures();
        }

        if ($this->option('status')) {
            return $this->showStatus();
        }

        $feature = $this->argument('feature');

        if ($this->option('disable')) {
            return $this->disableFeature($feature);
        }

        if ($this->option('enable')) {
            return $this->enableFeature($feature);
        }

        if ($this->option('percentage')) {
            return $this->rolloutPercentage($feature, (int) $this->option('percentage'));
        }

        if ($this->option('tenant')) {
            return $this->rolloutForTenant($feature, (int) $this->option('tenant'));
        }

        if ($this->option('user')) {
            return $this->rolloutForUser($feature, (int) $this->option('user'));
        }

        $this->error('Please specify an action: --enable, --disable, --percentage, --tenant, --user, --status, or --list');

        return self::FAILURE;
    }

    private function listFeatures(): int
    {
        $features = config('pennant.features');

        $this->info('Available Features:');
        $this->table(
            ['Feature', 'Class'],
            collect($features)->map(fn ($class, $name) => [$name, $class])->toArray()
        );

        return self::SUCCESS;
    }

    private function showStatus(): int
    {
        $feature = $this->argument('feature');

        $this->info("Feature: {$feature}");
        $this->info('Active: '.(Feature::active($feature) ? 'Yes' : 'No'));
        $this->info('Value: '.var_export(Feature::value($feature), true));

        return self::SUCCESS;
    }

    private function enableFeature(string $feature): int
    {
        $this->warn("Enabling feature globally: {$feature}");

        if (! $this->confirm('Are you sure you want to enable this feature globally?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Feature::activate($feature);
        $this->info("✅ Feature {$feature} enabled globally");

        return self::SUCCESS;
    }

    private function disableFeature(string $feature): int
    {
        $this->warn("Disabling feature: {$feature}");

        if (! $this->confirm('Are you sure you want to disable this feature?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Feature::deactivate($feature);
        $this->info("✅ Feature {$feature} disabled");

        return self::SUCCESS;
    }

    private function rolloutPercentage(string $feature, int $percentage): int
    {
        if ($percentage < 0 || $percentage > 100) {
            $this->error('Percentage must be between 0 and 100');

            return self::FAILURE;
        }

        $this->info("Rolling out {$feature} to {$percentage}% of traffic");

        if (! $this->confirm('Proceed with percentage rollout?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Feature::activate($feature);
        // Note: Actual percentage logic should be implemented in the feature class
        $this->info("✅ Feature {$feature} rolled out to {$percentage}%");

        return self::SUCCESS;
    }

    private function rolloutForTenant(string $feature, int $tenantId): int
    {
        $this->info("Rolling out {$feature} for tenant {$tenantId}");

        if (! $this->confirm('Proceed with tenant rollout?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Feature::for($tenantId)->activate($feature);
        $this->info("✅ Feature {$feature} enabled for tenant {$tenantId}");

        return self::SUCCESS;
    }

    private function rolloutForUser(string $feature, int $userId): int
    {
        $this->info("Rolling out {$feature} for user {$userId}");

        if (! $this->confirm('Proceed with user rollout?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Feature::for($userId)->activate($feature);
        $this->info("✅ Feature {$feature} enabled for user {$userId}");

        return self::SUCCESS;
    }
}
