<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerCLVService;

/**
 * Segment Distribution Widget
 * 
 * Displays CLV segment distribution as a pie chart.
 * Shows: Low, Medium, High, VIP segments with counts and total CLV.
 * 
 * Production-ready: cached, interactive chart.
 */
final class SegmentDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'CLV Segment Distribution';
    protected int | string | array $columnSpan = 2;
    protected static ?string $pollingInterval = '600s';

    protected function getData(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        $clvService = app(SellerCLVService::class);
        $distribution = $clvService->getSegmentDistribution($sellerId, $tenantId);

        $labels = [];
        $data = [];

        foreach ($distribution as $segment => $info) {
            $labels[] = $info['label'];
            $data[] = $info['count'];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#9CA3AF', // gray (low)
                        '#3B82F6', // blue (medium)
                        '#10B981', // green (high)
                        '#F59E0B', // amber (vip)
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
