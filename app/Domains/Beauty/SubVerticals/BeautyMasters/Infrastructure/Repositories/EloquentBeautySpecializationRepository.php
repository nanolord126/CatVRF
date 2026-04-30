<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\BeautySpecialization;
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;
use Modules\BeautyMasters\Domain\Repositories\BeautySpecializationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\BeautySpecializationModel;
use Illuminate\Support\Collection;

final readonly class EloquentBeautySpecializationRepository implements BeautySpecializationRepositoryInterface
{
    public function findById(int $id): ?BeautySpecialization
    {
        $model = BeautySpecializationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return BeautySpecializationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (BeautySpecializationModel $model) => $this->modelToEntity($model));
    }

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?BeautySpecialization
    {
        $model = BeautySpecializationModel::where('master_id', $masterId)
            ->where('specialization', $specialization)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(BeautySpecialization $specialization): BeautySpecialization
    {
        $data = [
            'master_id' => $specialization->masterId,
            'specialization' => $specialization->specialization,
            'level' => $specialization->level->value,
            'notes' => $specialization->notes,
        ];

        if ($specialization->id === 0) {
            $model = BeautySpecializationModel::create($data);
        } else {
            $model = BeautySpecializationModel::findOrFail($specialization->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return BeautySpecializationModel::destroy($id) > 0;
    }

    private function modelToEntity(BeautySpecializationModel $model): BeautySpecialization
    {
        return new BeautySpecialization(
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
