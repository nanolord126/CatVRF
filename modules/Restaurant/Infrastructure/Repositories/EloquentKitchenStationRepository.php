<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Restaurant\Domain\Entities\KitchenStation;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\Repositories\KitchenStationRepositoryInterface;
use Modules\Restaurant\Infrastructure\Models\KitchenStationModel;

final readonly class EloquentKitchenStationRepository implements KitchenStationRepositoryInterface
{
    public function findById(int $id): ?KitchenStation
    {
        $model = KitchenStationModel::find($id);
        return $model?->toDomain();
    }

    public function findByType(KitchenStationType $type): Collection
    {
        return KitchenStationModel::where('type', $type)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (KitchenStationModel $model) => $model->toDomain());
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return KitchenStationModel::where('tenant_id', $tenantId)
            ->orderBy('display_order')
            ->get()
            ->map(fn (KitchenStationModel $model) => $model->toDomain());
    }

    public function save(KitchenStation $station): void
    {
        $model = KitchenStationModel::fromDomain($station);
        $model->save();
    }

    public function delete(int $id): void
    {
        KitchenStationModel::findOrFail($id)->delete();
    }

    public function getActiveStations(): Collection
    {
        return KitchenStationModel::where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (KitchenStationModel $model) => $model->toDomain());
    }
}
