<?php

namespace App\Filament\B2B\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\B2B\B2BAIAnalyticsService;
use App\Models\Tenant;

class B2BDemandForecastChart extends ChartWidget
{
    protected static ?string $heading = '30-Day B2B Demand Forecast (AI-ML 2026)';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(\App\Services\B2B\B2BAIAnalyticsService::class);
        $tenant = Tenant::first(); // In actual session, get current tenant()

        // Forecast for primary categories
        $categories = ['Medical Supplies', 'Pet Nutrition', 'Industrial Lab Equip'];
        $forecasts = [];
        
        foreach ($categories as $cat) {
            $forecasts[] = $service->forecastDemand($cat, $tenant)['predicted_30d_volume'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Predicted Units Needed',
                    'data' => $forecasts,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                ],
            ],
            'labels' => $categories,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
