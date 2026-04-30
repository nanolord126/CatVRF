<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Repositories;

use Modules\Contraindications\Domain\Entities\Contraindication;
use Modules\Contraindications\Domain\Enums\ContraindicationSeverity;
use Modules\Contraindications\Domain\Repositories\ContraindicationRepositoryInterface;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use Modules\Contraindications\Infrastructure\Models\ContraindicationModel;

final class ContraindicationRepository implements ContraindicationRepositoryInterface
{
    public function findByUserId(int $userId): array
    {
        $models = ContraindicationModel::where('user_id', $userId)->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByPetId(int $petId): array
    {
        $models = ContraindicationModel::where('pet_id', $petId)->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function findActiveByUserId(int $userId): array
    {
        $models = ContraindicationModel::where('user_id', $userId)
            ->active()
            ->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function findActiveByPetId(int $petId): array
    {
        $models = ContraindicationModel::where('pet_id', $petId)
            ->active()
            ->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function findRelevantForUser(int $userId, Scope $scope): array
    {
        $models = ContraindicationModel::where('user_id', $userId)
            ->active()
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]'); // Empty scopes = applicable to all
            })
            ->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function findRelevantForPet(int $petId, Scope $scope): array
    {
        $models = ContraindicationModel::where('pet_id', $petId)
            ->active()
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]'); // Empty scopes = applicable to all
            })
            ->get();

        return $models->map(fn (ContraindicationModel $model) => $this->toEntity($model))->toArray();
    }

    public function save(Contraindication $contraindication): void
    {
        $model = ContraindicationModel::updateOrCreate(
            ['id' => $contraindication->id],
            [
                'tenant_id' => $contraindication->tenantId,
                'user_id' => $contraindication->userId,
                'pet_id' => $contraindication->petId,
                'name' => $contraindication->name,
                'description' => $contraindication->description,
                'severity' => $contraindication->severity->value,
                'scopes' => $contraindication->scopes,
                'is_active' => $contraindication->isActive,
            ]
        );
    }

    public function delete(int $id): void
    {
        ContraindicationModel::findOrFail($id)->delete();
    }

    private function toEntity(ContraindicationModel $model): Contraindication
    {
        return new Contraindication(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            petId: $model->pet_id,
            name: $model->name,
            description: $model->description,
            severity: ContraindicationSeverity::from($model->severity),
            scopes: $model->scopes,
            isActive: $model->is_active,
        );
    }
}
