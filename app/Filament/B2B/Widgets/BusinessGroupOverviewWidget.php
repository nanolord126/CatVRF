<?php

declare(strict_types=1);

namespace App\Filament\B2B\Widgets;

use App\Filament\Widgets\BaseCachedWidget;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;

/**
 * Business Group Overview Widget
 *
 * Shows aggregated statistics for all tenants in a business group:
 * - Total revenue
 * - Active tenants
 * - Total orders
 * - User count
 */
final class BusinessGroupOverviewWidget extends BaseCachedWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {
        parent::__construct();
    }
    protected static string $cacheKeyPrefix = 'b2b_business_group_overview';

    protected static int $cacheTtl = 300; // 5 minutes

    protected function getStats(): array
    {
        $businessGroupId = $this->guard->user()?->business_group_id;

        if (! $businessGroupId) {
            return [
                Stat::make('Business Group', 'Not assigned'),
            ];
        }

        $stats = $this->getCachedData("group_{$businessGroupId}", function () use ($businessGroupId) {
            return [
                'total_revenue' => $this->getTotalRevenue($businessGroupId),
                'active_tenants' => $this->getActiveTenantsCount($businessGroupId),
                'total_orders' => $this->getTotalOrders($businessGroupId),
                'total_users' => $this->getTotalUsers($businessGroupId),
                'avg_order_value' => $this->getAvgOrderValue($businessGroupId),
            ];
        });

        return [
            Stat::make('Total Revenue', number_format($stats['total_revenue'], 2).' ₽')
                ->description('All tenants')
                ->descriptionIcon('heroicon-o-currency-ruble')
                ->color('success'),

            Stat::make('Active Tenants', $stats['active_tenants'])
                ->description('In business group')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Total Orders', $stats['total_orders'])
                ->description('All tenants')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info'),

            Stat::make('Total Users', $stats['total_users'])
                ->description('Across all tenants')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),

            Stat::make('Avg Order Value', number_format($stats['avg_order_value'], 2).' ₽')
                ->description('Per order')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),
        ];
    }

    private function getTotalRevenue(int $businessGroupId): float
    {
        $now = CarbonImmutable::now();

        return (float) $this->db->table('orders')
            ->join('tenants', 'orders.tenant_id', '=', 'tenants.id')
            ->where('tenants.business_group_id', $businessGroupId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$now->subMonth(), $now])
            ->sum('orders.total_amount');
    }

    private function getActiveTenantsCount(int $businessGroupId): int
    {
        return $this->db->table('tenants')
            ->where('business_group_id', $businessGroupId)
            ->where('is_active', true)
            ->count();
    }

    private function getTotalOrders(int $businessGroupId): int
    {
        $now = CarbonImmutable::now();

        return $this->db->table('orders')
            ->join('tenants', 'orders.tenant_id', '=', 'tenants.id')
            ->where('tenants.business_group_id', $businessGroupId)
            ->whereBetween('orders.created_at', [$now->subMonth(), $now])
            ->count();
    }

    private function getTotalUsers(int $businessGroupId): int
    {
        return $this->db->table('users')
            ->join('tenants', 'users.tenant_id', '=', 'tenants.id')
            ->where('tenants.business_group_id', $businessGroupId)
            ->where('users.is_active', true)
            ->count();
    }

    private function getAvgOrderValue(int $businessGroupId): float
    {
        $total = $this->getTotalRevenue($businessGroupId);
        $count = $this->getTotalOrders($businessGroupId);

        return $count > 0 ? $total / $count : 0;
    }
}
