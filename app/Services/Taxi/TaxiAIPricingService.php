<?php

namespace App\Services\Taxi;

use App\Domains\Taxi\Models\TaxiSurgeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TaxiAIPricingService
{
    /**
     * Динамическое ценообразование 2026.
     * Кэширование расчетов базовой стоимости и Surge (60 сек) для High-Load.
     */
    public function calculate(float $distanceKm, string $class, ?float $lat = null, ?float $lng = null): array
    {
        $cacheKey = "taxi_price_{$class}_{$distanceKm}_" . ($lat ?? 0) . "_" . ($lng ?? 0);
        
        return Cache::remember($cacheKey, 60, function () use ($distanceKm, $class, $lat, $lng) {
            $baseTariffs = [
                'economy' => 100.0, 
                'comfort' => 250.0,
                'business' => 500.0,
                'vip' => 1500.0,
                'cargo' => 800.0
            ];

            return $this->performCalculation($distanceKm, $class, $lat, $lng, $baseTariffs);
        });
    }

    private function performCalculation(float $distanceKm, string $class, ?float $lat, ?float $lng, array $baseTariffs): array
    {
        $perKmRates = [
            'economy' => 20.0,
            'comfort' => 45.0,
            'business' => 85.0,
            'vip' => 250.0,
            'cargo' => 120.0
        ];

        $baseAmount = ($baseTariffs[$class] ?? 100.0) + ($distanceKm * ($perKmRates[$class] ?? 20.0));
        
        // 1. Учет времени суток (Ночной тариф +25%, Час-пик +35%)
        $timeMultiplier = $this->getTimeMultiplier();
        $baseAmount *= $timeMultiplier;

        // 2. Surge (динамический спрос в гео-зоне)
        $surgeMultiplier = 1.0;
        if ($lat && $lng) {
             $surgeMultiplier = $this->getActiveSurge($lat, $lng);
        }
        
        $finalAmount = $baseAmount * $surgeMultiplier;

        return [
            'amount' => round($finalAmount, 2),
            'surge' => $surgeMultiplier,
            'time_factor' => $timeMultiplier,
            'base' => $baseAmount,
            'calculation_id' => bin2hex(random_bytes(8)),
            'timestamp' => Carbon::now()->toISOString()
        ];
    }

    private function getTimeMultiplier(): float
    {
        $hour = Carbon::now()->hour;
        if ($hour >= 0 && $hour < 6) return 1.25; // Ночь
        if (($hour >= 8 && $hour < 11) || ($hour >= 17 && $hour < 20)) return 1.35; // Час пик
        return 1.0;
    }

    private function getActiveSurge(float $lat, float $lng): float
    {
        // Поиск активной зоны "всплеска спроса" вокруг точки с использованием гео-расстояния
        return TaxiSurgeZone::where('is_active', true)
            ->whereRaw(
                'ST_Distance_Sphere(center_point, ST_GeomFromText(?, 4326)) < radius',
                ["POINT($lng $lat)"]
            )
            ->orderBy('multiplier', 'desc')
            ->first()?->multiplier ?? 1.0;
    }
}
