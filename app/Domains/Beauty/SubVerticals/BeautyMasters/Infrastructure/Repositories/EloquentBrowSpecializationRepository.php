<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowSpecialization;
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;
use Modules\BeautyMasters\Domain\Repositories\BrowSpecializationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\BrowSpecializationModel;
use Illuminate\Support\Collection;

final readonly class EloquentBrowSpecializationRepository implements BrowSpecializationRepositoryInterface
{
    public function findById(int $id): ?BrowSpecialization
    {
        $model = BrowSpecializationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return BrowSpecializationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (BrowSpecializationModel $model) => $this->modelToEntity($model));
    }

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?BrowSpecialization
    {
        $model = BrowSpecializationModel::where('master_id', $masterId)
            ->where('specialization', $specialization)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(BrowSpecialization $specialization): BrowSpecialization
    {
        $data = [
            'master_id' => $specialization->masterId,
            'specialization' => $specialization->specialization,
            'level' => $specialization->level->value,
            'notes' => $specialization->notes,
        ];

        if ($specialization->id === 0) {
            $model = BrowSpecializationModel::create($data);
        } else {
            $model = BrowSpecializationModel::findOrFail($specialization->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return BrowSpecializationModel::destroy($id) > 0;
    }

    private function modelToEntity(BrowSpecializationModel $model): BrowSpecialization
    {
        return new BrowSpecialization(
            id: $model->id,
            masterId: $model->master_id,
            specialization: $model->specialization,
            level: SpecializationLevel::from($model->level),
            notes: $model->notes,
            createdAt: new \DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null,
        );
    }
}
