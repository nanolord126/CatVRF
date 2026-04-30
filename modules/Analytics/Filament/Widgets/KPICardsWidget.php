<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * KPI Cards Widget
 *
 * Displays key performance indicators for the seller.
 * Shows: GMV, Orders, AOV, Conversion Rate, Active Products, Revenue to Payout.
 *
 * Includes growth rates compared to previous period.
 */
class KPICardsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        if (!$sellerId) {
            return [];
        }

        $period = Period::last30Days();
        $kpiCards = app(SellerAnalyticsService::class)
            ->getKPICards($sellerId, $period, $tenantId);

        return array_map(function ($card) {
            $growthColor = match ($card['trend']) {
                'up' => 'success',
                'down' => 'danger',
                default => 'gray',
            };

            $description = $card['growth_rate'] !== null
                ? ($card['trend'] === 'up' ? '+' : '') . number_format($card['growth_rate'], 1) . '% vs previous'
                : null;

            $value = match ($card['format']) {
                'currency' => '$' . number_format($card['value'], 2),
                'percentage' => number_format($card['value'], 1) . '%',
                default => number_format($card['value']),
            };

            return Stat::make($card['label'], $value)
                ->description($description)
                ->descriptionIcon($card['icon'] ?? null)
                ->color($growthColor);
        }, $kpiCards);
    }

    /**
     * Get the widget refresh interval in seconds.
     */
    protected static function getRefreshInterval(): ?int
    {
        return 300; // Refresh every 5 minutes
    }
}
