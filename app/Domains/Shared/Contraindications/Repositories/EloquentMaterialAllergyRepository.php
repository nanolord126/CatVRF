<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\Repositories;

use App\Domains\Shared\Contraindications\Entities\MaterialAllergy;
use App\Domains\Shared\Contraindications\ValueObjects\AllergenType;
use App\Domains\Shared\Contraindications\ValueObjects\SeverityLevel;
use App\Models\UserMaterialAllergy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentMaterialAllergyRepository implements MaterialAllergyRepositoryInterface
{
    public function save(MaterialAllergy $allergy): void
    {
        $model = UserMaterialAllergy::where('uuid', $allergy->getUuid())->first();

        if ($model) {
            $model->update([
                'material_name' => $allergy->getMaterialName(),
                'allergen_type' => $allergy->getAllergenType()->value,
                'severity' => $allergy->getSeverity()->value,
                'triggering_materials' => $allergy->getTriggeringMaterials(),
                'description' => $allergy->getDescription(),
                'medical_reference' => $allergy->getMedicalReference(),
                'is_active' => $allergy->isActive(),
            ]);
        } else {
            UserMaterialAllergy::create([
                'uuid' => $allergy->getUuid(),
                'material_name' => $allergy->getMaterialName(),
                'allergen_type' => $allergy->getAllergenType()->value,
                'severity' => $allergy->getSeverity()->value,
                'triggering_materials' => $allergy->getTriggeringMaterials(),
                'description' => $allergy->getDescription(),
                'medical_reference' => $allergy->getMedicalReference(),
                'is_active' => $allergy->isActive(),
            ]);
        }
    }

    public function saveForUser(int $userId, MaterialAllergy $allergy): void
    {
        $this->save($allergy);

        $model = UserMaterialAllergy::where('uuid', $allergy->getUuid())->first();
        if ($model && !$model->users()->where('user_id', $userId)->exists()) {
            $model->users()->attach($userId);
        }
    }

    public function findByUuid(string $uuid): ?MaterialAllergy
    {
        $model = UserMaterialAllergy::where('uuid', $uuid)->first();

        return $model?->toDomain();
    }

    public function findByUser(int $userId): Collection
    {
        return UserMaterialAllergy::whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByUserAndMaterial(int $userId, string $material): ?MaterialAllergy
    {
        $model = UserMaterialAllergy::whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->where('is_active', true)
            ->where('material_name', 'like', "%{$material}%")
            ->first();

        return $model?->toDomain();
    }

    public function findByAllergenType(AllergenType $type): Collection
    {
        return UserMaterialAllergy::where('allergen_type', $type->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findBySeverity(SeverityLevel $severity): Collection
    {
        return UserMaterialAllergy::where('severity', $severity->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findActive(): Collection
    {
        return UserMaterialAllergy::where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function delete(string $uuid): bool
    {
        return UserMaterialAllergy::where('uuid', $uuid)->delete() > 0;
    }

    public function deleteForUser(int $userId, string $uuid): bool
    {
        $model = UserMaterialAllergy::where('uuid', $uuid)->first();
        if (!$model) {
            return false;
        }

        return $model->users()->detach($userId) > 0;
    }

    public function getStatistics(): array
    {
        return [
            'total_allergies' => UserMaterialAllergy::count(),
            'active_allergies' => UserMaterialAllergy::where('is_active', true)->count(),
            'users_with_allergies' => DB::table('user_material_allergy')->distinct('user_id')->count(),
            'by_type' => $this->countByAllergenType(),
            'by_severity' => $this->countBySeverity(),
        ];
    }

    public function countByAllergenType(): array
    {
        return UserMaterialAllergy::selectRaw('allergen_type, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('allergen_type')
            ->pluck('count', 'allergen_type')
            ->toArray();
    }

    public function countBySeverity(): array
    {
        return UserMaterialAllergy::selectRaw('severity, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    public function findCommonAllergies(int $limit = 10): Collection
    {
        return UserMaterialAllergy::withCount('users')
            ->where('is_active', true)
            ->orderBy('users_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }
}
