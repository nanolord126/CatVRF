<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Orders Trend Chart Widget
 *
 * Displays orders trend over time using Filament Charts.
 */
class OrdersTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Orders Trend';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        if (!$sellerId) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $period = Period::last30Days();
        $trends = app(SellerAnalyticsService::class)
            ->getTrends($sellerId, $period, $tenantId);

        $ordersTrend = $trends[1] ?? null;
        $labels = [];
        $data = [];

        if ($ordersTrend) {
            foreach ($ordersTrend->dataPoints ?? [] as $point) {
                $labels[] = $point['date'];
                $data[] = $point['value'];
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    /**
     * Get the widget refresh interval in seconds.
     */
    protected static function getRefreshInterval(): ?int
    {
        return 300; // Refresh every 5 minutes
    }
}
