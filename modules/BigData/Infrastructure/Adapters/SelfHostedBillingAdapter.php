<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Adapters;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;

/**
 * Self-Hosted Billing Adapter
 *
 * For on-premises / self-hosted deployments.
 * Estimates costs based on server metrics, disk usage, and configured pricing.
 */
final class SelfHostedBillingAdapter implements CloudBillingAdapterInterface
{
    public function __construct(
        private readonly float $computeCostPerHour = 0.10,
        private readonly float $storageCostPerGbMonth = 0.023,
        private readonly float $networkCostPerGb = 0.01,
    ) {}

    public function getDailyBilling(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $records = [];
        $current = $startDate;

        while ($current <= $endDate) {
            $records = array_merge($records, $this->estimateDailyBilling($current));
            $current = $current->addDay();
        }

        return $records;
    }

    public function getMonthlyForecast(): float
    {
        $dailyCost = $this->estimateDailyTotal();
        $daysInMonth = now()->daysInMonth;

        return $dailyCost * $daysInMonth;
    }

    public function getOptimizationRecommendations(): array
    {
        return [
            [
                'type' => 'compression_increase',
                'resource' => 'clickhouse_all_tables',
                'savings_usd' => 50.0,
                'description' => 'Enable ZSTD(3) compression on all ClickHouse tables',
            ],
            [
                'type' => 'scheduled_merges',
                'resource' => 'clickhouse_cluster',
                'savings_usd' => 20.0,
                'description' => 'Schedule OPTIMIZE FINAL during off-peak hours',
            ],
        ];
    }

    public function getResourceTags(): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        return true; // Always available for self-hosted
    }

    private function estimateDailyBilling(CarbonImmutable $date): array
    {
        $serverCount = (int) config('bigdata.cost.self_hosted_server_count', 3);
        $storageGb = (float) config('bigdata.cost.self_hosted_storage_gb', 2000);
        $networkGb = (float) config('bigdata.cost.self_hosted_network_gb_daily', 50);

        $computeCost = $serverCount * 24 * $this->computeCostPerHour;
        $storageCost = ($storageGb * $this->storageCostPerGbMonth) / 30;
        $networkCost = $networkGb * $this->networkCostPerGb;

        return [
            [
                'billing_id' => uniqid('self_', true),
                'cloud_provider' => 'self_hosted',
                'billing_date' => $date->toDateString(),
                'service' => 'clickhouse_compute',
                'cost_category' => 'compute',
                'environment' => config('app.env', 'production'),
                'tenant_id' => 0,
                'seller_id' => 0,
                'workload' => '',
                'resource_tags' => '{}',
                'cost_amount' => $computeCost,
                'cost_amount_rub' => $computeCost * (float) config('bigdata.cost.usd_to_rub', 95),
                'currency' => 'USD',
                'pricing_unit' => 'hours',
                'usage_quantity' => $serverCount * 24,
                'unit_price' => $this->computeCostPerHour,
                'account_id' => 'self-hosted',
                'payment_type' => 'on_demand',
                'is_optimizable' => 1,
                'optimization_potential' => $computeCost * 0.3,
            ],
            [
                'billing_id' => uniqid('self_', true),
                'cloud_provider' => 'self_hosted',
                'billing_date' => $date->toDateString(),
                'service' => 'clickhouse_storage',
                'cost_category' => 'storage',
                'environment' => config('app.env', 'production'),
                'tenant_id' => 0,
                'seller_id' => 0,
                'workload' => '',
                'resource_tags' => '{}',
                'cost_amount' => $storageCost,
                'cost_amount_rub' => $storageCost * (float) config('bigdata.cost.usd_to_rub', 95),
                'currency' => 'USD',
                'pricing_unit' => 'GB-Month',
                'usage_quantity' => $storageGb,
                'unit_price' => $this->storageCostPerGbMonth,
                'account_id' => 'self-hosted',
                'payment_type' => 'on_demand',
                'is_optimizable' => 1,
                'optimization_potential' => $storageCost * 0.2,
            ],
            [
                'billing_id' => uniqid('self_', true),
                'cloud_provider' => 'self_hosted',
                'billing_date' => $date->toDateString(),
                'service' => 'kafka_broker',
                'cost_category' => 'compute',
                'environment' => config('app.env', 'production'),
                'tenant_id' => 0,
                'seller_id' => 0,
                'workload' => 'ingestion',
                'resource_tags' => '{}',
                'cost_amount' => $computeCost * 0.3,
                'cost_amount_rub' => $computeCost * 0.3 * (float) config('bigdata.cost.usd_to_rub', 95),
                'currency' => 'USD',
                'pricing_unit' => 'hours',
                'usage_quantity' => $serverCount * 24 * 0.3,
                'unit_price' => $this->computeCostPerHour,
                'account_id' => 'self-hosted',
                'payment_type' => 'on_demand',
                'is_optimizable' => 0,
                'optimization_potential' => 0,
            ],
            [
                'billing_id' => uniqid('self_', true),
                'cloud_provider' => 'self_hosted',
                'billing_date' => $date->toDateString(),
                'service' => 'network_transfer',
                'cost_category' => 'network',
                'environment' => config('app.env', 'production'),
                'tenant_id' => 0,
                'seller_id' => 0,
                'workload' => '',
                'resource_tags' => '{}',
                'cost_amount' => $networkCost,
                'cost_amount_rub' => $networkCost * (float) config('bigdata.cost.usd_to_rub', 95),
                'currency' => 'USD',
                'pricing_unit' => 'GB',
                'usage_quantity' => $networkGb,
                'unit_price' => $this->networkCostPerGb,
                'account_id' => 'self-hosted',
                'payment_type' => 'on_demand',
                'is_optimizable' => 0,
                'optimization_potential' => 0,
            ],
        ];
    }

    private function estimateDailyTotal(): float
    {
        $serverCount = (int) config('bigdata.cost.self_hosted_server_count', 3);
        $storageGb = (float) config('bigdata.cost.self_hosted_storage_gb', 2000);
        $networkGb = (float) config('bigdata.cost.self_hosted_network_gb_daily', 50);

        return ($serverCount * 24 * $this->computeCostPerHour * 1.3)
            + (($storageGb * $this->storageCostPerGbMonth) / 30)
            + ($networkGb * $this->networkCostPerGb);
    }
}
