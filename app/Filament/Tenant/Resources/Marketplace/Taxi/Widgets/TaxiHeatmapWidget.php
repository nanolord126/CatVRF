<?php

namespace App\Filament\Tenant\Resources\Marketplace\Taxi\Widgets;

use App\Models\Taxi\TaxiSurgeZone;
use App\Models\Taxi\TaxiCar;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TaxiHeatmapWidget extends Widget
{
    protected static string $view = 'filament.tenant.resources.marketplace.taxi.widgets.taxi-heatmap-widget';

    protected int | string | array $columnSpan = 'full';

    /**
     * Данные для отображения на карте:
     * 1. Активные машины (GPS/ГЛОНАСС)
     * 2. Зоны повышенного спроса (Surge Zones)
     * 3. ПРЕДСКАЗАНИЯ СПРОСА (AI Hotspots)
     */
    public function getMapData(): array
    {
        $forecasting = app(\App\Services\Taxi\TaxiDemandForecastingService::class);
        $predictions = $forecasting->predictHighProfitZones();

        return [
            'drivers' => TaxiCar::whereNotNull('current_location')
                ->where('last_sync_at', '>=', now()->subMinutes(5))
                ->get(['id', 'current_location', 'category'])
                ->map(fn($car) => [
                    'lat' => $car->current_location['coordinates'][1],
                    'lng' => $car->current_location['coordinates'][0],
                    'category' => $car->category,
                ]),
            'surge_zones' => TaxiSurgeZone::active()
                ->get(['id', 'name', 'polygon_coords', 'multiplier'])
                ->map(fn($zone) => [
                    'id' => $zone->id,
                    'multiplier' => $zone->multiplier,
                    'path' => $zone->polygon_coords, // Отправляем массив точек для рисования на Leaflet
                ]),
            'predictions' => $predictions, // Предиктивные точки прибыли
        ];
    }
}
