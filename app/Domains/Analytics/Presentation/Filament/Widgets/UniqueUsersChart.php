<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Presentation\Filament\Widgets;

use GetAnalyticsDashboardDataUseCase;

use Carbon\CarbonImmutable;

use App\Domains\Analytics\Application\UseCases\GetAnalyticsDashboardDataUseCase;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Class UniqueUsersChart
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class UniqueUsersChart extends ChartWidget
{
    protected static ?string $heading = 'Unique Users';

    protected function getData(): array
    {
        $useCase = $this->getAnalyticsDashboardDataUseCase /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $data = $useCase->execute(
            filament()->getTenant()->id,
            'unique_users',
            CarbonImmutable::now()->subDays(30),
            CarbonImmutable::now(),
            'toStartOfDay(created_at)'
        );

        return [
            'datasets' => [
                [
                    'label' => 'Unique Users',
                    'data' => $data->pluck('value')->all(),
                ],
            ],
            'labels' => $data->pluck('group')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
