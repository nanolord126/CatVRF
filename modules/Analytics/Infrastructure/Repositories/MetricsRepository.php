<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Repositories;

use Illuminate\Database\DatabaseManager;
use Modules\Analytics\Domain\Entities\DailyMetrics;
use Modules\Analytics\Domain\Entities\HourlyMetrics;
use Modules\Analytics\Domain\Repositories\MetricsRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\Period;
use Modules\Analytics\Domain\ValueObjects\TenantId;
use Modules\Analytics\Domain\ValueObjects\SellerId;

/**
 * Metrics Repository Implementation
 *
 * Implements metrics data access using Eloquent/DatabaseManager.
 * Bridges domain entities with database tables.
 */
final class MetricsRepository implements MetricsRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function findDailyMetrics(TenantId $tenantId, Period $period): array
    {
        $results = $this->db->table('analytics_daily_metrics')
            ->where('tenant_id', $tenantId->value)
            ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()])
            ->orderBy('date')
            ->get();

        return $results->map(fn ($result) => $this->toDailyMetricsEntity($result))->toArray();
    }

    public function findHourlyMetrics(TenantId $tenantId, Period $period): array
    {
        $results = $this->db->table('analytics_hourly_metrics')
            ->where('tenant_id', $tenantId->value)
            ->whereBetween('hour', [$period->from(), $period->to()])
            ->orderBy('hour')
            ->get();

        return $results->map(fn ($result) => $this->toHourlyMetricsEntity($result))->toArray();
    }

    public function findSellerMetrics(TenantId $tenantId, SellerId $sellerId, Period $period): array
    {
        $results = $this->db->table('analytics_seller_metrics')
            ->where('tenant_id', $tenantId->value)
            ->where('seller_id', $sellerId->value)
            ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()])
            ->orderBy('date')
            ->get();

        return $results->map(fn ($result) => $this->toDailyMetricsEntity($result))->toArray();
    }

    public function saveDailyMetrics(DailyMetrics $metrics): void
    {
        $this->db->table('analytics_daily_metrics')->insert([
            'tenant_id' => $metrics->tenantId,
            'date' => $metrics->date->toDateString(),
            'sessions' => $metrics->sessions,
            'page_views' => $metrics->pageViews,
            'users_active' => $metrics->usersActive,
            'users_new' => $metrics->usersNew,
            'products_viewed' => $metrics->productsViewed,
            'products_added_to_cart' => $metrics->productsAddedToCart,
            'orders_count' => $metrics->ordersCount,
            'orders_revenue' => $metrics->ordersRevenue,
            'orders_aov' => $metrics->ordersAov,
            'gmv' => $metrics->gmv,
            'refunds_count' => $metrics->refundsCount,
            'refunds_amount' => $metrics->refundsAmount,
            'conversion_rate' => $metrics->conversionRate,
            'cart_abandonment_rate' => $metrics->cartAbandonmentRate,
            'sellers_active' => $metrics->sellersActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveHourlyMetrics(HourlyMetrics $metrics): void
    {
        $this->db->table('analytics_hourly_metrics')->insert([
            'tenant_id' => $metrics->tenantId,
            'hour' => $metrics->hour,
            'sessions' => $metrics->sessions,
            'page_views' => $metrics->pageViews,
            'orders_count' => $metrics->ordersCount,
            'orders_revenue' => $metrics->ordersRevenue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function upsertDailyMetrics(DailyMetrics $metrics): void
    {
        $this->db->table('analytics_daily_metrics')
            ->updateOrInsert(
                [
                    'tenant_id' => $metrics->tenantId,
                    'date' => $metrics->date->toDateString(),
                ],
                [
                    'sessions' => $metrics->sessions,
                    'page_views' => $metrics->pageViews,
                    'users_active' => $metrics->usersActive,
                    'users_new' => $metrics->usersNew,
                    'products_viewed' => $metrics->productsViewed,
                    'products_added_to_cart' => $metrics->productsAddedToCart,
                    'orders_count' => $metrics->ordersCount,
                    'orders_revenue' => $metrics->ordersRevenue,
                    'orders_aov' => $metrics->ordersAov,
                    'gmv' => $metrics->gmv,
                    'refunds_count' => $metrics->refundsCount,
                    'refunds_amount' => $metrics->refundsAmount,
                    'conversion_rate' => $metrics->conversionRate,
                    'cart_abandonment_rate' => $metrics->cartAbandonmentRate,
                    'sellers_active' => $metrics->sellersActive,
                    'updated_at' => now(),
                ]
            );
    }

    private function toDailyMetricsEntity(object $result): DailyMetrics
    {
        return new DailyMetrics(
            tenantId: $result->tenant_id,
            date: \Carbon\CarbonImmutable::parse($result->date),
            sessions: $result->sessions,
            pageViews: $result->page_views,
            usersActive: $result->users_active,
            usersNew: $result->users_new,
            productsViewed: $result->products_viewed,
            productsAddedToCart: $result->products_added_to_cart,
            ordersCount: $result->orders_count,
            ordersRevenue: (float) $result->orders_revenue,
            ordersAov: (float) $result->orders_aov,
            gmv: (float) $result->gmv,
            refundsCount: $result->refunds_count,
            refundsAmount: (float) $result->refunds_amount,
            conversionRate: (float) $result->conversion_rate,
            cartAbandonmentRate: (float) $result->cart_abandonment_rate,
            sellersActive: $result->sellers_active,
        );
    }

    private function toHourlyMetricsEntity(object $result): HourlyMetrics
    {
        return new HourlyMetrics(
            tenantId: $result->tenant_id,
            hour: \Carbon\CarbonImmutable::parse($result->hour),
            sessions: $result->sessions,
            pageViews: $result->page_views,
            ordersCount: $result->orders_count,
            ordersRevenue: (float) $result->orders_revenue,
        );
    }
}
