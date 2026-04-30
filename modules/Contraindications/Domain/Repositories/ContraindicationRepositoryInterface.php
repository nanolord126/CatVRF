<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Repositories;

use Modules\Contraindications\Domain\Entities\Contraindication;
use Modules\Contraindications\Domain\ValueObjects\Scope;

interface ContraindicationRepositoryInterface
{
    /**
     * @return array<Contraindication>
     */
    public function findByUserId(int $userId): array;

    /**
     * @return array<Contraindication>
     */
    public function findByPetId(int $petId): array;

    /**
     * @return array<Contraindication>
     */
    public function findActiveByUserId(int $userId): array;

    /**
     * @return array<Contraindication>
     */
    public function findActiveByPetId(int $petId): array;

    /**
     * @return array<Contraindication>
     */
    public function findRelevantForUser(int $userId, Scope $scope): array;

    /**
     * @return array<Contraindication>
     */
    public function findRelevantForPet(int $petId, Scope $scope): array;

    public function save(Contraindication $contraindication): void;

    public function delete(int $id): void;
}
