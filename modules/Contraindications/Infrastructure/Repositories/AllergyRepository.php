<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Repositories;

use Modules\Contraindications\Domain\Entities\Allergy;
use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Domain\Repositories\AllergyRepositoryInterface;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use Modules\Contraindications\Infrastructure\Models\AllergyModel;

final class AllergyRepository implements AllergyRepositoryInterface
{
    public function findByUserId(int $userId): array
    {
        $models = AllergyModel::where('user_id', $userId)->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByPetId(int $petId): array
    {
        $models = AllergyModel::where('pet_id', $petId)->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function findActiveByUserId(int $userId): array
    {
        $models = AllergyModel::where('user_id', $userId)
            ->active()
            ->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function findActiveByPetId(int $petId): array
    {
        $models = AllergyModel::where('pet_id', $petId)
            ->active()
            ->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function findRelevantForUser(int $userId, Scope $scope): array
    {
        $models = AllergyModel::where('user_id', $userId)
            ->active()
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]'); // Empty scopes = applicable to all
            })
            ->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function findRelevantForPet(int $petId, Scope $scope): array
    {
        $models = AllergyModel::where('pet_id', $petId)
            ->active()
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]'); // Empty scopes = applicable to all
            })
            ->get();

        return $models->map(fn (AllergyModel $model) => $this->toEntity($model))->toArray();
    }

    public function save(Allergy $allergy): void
    {
        $model = AllergyModel::updateOrCreate(
            ['id' => $allergy->id],
            [
                'tenant_id' => $allergy->tenantId,
                'user_id' => $allergy->userId,
                'pet_id' => $allergy->petId,
                'name' => $allergy->name,
                'severity' => $allergy->severity->value,
                'reaction' => $allergy->reaction,
                'scopes' => $allergy->scopes,
                'is_active' => $allergy->isActive,
            ]
        );
    }

    public function delete(int $id): void
    {
        AllergyModel::findOrFail($id)->delete();
    }

    private function toEntity(AllergyModel $model): Allergy
    {
        return new Allergy(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            petId: $model->pet_id,
            name: $model->name,
            severity: AllergySeverity::from($model->severity),
            reaction: $model->reaction,
            scopes: $model->scopes,
            isActive: $model->is_active,
        );
    }
}
