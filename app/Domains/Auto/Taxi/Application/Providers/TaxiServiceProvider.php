<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\Providers;

use App\Domains\Auto\Taxi\Domain\Repository\DriverRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Repository\TaxiFleetRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Repository\VehicleRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Services\GeoLogisticsServiceInterface;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories\EloquentDriverRepository;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories\EloquentRideRepository;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories\EloquentTaxiFleetRepository;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories\EloquentVehicleRepository;
use App\Domains\Auto\Taxi\Infrastructure\Services\FakeGeoLogisticsService;
use Illuminate\Support\ServiceProvider;

/**
 * Class TaxiServiceProvider
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
 * @package App\Domains\Auto\Taxi\Application\Providers
 */
final class TaxiServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // B2C
        RideRepositoryInterface::class => EloquentRideRepository::class,

        // B2B
        DriverRepositoryInterface::class      => EloquentDriverRepository::class,
        VehicleRepositoryInterface::class     => EloquentVehicleRepository::class,
        TaxiFleetRepositoryInterface::class   => EloquentTaxiFleetRepository::class,

        // Infrastructure services
        GeoLogisticsServiceInterface::class   => FakeGeoLogisticsService::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
