<?php

namespace App\Services\Taxi;

use App\Models\Taxi\TaxiTrip;
use App\Models\Taxi\TaxiCar;
use App\Models\Taxi\TaxiSurgeZone; // Будет создана модель
use Illuminate\Support\Facades\DB;

/**
 * Сервис динамического ценообразования.
 * Рассчитывает стоимость поездки с учетом тарифа (Economy/Comfort/Business),
 * наценки за спрос (Surge) и распределяет доход.
 */
class SurgePricingService
{
    /**
     * Рассчитывает финальную стоимость поездки.
     * При повышенном спросе делит наценку 50/50 между платформой и автопарком.
     */
    public function calculateFinalPrice(TaxiTrip $trip, string $category = 'economy')
    {
        // 1. Получаем базовые параметры тарифа (зависит от настроек в Бд)
        $tariff = [
            'economy' => ['base' => 100, 'km' => 20, 'min' => 5],
            'comfort' => ['base' => 150, 'km' => 30, 'min' => 7],
            'business' => ['base' => 300, 'km' => 60, 'min' => 15],
        ][$category] ?? ['base' => 100, 'km' => 20, 'min' => 5];

        // Симуляция дистанции и времени
        $distance = $trip->distance_km ?? 5.5; 
        $minutes = $trip->estimated_minutes ?? 15;

        $basePrice = $tariff['base'] + ($distance * $tariff['km']) + ($minutes * $tariff['min']);

        // 2. Определение коэффициента спроса (Surge)
        // Проверяем наличие активной геозоны спроса для начальной точки (lat, lon)
        $surgeZone = TaxiSurgeZone::where('is_active', true)
            // Мы используем PostGIS/MySQL Geo функции или полигоны в JSON
            ->whereJsonContains('polygon_coords', ['lat' => $trip->pickup_lat, 'lon' => $trip->pickup_lon])
            ->orderByDesc('multiplier')
            ->first();

        $multiplier = $surgeZone?->multiplier ?? 1.0;

        // 3. Распределение прибыли от наценки 50/50
        $finalPrice = $basePrice * $multiplier;
        $surgeMarkup = $finalPrice - $basePrice;

        $fleetSurgeShare = $surgeMarkup > 0 ? $surgeMarkup * 0.5 : 0;
        $platformSurgeShare = $surgeMarkup > 0 ? $surgeMarkup * 0.5 : 0;

        $trip->update([
            'tariff_category' => $category,
            'base_price' => $basePrice,
            'surge_multiplier' => $multiplier,
            'price' => $finalPrice,
            'surge_profit_fleet' => $fleetSurgeShare,
            'surge_profit_platform' => $platformSurgeShare,
            'correlation_id' => request()->header('X-Correlation-ID', uniqid()),
        ]);

        return [
            'total' => $finalPrice,
            'surge_multiplier' => $multiplier,
            'fleet_extra' => $fleetSurgeShare,
        ];
    }
}
