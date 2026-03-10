<?php

namespace Modules\Analytics\Services;

use Modules\Geo\Models\GeoZone;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GeoFencingService
{
    /**
     * Вызывается при обновлении местоположения пользователя.
     */
    public function onUserLocationUpdate(User $user, float $lat, float $lng, string $correlationId): void
    {
        // Поиск активных геозон вокруг
        $zones = GeoZone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            // Упрощенная проверка вхождения в прямоугольник (в проде использовать PostGIS ST_Contains)
            if ($this->isPointInZone($lat, $lng, $zone)) {
                $this->triggerZoneOffer($user, $zone, $correlationId);
            }
        }
    }

    private function isPointInZone($lat, $lng, $zone): bool
    {
        // mock logic
        return ($lat > 40 && $lat < 60 && $lng > 30 && $lng < 90);
    }

    private function triggerZoneOffer(User $user, GeoZone $zone, string $correlationId): void
    {
        Log::info("GeoFencing trigger for User {$user->id} in Zone {$zone->name}");
        // Отправка оффера...
    }
}
