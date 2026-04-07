<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\GeoLogistics\Domain\Contracts\GeoRoutingServiceInterface;
use App\Domains\GeoLogistics\Infrastructure\Services\OsrmRoutingService;
use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use App\Domains\GeoLogistics\Infrastructure\Repositories\EloquentShipmentRepository;

/**
 * Class GeoLogisticsServiceProvider
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
 * @package App\Providers
 */
final class GeoLogisticsServiceProvider extends ServiceProvider
{
    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
    {
        $this->app->bind(GeoRoutingServiceInterface::class, OsrmRoutingService::class);
        $this->app->bind(ShipmentRepositoryInterface::class, EloquentShipmentRepository::class);
    }

    /**
     * Handle boot operation.
     *
     * @throws \DomainException
     */
    public function boot(): void
    {
        // ...
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
