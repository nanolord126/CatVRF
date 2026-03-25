declare(strict_types=1);

<?php

namespace Modules\Analytics\Services;

use Modules\Geo\Models\GeoZone;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * GeoFencingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GeoFencingService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
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
        $this->log->info("GeoFencing trigger for User {$user->id} in Zone {$zone->name}");
        // Отправка оффера...
    }
}
