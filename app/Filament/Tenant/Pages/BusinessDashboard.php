<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\B2BAnalyticsWidget;
use App\Filament\Tenant\Widgets\EmployeeWorkloadHeatmap;
use App\Filament\Tenant\Widgets\GeoHeatmapWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class BusinessDashboard extends BaseDashboard
{
    protected static ?string $title = 'Business Insights Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $routePath = '/business-insights';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Tenant\Widgets\B2BAnalyticsWidget::class,
            \App\Filament\Tenant\Widgets\EmployeeWorkloadHeatmap::class,
            \App\Filament\Tenant\Widgets\CategoryExpenseHeatmap::class,
            \App\Filament\Tenant\Widgets\GeoHeatmapWidget::class,
        ];
    }
}
