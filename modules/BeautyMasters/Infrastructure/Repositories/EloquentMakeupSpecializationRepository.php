<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\MakeupSpecialization;
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;
use Modules\BeautyMasters\Domain\Repositories\MakeupSpecializationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\MakeupSpecializationModel;
use Illuminate\Support\Collection;

final readonly class EloquentMakeupSpecializationRepository implements MakeupSpecializationRepositoryInterface
{
    public function findById(int $id): ?MakeupSpecialization
    {
        $model = MakeupSpecializationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return MakeupSpecializationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (MakeupSpecializationModel $model) => $this->modelToEntity($model));
    }

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?MakeupSpecialization
    {
        $model = MakeupSpecializationModel::where('master_id', $masterId)
            ->where('specialization', $specialization)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(MakeupSpecialization $specialization): MakeupSpecialization
    {
        $data = [
            'master_id' => $specialization->masterId,
            'specialization' => $specialization->specialization,
            'level' => $specialization->level->value,
            'notes' => $specialization->notes,
        ];

        if ($specialization->id === 0) {
            $model = MakeupSpecializationModel::create($data);
        } else {
            $model = MakeupSpecializationModel::findOrFail($specialization->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return MakeupSpecializationModel::destroy($id) > 0;
    }

    private function modelToEntity(MakeupSpecializationModel $model): MakeupSpecialization
    {
        return new MakeupSpecialization(
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
