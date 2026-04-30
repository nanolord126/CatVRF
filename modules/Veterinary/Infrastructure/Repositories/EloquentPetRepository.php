<?php

declare(strict_types=1);

namespace Modules\Veterinary\Infrastructure\Repositories;

use Modules\Veterinary\Domain\Entities\Pet;
use Modules\Veterinary\Domain\Repositories\PetRepositoryInterface;
use Modules\Veterinary\Infrastructure\Models\PetModel;

class EloquentPetRepository implements PetRepositoryInterface
{
    public function create(array $data): Pet
    {
        $model = PetModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): Pet
    {
        $model = PetModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?Pet
    {
        $model = PetModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?Pet
    {
        $model = PetModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return PetModel::where('owner_id', $ownerId)
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByChipNumber(string $chipNumber): ?Pet
    {
        $model = PetModel::where('chip_number', $chipNumber)->first();
        return $model?->toDomain();
    }
}
