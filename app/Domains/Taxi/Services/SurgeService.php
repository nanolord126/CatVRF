<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Получить коэффициент для точки (lat, lon).
         */
        public function getMultiplierAtPoint(float $lat, float $lon, int $tenantId): float
        {
            $cacheKey = "surge_multiplier_{$tenantId}_{$lat}_{$lon}";

            return Cache::remember($cacheKey, 60, function () use ($lat, $lon, $tenantId) {
                $activeZones = SurgeZone::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->get();

                $maxMultiplier = 1.0;

                foreach ($activeZones as $zone) {
                    if ($zone->containsPoint($lat, $lon)) {
                        $maxMultiplier = max($maxMultiplier, $zone->multiplier);
                    }
                }

                Log::channel('audit')->info('Surge multiplier calculated', [
                    'tenant_id' => $tenantId,
                    'lat' => $lat,
                    'lon' => $lon,
                    'multiplier' => $maxMultiplier
                ]);

                return $maxMultiplier;
            });
        }

        /**
         * Активация зоны (вызывается из AI-оптимизатора или вручную).
         */
        public function activateZone(int $zoneId, float $multiplier, int $minutes): void
        {
            $zone = SurgeZone::findOrFail($zoneId);
            $zone->update([
                'multiplier' => $multiplier,
                'is_active' => true,
                'expires_at' => now()->addMinutes($minutes)
            ]);

            Log::channel('audit')->info('Surge zone activated', [
                'zone_id' => $zoneId,
                'multiplier' => $multiplier,
                'expires_at' => $zone->expires_at
            ]);
        }
}
