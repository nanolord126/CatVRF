<?php

namespace App\Filament\Widgets;

use App\Models\Tenants\TaxiDemandLog;
use Filament\Widgets\Widget;

class TaxiHeatmapWidget extends Widget
{
    protected static string $view = 'filament.widgets.taxi-heatmap-widget';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'demand_points' => TaxiDemandLog::latest()->take(100)->get()->map(fn ($log) => [
                'lat' => $log->location_geo['lat'] ?? 0,
                'lng' => $log->location_geo['lng'] ?? 0,
                'intensity' => 1
            ]),
        ];
    }
}
