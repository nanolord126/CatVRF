<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Modules\BigData\Domain\Enums\BoundedContext;

/**
 * CostAttribution Entity
 *
 * Immutable domain entity representing cost attributed to a seller / bounded context.
 * Used for unit economics: cost per order, cost per prediction, cost per dashboard load.
 */
final readonly class CostAttribution
{
    private function __construct(
        public \DateTimeImmutable $date,
        public int $tenantId,
        public int $sellerId,
        public BoundedContext $boundedContext,
        public string $vertical,
        public float $attributedCostUsd,
        public int $eventsProcessed,
        public int $queriesExecuted,
        public int $dashboardLoads,
        public int $mlPredictions,
        public float $costPer1mEvents,
        public float $costPerQuery,
        public float $costPerDashboardLoad,
        public float $costPerMlPrediction,
        public float $costPerOrderAnalyzed,
        public float $gmvAttributed,
        public float $revenueAttributed,
        public float $costToGmvRatio,
        public float $roiMultiplier,
    ) {
        $this->validate();
    }

    public static function fromMetrics(
        \DateTimeImmutable $date,
        int $tenantId,
        int $sellerId,
        BoundedContext $boundedContext,
        string $vertical,
        float $attributedCostUsd,
        int $eventsProcessed,
        int $queriesExecuted,
        int $dashboardLoads,
        int $mlPredictions,
        float $gmvAttributed,
        float $revenueAttributed,
        int $ordersAnalyzed,
    ): self {
        $costPer1mEvents = $eventsProcessed > 0 ? ($attributedCostUsd * 1_000_000) / $eventsProcessed : 0;
        $costPerQuery = $queriesExecuted > 0 ? $attributedCostUsd / $queriesExecuted : 0;
        $costPerDashboardLoad = $dashboardLoads > 0 ? $attributedCostUsd / $dashboardLoads : 0;
        $costPerMlPrediction = $mlPredictions > 0 ? $attributedCostUsd / $mlPredictions : 0;
        $costPerOrderAnalyzed = $ordersAnalyzed > 0 ? $attributedCostUsd / $ordersAnalyzed : 0;
        $costToGmvRatio = $gmvAttributed > 0 ? $attributedCostUsd / $gmvAttributed : 0;
        $roiMultiplier = $attributedCostUsd > 0 ? $revenueAttributed / $attributedCostUsd : 0;

        return new self(
            date: $date,
            tenantId: $tenantId,
            sellerId: $sellerId,
            boundedContext: $boundedContext,
            vertical: $vertical,
            attributedCostUsd: $attributedCostUsd,
            eventsProcessed: $eventsProcessed,
            queriesExecuted: $queriesExecuted,
            dashboardLoads: $dashboardLoads,
            mlPredictions: $mlPredictions,
            costPer1mEvents: $costPer1mEvents,
            costPerQuery: $costPerQuery,
            costPerDashboardLoad: $costPerDashboardLoad,
            costPerMlPrediction: $costPerMlPrediction,
            costPerOrderAnalyzed: $costPerOrderAnalyzed,
            gmvAttributed: $gmvAttributed,
            revenueAttributed: $revenueAttributed,
            costToGmvRatio: $costToGmvRatio,
            roiMultiplier: $roiMultiplier,
        );
    }

    public function isCostEfficient(): bool
    {
        return $this->costToGmvRatio <= 0.05;
    }

    public function exceedsTargetUnitCost(): bool
    {
        $target = $this->boundedContext->targetCostPerUnit();

        if ($target === null) {
            return false;
        }

        return match ($this->boundedContext) {
            BoundedContext::Ingestion => $this->costPer1mEvents > $target,
            BoundedContext::ClickHouseCompute => $this->costPerQuery > $target,
            BoundedContext::CLVInference => $this->costPerMlPrediction > $target,
            BoundedContext::SellerDashboard => $this->costPerDashboardLoad > $target,
            default => false,
        };
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'bounded_context' => $this->boundedContext->value,
            'vertical' => $this->vertical,
            'attributed_cost_usd' => round($this->attributedCostUsd, 4),
            'events_processed' => $this->eventsProcessed,
            'queries_executed' => $this->queriesExecuted,
            'dashboard_loads' => $this->dashboardLoads,
            'ml_predictions' => $this->mlPredictions,
            'cost_per_1m_events' => round($this->costPer1mEvents, 6),
            'cost_per_query' => round($this->costPerQuery, 6),
            'cost_per_dashboard_load' => round($this->costPerDashboardLoad, 6),
            'cost_per_ml_prediction' => round($this->costPerMlPrediction, 6),
            'cost_per_order_analyzed' => round($this->costPerOrderAnalyzed, 6),
            'gmv_attributed' => round($this->gmvAttributed, 2),
            'revenue_attributed' => round($this->revenueAttributed, 2),
            'cost_to_gmv_ratio' => round($this->costToGmvRatio, 6),
            'roi_multiplier' => round($this->roiMultiplier, 2),
            'is_cost_efficient' => $this->isCostEfficient(),
        ];
    }

    private function validate(): void
    {
        if ($this->attributedCostUsd < 0) {
            throw new \DomainException('Attributed cost cannot be negative');
        }
        if ($this->tenantId <= 0) {
            throw new \DomainException('Tenant ID must be positive');
        }
    }
}
