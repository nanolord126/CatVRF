<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;

class GeoHeatmapWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.geo-heatmap-widget';
    protected int | string | array $columnSpan = 'full';

    public function getHeatmapData(): array
    {
        // В реальном приложении здесь будет выборка из БД по тенанту
        return [
            ['lat' => 55.7558, 'lng' => 37.6173, 'count' => 10],
            ['lat' => 55.7658, 'lng' => 37.6273, 'count' => 5],
            ['lat' => 55.7458, 'lng' => 37.6073, 'count' => 8],
        ];
    }
}
