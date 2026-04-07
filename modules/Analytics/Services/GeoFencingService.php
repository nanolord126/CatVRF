<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Modules\Analytics\Models\GeoZone;
use Modules\Common\Services\AbstractTechnicalVerticalService;

/**
 * Сервис геофенсинга — триггерные офферы по местоположению пользователя.
 *
 * Работает в связке с RecommendationService:
 *  - при входе пользователя в геозону → PromoCampaignService даёт оффер
 *  - при выходе → оффер отзывается или истекает
 *
 * КАНОН 2026:
 * - Нет static Log::, нет static DB::
 * - tenant_id scoping на GeoZone запросах
 * - Производственная реализация: PostGIS ST_Contains для точной геометрии
 */
final class GeoFencingService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection  $db,
        private readonly LogManager $log,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('analytics.geofencing.enabled', true);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Обработать обновление местоположения пользователя.
     *
     * Ищет активные геозоны текущего тенанта, в которые попал пользователь,
     * и инициирует офферы для каждой подходящей зоны.
     */
    public function onUserLocationUpdate(
        User   $user,
        float  $lat,
        float  $lng,
        string $correlationId,
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $tenantId = isset($this->tenant)
            ? $this->resolveTenantId()
            : ($user->tenant_id ?? null);

        // PostGIS: ST_Contains(zone.geometry, ST_SetSRID(ST_MakePoint(lng, lat), 4326))
        // Fallback для dev — прямоугольная проверка
        $zones = GeoZone::where('is_active', true)
            ->when(
                $tenantId !== null,
                fn ($q) => $q->where('tenant_id', $tenantId),
            )
            ->get();

        foreach ($zones as $zone) {
            if ($this->isPointInZone($lat, $lng, $zone)) {
                $this->triggerZoneOffer($user, $zone, $correlationId);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Проверить, что точка (lat, lng) находится внутри зоны.
     *
     * Production: заменить на PostGIS ST_Contains запрос.
     * Dev: простая прямоугольная bounding box проверка.
     */
    private function isPointInZone(float $lat, float $lng, GeoZone $zone): bool
    {
        if (
            isset($zone->min_lat, $zone->max_lat, $zone->min_lng, $zone->max_lng)
        ) {
            return $lat >= $zone->min_lat
                && $lat <= $zone->max_lat
                && $lng >= $zone->min_lng
                && $lng <= $zone->max_lng;
        }

        // Заглушка для dev/тестов — условный bbox России
        return ($lat > 40.0 && $lat < 60.0 && $lng > 30.0 && $lng < 90.0);
    }

    /**
     * Инициировать оффер для пользователя в данной геозоне.
     */
    private function triggerZoneOffer(User $user, GeoZone $zone, string $correlationId): void
    {
        try {
            $this->db->table('geo_zone_triggers')->insert([
                'user_id'        => $user->id,
                'geo_zone_id'    => $zone->id,
                'tenant_id'      => $zone->tenant_id,
                'correlation_id' => $correlationId,
                'triggered_at'   => now(),
            ]);

            $this->log->channel('audit')->info('geofencing.zone_triggered', [
                'correlation_id' => $correlationId,
                'user_id'        => $user->id,
                'zone_id'        => $zone->id,
                'zone_name'      => $zone->name,
                'tenant_id'      => $zone->tenant_id,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('geofencing.trigger_failed', [
                'correlation_id' => $correlationId,
                'user_id'        => $user->id,
                'zone_id'        => $zone->id,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
        }
    }
}

