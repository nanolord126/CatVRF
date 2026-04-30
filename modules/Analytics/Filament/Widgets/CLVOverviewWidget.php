<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerCLVService;

/**
 * CLV Overview Widget
 * 
 * Displays key CLV metrics for seller dashboard:
 * - Total predicted CLV (180d and 365d)
 * - Total buyers with predictions
 * - VIP customer count
 * - High churn risk count
 * 
 * Production-ready: cached, tenant-aware, real-time updates.
 */
final class CLVOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '300s'; // Refresh every 5 minutes

    protected function getStats(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1; // Multi-tenant

        $clvService = app(SellerCLVService::class);
        $metrics = $clvService->getAggregatedCLVMetrics($sellerId, $tenantId);

        return [
            Stat::make('Total CLV (180 days)', number_format($metrics['total_clv_180d'], 2) . ' ₽')
                ->description('Predicted revenue from all buyers')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Total CLV (365 days)', number_format($metrics['total_clv_365d'], 2) . ' ₽')
                ->description('Annual prediction')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary'),

            Stat::make('Total Buyers', $metrics['total_buyers'])
                ->description('With CLV predictions')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('VIP Customers', $metrics['vip_count'])
                ->description('High-value segment')
                ->descriptionIcon('heroicon-o-star')
                ->color('warning'),

            Stat::make('High Churn Risk', $metrics['high_churn_count'])
                ->description('Buyers at risk of leaving')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Avg CLV per Buyer', number_format($metrics['avg_clv_180d'], 2) . ' ₽')
                ->description('180-day average')
                ->descriptionIcon('heroicon-o-trending-up')
                ->color('gray'),
        ];
    }
}
