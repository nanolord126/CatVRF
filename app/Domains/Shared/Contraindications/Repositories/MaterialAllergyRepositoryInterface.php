<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\Repositories;

use App\Domains\Shared\Contraindications\Entities\MaterialAllergy;
use App\Domains\Shared\Contraindications\ValueObjects\AllergenType;
use App\Domains\Shared\Contraindications\ValueObjects\SeverityLevel;
use Illuminate\Support\Collection;

interface MaterialAllergyRepositoryInterface
{
    public function save(MaterialAllergy $allergy): void;

    public function saveForUser(int $userId, MaterialAllergy $allergy): void;

    public function findByUuid(string $uuid): ?MaterialAllergy;

    public function findByUser(int $userId): Collection;

    public function findByUserAndMaterial(int $userId, string $material): ?MaterialAllergy;

    public function findByAllergenType(AllergenType $type): Collection;

    public function findBySeverity(SeverityLevel $severity): Collection;

    public function findActive(): Collection;

    public function delete(string $uuid): bool;

    public function deleteForUser(int $userId, string $uuid): bool;

    public function getStatistics(): array;

    public function countByAllergenType(): array;

    public function countBySeverity(): array;

    public function findCommonAllergies(int $limit = 10): Collection;
}
