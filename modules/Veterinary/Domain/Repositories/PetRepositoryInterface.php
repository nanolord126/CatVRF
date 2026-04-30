<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\Repositories;

use Modules\Veterinary\Domain\Entities\Pet;

interface PetRepositoryInterface
{
    public function create(array $data): Pet;

    public function update(int $id, array $data): Pet;

    public function findById(int $id): ?Pet;

    public function findByUuid(string $uuid): ?Pet;

    public function findByOwnerId(int $ownerId): array;

    public function findByChipNumber(string $chipNumber): ?Pet;
}
