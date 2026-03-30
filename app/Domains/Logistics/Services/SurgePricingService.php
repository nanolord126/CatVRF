<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgePricingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Расчет коэффициента Surge для точки и вертикали
         */
        public function calculateSurge(float $lat, float $lon, string $vertical): array
        {
            $cacheKey = "logistics:surge:{$lat}:{$lon}:{$vertical}:v1";

            return Cache::remember($cacheKey, 300, function () use ($lat, $lon, $vertical) {
                // 1. Поиск активной GeoZone для точки
                $geoZone = $this->findMatchingGeoZone($lat, $lon);

                if (!$geoZone) {
                    return ['multiplier' => 1.0, 'reason' => 'default', 'zone_id' => null];
                }

                // 2. Поиск активной SurgeZone для этой GeoZone
                $activeSurge = SurgeZone::where('geo_zone_id', $geoZone->id)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('active_from')->orWhere('active_from', '<=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('active_until')->orWhere('active_until', '>=', now());
                    })
                    ->orderBy('multiplier', 'desc')
                    ->first();

                if ($activeSurge) {
                    return [
                        'multiplier' => (float)$activeSurge->multiplier,
                        'reason' => $activeSurge->reason,
                        'zone_id' => $activeSurge->id,
                        'geo_zone_id' => $geoZone->id
                    ];
                }

                // 3. Базовый расчет (например, по времени суток)
                $timeMultiplier = $this->calculateTimeBasedSurge();

                return [
                    'multiplier' => $timeMultiplier,
                    'reason' => 'time_based',
                    'zone_id' => null,
                    'geo_zone_id' => $geoZone->id
                ];
            });
        }

        /**
         * Создание временного Surge-пика (например, дождь)
         */
        public function createDynamicSurge(int $geoZoneId, float $multiplier, string $reason, int $minutes, string $correlationId): SurgeZone
        {
            $surge = SurgeZone::create([
                'geo_zone_id' => $geoZoneId,
                'multiplier' => $multiplier,
                'reason' => $reason,
                'is_active' => true,
                'active_from' => now(),
                'active_until' => now()->addMinutes($minutes),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Dynamic surge created', [
                'geo_zone_id' => $geoZoneId,
                'multiplier' => $multiplier,
                'reason' => $reason,
                'correlation_id' => $correlationId
            ]);

            // Инвалидация кэша для этой зоны (упрощенно — всей логистики)
            Cache::tags(['logistics_surge'])->flush();

            return $surge;
        }

        private function findMatchingGeoZone(float $lat, float $lon): ?GeoZone
        {
            // В идеале — Geo-запрос в БД (ST_Contains)
            // Здесь — упрощенно перебираем активные зоны
            return GeoZone::where('is_active', true)
                ->get()
                ->first(fn($zone) => $zone->containsPoint($lat, $lon));
        }

        private function calculateTimeBasedSurge(): float
        {
            $hour = (int)now()->format('H');

            // Утренний пик 08-10
            if ($hour >= 8 && $hour <= 10) return 1.3;

            // Вечерний пик 18-20
            if ($hour >= 18 && $hour <= 20) return 1.5;

            // Поздний вечер 22-00
            if ($hour >= 22 || $hour <= 1) return 1.2;

            return 1.0;
        }

        /**
         * Получить все активные зоны для тепловой карты
         */
        public function getActiveSurgeMap(): Collection
        {
            return SurgeZone::with('geoZone')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('active_until')->orWhere('active_until', '>=', now());
                })
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'multiplier' => $s->multiplier,
                    'polygon' => $s->geoZone->polygon ?? [],
                    'center' => $s->geoZone->center_point ?? [],
                    'reason' => $s->reason
                ]);
        }
}
