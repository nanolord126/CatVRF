<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources;

use Filament\Widgets;
use Filament\Pages;
use Filament\Support\Enums\IconPosition;

final class CertificationDashboardResource extends Pages\Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Beauty Masters';

    protected static ?string $navigationLabel = 'Certification Dashboard';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.resources.certification-dashboard-resource.pages.view';

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\StatsOverviewWidget::make([
                \Modules\BeautyMasters\Filament\Widgets\CertificationStatsWidget::class,
            ]),
            \Modules\BeautyMasters\Filament\Widgets\ExpiringCertificationsWidget::class,
            \Modules\BeautyMasters\Filament\Widgets\CertificationByVerticalWidget::class,
        ];
    }
}
