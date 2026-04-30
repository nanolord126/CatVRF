<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Seller Dashboard DTO
 *
 * Contains all data needed for the seller analytics dashboard.
 * Includes KPI cards, trends, top products, and insights.
 */
final readonly class SellerDashboardDTO
{
    /**
     * @param KPICardDTO[] $kpiCards
     * @param TimeSeriesDto[] $trends
     * @param TopItemsDto $topProducts
     * @param TopItemsDto $topCategories
     * @param SellerInsightDTO[] $insights
     */
    public function __construct(
        public readonly Period $period,
        public readonly int $sellerId,
        public readonly int $tenantId,
        public readonly array $kpiCards,
        public readonly array $trends,
        public readonly TopItemsDto $topProducts,
        public readonly TopItemsDto $topCategories,
        public readonly array $insights,
        public readonly CarbonImmutable $generatedAt,
    ) {}

    /**
     * Create dashboard DTO from components.
     */
    public static function create(
        Period $period,
        int $sellerId,
        int $tenantId,
        array $kpiCards,
        array $trends,
        TopItemsDto $topProducts,
        TopItemsDto $topCategories,
        array $insights = [],
    ): self {
        return new self(
            $period,
            $sellerId,
            $tenantId,
            $kpiCards,
            $trends,
            $topProducts,
            $topCategories,
            $insights,
            CarbonImmutable::now(),
        );
    }

    public function toArray(): array
    {
        return [
            'period' => [
                'from' => $this->period->from()->toIso8601String(),
                'to' => $this->period->to()->toIso8601String(),
                'type' => (string) $this->period,
            ],
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'kpi_cards' => array_map(fn (KPICardDTO $card) => $card->toArray(), $this->kpiCards),
            'trends' => array_map(fn (TimeSeriesDto $trend) => $trend->toArray(), $this->trends),
            'top_products' => $this->topProducts->toArray(),
            'top_categories' => $this->topCategories->toArray(),
            'insights' => array_map(fn (SellerInsightDTO $insight) => $insight->toArray(), $this->insights),
            'generated_at' => $this->generatedAt->toIso8601String(),
        ];
    }
}
