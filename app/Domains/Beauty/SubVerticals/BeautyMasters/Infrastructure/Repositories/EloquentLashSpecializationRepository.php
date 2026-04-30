<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashSpecialization;
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;
use Modules\BeautyMasters\Domain\Repositories\LashSpecializationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\LashSpecializationModel;
use Illuminate\Support\Collection;

final readonly class EloquentLashSpecializationRepository implements LashSpecializationRepositoryInterface
{
    public function findById(int $id): ?LashSpecialization
    {
        $model = LashSpecializationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return LashSpecializationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (LashSpecializationModel $model) => $this->modelToEntity($model));
    }

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?LashSpecialization
    {
        $model = LashSpecializationModel::where('master_id', $masterId)
            ->where('specialization', $specialization)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(LashSpecialization $specialization): LashSpecialization
    {
        $data = [
            'master_id' => $specialization->masterId,
            'specialization' => $specialization->specialization,
            'level' => $specialization->level->value,
            'notes' => $specialization->notes,
        ];

        if ($specialization->id === 0) {
            $model = LashSpecializationModel::create($data);
        } else {
            $model = LashSpecializationModel::findOrFail($specialization->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return LashSpecializationModel::destroy($id) > 0;
    }

    private function modelToEntity(LashSpecializationModel $model): LashSpecialization
    {
        return new LashSpecialization(
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
