<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class AnalyticsDto
{
    /**
     * @param array<string, mixed> $salesData
     * @param array<string, mixed> $trafficData
     * @param array<string, mixed> $conversionData
     * @param array<string, mixed> $topProducts
     * @param array<string, mixed> $brandStats
     * @param array<string, mixed> $categoryStats
     * @param array<string, mixed> $priceDistribution
     * @param array<string, mixed> $inventoryStats
     * @param array<string, mixed> $customerBehavior
     */
    public function __construct(
        public array $salesData,
        public array $trafficData,
        public array $conversionData,
        public array $topProducts,
        public array $brandStats,
        public array $categoryStats,
        public array $priceDistribution,
        public array $inventoryStats,
        public array $customerBehavior,
        public string $period,
        public string $correlationId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'sales_data' => $this->salesData,
            'traffic_data' => $this->trafficData,
            'conversion_data' => $this->conversionData,
            'top_products' => $this->topProducts,
            'brand_stats' => $this->brandStats,
            'category_stats' => $this->categoryStats,
            'price_distribution' => $this->priceDistribution,
            'inventory_stats' => $this->inventoryStats,
            'customer_behavior' => $this->customerBehavior,
            'period' => $this->period,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            salesData: $data['sales_data'] ?? [],
            trafficData: $data['traffic_data'] ?? [],
            conversionData: $data['conversion_data'] ?? [],
            topProducts: $data['top_products'] ?? [],
            brandStats: $data['brand_stats'] ?? [],
            categoryStats: $data['category_stats'] ?? [],
            priceDistribution: $data['price_distribution'] ?? [],
            inventoryStats: $data['inventory_stats'] ?? [],
            customerBehavior: $data['customer_behavior'] ?? [],
            period: $data['period'] ?? '7d',
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
