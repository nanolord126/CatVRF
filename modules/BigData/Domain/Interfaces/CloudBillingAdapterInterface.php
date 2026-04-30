<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Carbon\CarbonImmutable;

/**
 * Cloud Billing Adapter Interface
 *
 * Domain interface for cloud provider billing API integration.
 * Each cloud provider implements this interface.
 */
interface CloudBillingAdapterInterface
{
    /**
     * Get daily billing data for a date range
     *
     * @return array<array{
     *   billing_id: string,
     *   cloud_provider: string,
     *   billing_date: string,
     *   service: string,
     *   cost_category: string,
     *   environment: string,
     *   tenant_id: int,
     *   seller_id: int,
     *   workload: string,
     *   resource_tags: string,
     *   cost_amount: float,
     *   cost_amount_rub: float,
     *   currency: string,
     *   pricing_unit: string,
     *   usage_quantity: float,
     *   unit_price: float,
     *   account_id: string,
     *   payment_type: string,
     *   is_optimizable: int,
     *   optimization_potential: float
     * }>
     */
    public function getDailyBilling(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * Get current month forecast from cloud provider
     */
    public function getMonthlyForecast(): float;

    /**
     * Get cost optimization recommendations from cloud provider
     * @return array<array{type: string, resource: string, savings_usd: float, description: string}>
     */
    public function getOptimizationRecommendations(): array;

    /**
     * Get resource tags for cost attribution
     * @return array<string, array<string, string>> resource_id => tags
     */
    public function getResourceTags(): array;

    /**
     * Test connectivity to the cloud billing API
     */
    public function testConnection(): bool;
}
