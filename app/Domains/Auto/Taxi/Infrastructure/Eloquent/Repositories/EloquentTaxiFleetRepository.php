<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories;

use App\Domains\Auto\Taxi\Domain\Entities\TaxiFleet as TaxiFleetEntity;
use App\Domains\Auto\Taxi\Domain\Repository\TaxiFleetRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\TaxiFleet as TaxiFleetModel;

/**
 * Class EloquentTaxiFleetRepository
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories
 */
final class EloquentTaxiFleetRepository implements TaxiFleetRepositoryInterface
{
    /**
     * Handle findById operation.
     *
     * @throws \DomainException
     */
    public function findById(TaxiFleetId $id): ?TaxiFleetEntity
    {
        $model = TaxiFleetModel::find($id->toString());
        return $model ? $this->toEntity($model) : null;
    }

    public function save(TaxiFleetEntity $fleet): void
    {
        $fleetData = $fleet->toArray();
        TaxiFleetModel::updateOrCreate(
            ['id' => $fleet->getId()->toString()],
            $fleetData
        );
    }

    private function toEntity(TaxiFleetModel $model): TaxiFleetEntity
    {
        // Note: Loading drivers would require an additional query or a relationship
        return new TaxiFleetEntity(
            id: new TaxiFleetId($model->id),
            tenantId: $model->tenant_id,
            name: $model->name,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}
