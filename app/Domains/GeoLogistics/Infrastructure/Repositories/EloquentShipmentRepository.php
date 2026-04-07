<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Infrastructure\Repositories;

use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use App\Domains\GeoLogistics\Domain\Models\Shipment;

/**
 * Class EloquentShipmentRepository
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\GeoLogistics\Infrastructure\Repositories
 */
final class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    /**
     * Handle findById operation.
     *
     * @throws \DomainException
     */
    public function findById(int $id): ?Shipment
    {
        return Shipment::find($id);
    }

    /**
     * Handle save operation.
     *
     * @throws \DomainException
     */
    public function save(Shipment $shipment): void
    {
        $shipment->save();
    }
}
