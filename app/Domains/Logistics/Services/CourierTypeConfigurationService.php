<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Enums\CourierType;
use App\Domains\Logistics\Models\CourierTypeConfiguration;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Collection;

/**
 * Service for managing courier type configurations with caching.
 *
 * Production Strategy:
 * - Cache configurations by tenant/city/type for fast lookup
 * - Support city-specific overrides with fallback to defaults
 * - Invalidate cache on configuration changes
 */
final readonly class CourierTypeConfigurationService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Get configuration for courier type (with caching).
     */
    public function getConfiguration(
        int $tenantId,
        string $type,
        ?string $city = null,
    ): ?CourierTypeConfiguration {
        $cacheKey = $this->getCacheKey($tenantId, $type, $city);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $type, $city) {
            return CourierTypeConfiguration::query()
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->where('is_active', true)
                ->where(function ($q) use ($city) {
                    $q->where('city', $city)->orWhereNull('city');
                })
                ->orderBy('city', 'desc') // Prefer city-specific
                ->first();
        });
    }

    /**
     * Get all active configurations for tenant.
     */
    public function getAllConfigurations(int $tenantId, ?string $city = null): Collection
    {
        $query = CourierTypeConfiguration::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($city !== null) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)->orWhereNull('city');
            });
        }

        return $query->orderBy('priority')->get();
    }

    /**
     * Get configurations suitable for order constraints.
     */
    public function getSuitableTypes(
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        ?string $city = null,
    ): Collection {
        $configs = $this->getAllConfigurations($tenantId, $city);

        return $configs->filter(function ($config) use ($weightKg) {
            return $config->max_weight_kg >= $weightKg
                && $config->max_radius_km >= $distanceKg;
        });
    }

    /**
     * Create or update configuration.
     */
    public function upsertConfiguration(
        int $tenantId,
        string $type,
        array $data,
        ?string $city = null,
        string $correlationId = '',
    ): CourierTypeConfiguration {
        $this->logger->$this->logger->info('Upserting courier type configuration', [
            'tenant_id' => $tenantId,
            'type' => $type,
            'city' => $city,
            'correlation_id' => $correlationId,
        ]);

        $config = CourierTypeConfiguration::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'type' => $type,
                'city' => $city,
            ],
            array_merge($data, [
                'tenant_id' => $tenantId,
                'type' => $type,
                'city' => $city,
            ])
        );

        // Invalidate cache
        $this->invalidateCache($tenantId, $type, $city);

        return $config;
    }

    /**
     * Delete configuration.
     */
    public function deleteConfiguration(
        int $tenantId,
        string $type,
        ?string $city = null,
        string $correlationId = '',
    ): void {
        $this->logger->$this->logger->info('Deleting courier type configuration', [
            'tenant_id' => $tenantId,
            'type' => $type,
            'city' => $city,
            'correlation_id' => $correlationId,
        ]);

        CourierTypeConfiguration::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('city', $city)
            ->delete();

        // Invalidate cache
        $this->invalidateCache($tenantId, $type, $city);
    }

    /**
     * Get default configuration from enum (fallback).
     */
    public function getDefaultConfiguration(string $type): ?array
    {
        $courierType = CourierType::tryFrom($type);

        if ($courierType === null) {
            return null;
        }

        return [
            'max_radius_km' => $courierType->getMaxRadiusKm(),
            'max_weight_kg' => $courierType->getMaxWeightKg(),
            'avg_speed_kmh' => $courierType->getAvgSpeedKmh(),
            'max_delivery_time_min' => $courierType->getMaxDeliveryTimeMin(),
            'parking_time_min' => $courierType->getParkingTimeMin(),
            'battery_threshold' => $courierType->getBatteryThreshold(),
            'requires_battery' => $courierType->requiresBattery(),
            'cost_multiplier' => $courierType->getCostMultiplier(),
        ];
    }

    /**
     * Invalidate cache for configuration.
     */
    private function invalidateCache(int $tenantId, string $type, ?string $city = null): void
    {
        $cacheKey = $this->getCacheKey($tenantId, $type, $city);
        $this->cache->forget($cacheKey);
    }

    /**
     * Generate cache key.
     */
    private function getCacheKey(int $tenantId, string $type, ?string $city = null): string
    {
        return sprintf('courier_type_config:%d:%s:%s', $tenantId, $type, $city ?? 'default');
    }
}
