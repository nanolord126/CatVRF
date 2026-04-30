<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Repositories;

use Modules\Contraindications\Domain\Entities\Allergy;
use Modules\Contraindications\Domain\ValueObjects\Scope;

interface AllergyRepositoryInterface
{
    /**
     * @return array<Allergy>
     */
    public function findByUserId(int $userId): array;

    /**
     * @return array<Allergy>
     */
    public function findByPetId(int $petId): array;

    /**
     * @return array<Allergy>
     */
    public function findActiveByUserId(int $userId): array;

    /**
     * @return array<Allergy>
     */
    public function findActiveByPetId(int $petId): array;

    /**
     * @return array<Allergy>
     */
    public function findRelevantForUser(int $userId, Scope $scope): array;

    /**
     * @return array<Allergy>
     */
    public function findRelevantForPet(int $petId, Scope $scope): array;

    public function save(Allergy $allergy): void;

    public function delete(int $id): void;
}
