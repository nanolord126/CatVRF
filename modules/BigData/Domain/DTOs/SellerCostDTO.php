<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\DTOs;

/**
 * Seller Cost DTO
 *
 * Immutable DTO for seller-specific cost attribution.
 * Used for the seller-facing "Your analytics costs X" feature.
 */
final readonly class SellerCostDTO
{
    public function __construct(
        public int $sellerId,
        public int $tenantId,
        public string $period,
        public float $totalCostUsd,
        public float $costPer1mEvents,
        public float $costPerQuery,
        public float $costPerDashboardLoad,
        public float $costPerMlPrediction,
        public float $gmvAttributed,
        public float $costToGmvRatio,
        public float $roiMultiplier,
        /** @var array<string, float> context => cost */
        public array $byContext,
        /** @var array<string, mixed> optimization tips */
        public array $optimizationTips,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sellerId: (int) ($data['seller_id'] ?? 0),
            tenantId: (int) ($data['tenant_id'] ?? 0),
            period: (string) ($data['period'] ?? '30d'),
            totalCostUsd: (float) ($data['total_cost_usd'] ?? 0),
            costPer1mEvents: (float) ($data['cost_per_1m_events'] ?? 0),
            costPerQuery: (float) ($data['cost_per_query'] ?? 0),
            costPerDashboardLoad: (float) ($data['cost_per_dashboard_load'] ?? 0),
            costPerMlPrediction: (float) ($data['cost_per_ml_prediction'] ?? 0),
            gmvAttributed: (float) ($data['gmv_attributed'] ?? 0),
            costToGmvRatio: (float) ($data['cost_to_gmv_ratio'] ?? 0),
            roiMultiplier: (float) ($data['roi_multiplier'] ?? 0),
            byContext: (array) ($data['by_context'] ?? []),
            optimizationTips: (array) ($data['optimization_tips'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'period' => $this->period,
            'total_cost_usd' => round($this->totalCostUsd, 4),
            'cost_per_1m_events' => round($this->costPer1mEvents, 6),
            'cost_per_query' => round($this->costPerQuery, 6),
            'cost_per_dashboard_load' => round($this->costPerDashboardLoad, 6),
            'cost_per_ml_prediction' => round($this->costPerMlPrediction, 6),
            'gmv_attributed' => round($this->gmvAttributed, 2),
            'cost_to_gmv_ratio' => round($this->costToGmvRatio, 6),
            'roi_multiplier' => round($this->roiMultiplier, 2),
            'by_context' => array_map(fn(float $v) => round($v, 4), $this->byContext),
            'optimization_tips' => $this->optimizationTips,
        ];
    }
}
