<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\AIRecommendationsWidget;
use App\Filament\Tenant\Widgets\GeoHeatmapWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.tenant.pages.dashboard';

    protected static ?string $title = 'AI Terminal 2026';

    protected function getHeaderWidgets(): array
    {
        return [
            AIRecommendationsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            GeoHeatmapWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array | string
    {
        return 1;
    }
}
