<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Presentation\Filament\Widgets;

use GetAnalyticsDashboardDataUseCase;

use Carbon\CarbonImmutable;

use App\Domains\Analytics\Application\UseCases\GetAnalyticsDashboardDataUseCase;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Class PageViewsChart
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class PageViewsChart extends ChartWidget
{
    protected static ?string $heading = 'Page Views';

    protected function getData(): array
    {
        $useCase = $this->getAnalyticsDashboardDataUseCase /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $data = $useCase->execute(
            filament()->getTenant()->id,
            'page_views',
            CarbonImmutable::now()->subDays(30),
            CarbonImmutable::now(),
            'toStartOfDay(created_at)'
        );

        return [
            'datasets' => [
                [
                    'label' => 'Page Views',
                    'data' => $data->pluck('value')->all(),
                ],
            ],
            'labels' => $data->pluck('group')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
