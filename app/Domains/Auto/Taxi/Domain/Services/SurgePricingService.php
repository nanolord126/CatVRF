<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Services;

use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;
use Illuminate\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Class SurgePricingService
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Auto\Taxi\Domain\Services
 */
final readonly class SurgePricingService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}

    private const CACHE_TTL = 300;
    private const DEFAULT_MULTIPLIER = 1.0;

    /**
     * Calculate the surge pricing multiplier for a given pickup location.
     *
     * @param Coordinate $pickup Pickup location coordinates
     * @return float Surge multiplier (1.0 = no surge)
     */
    public function getMultiplier(Coordinate $pickup): float
    {
        $key = sprintf(
            'taxi:surge:%.4f:%.4f',
            round($pickup->latitude, 2),
            round($pickup->longitude, 2),
        );

        return $this->cache->remember($key, self::CACHE_TTL, function () use ($pickup) {
            $zone = $this->db->table('taxi_surge_zones')
                ->whereRaw('ST_Contains(polygon, ST_GeomFromText(?, 4326))', [
                    "POINT({$pickup->longitude} {$pickup->latitude})",
                ])
                ->orderByDesc('surge_multiplier')
                ->value('surge_multiplier');

            return $zone ? (float) $zone : self::DEFAULT_MULTIPLIER;
        });
    }

    /**
     * Apply surge multiplier to base price and return adjusted price in kopecks.
     *
     * @param int $basePrice Base trip price in kopecks
     * @param float $multiplier Surge multiplier (>= 1.0)
     * @return int Final price after applying surge, in kopecks
     */
    public function applyMultiplier(int $basePrice, float $multiplier): int
    {
        $finalPrice = (int) ceil($basePrice * $multiplier);

        if ($multiplier > self::DEFAULT_MULTIPLIER) {
            $this->logger->info('Surge pricing applied to trip', [
                'base_price' => $basePrice,
                'multiplier' => $multiplier,
                'final_price' => $finalPrice,
            ]);
        }

        return $finalPrice;
    }
}
