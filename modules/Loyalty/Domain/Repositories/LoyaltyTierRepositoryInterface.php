<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\LoyaltyTier;

interface LoyaltyTierRepositoryInterface
{
    public function findById(string $id): ?LoyaltyTier;

    public function findByUuid(string $uuid): ?LoyaltyTier;

    public function findByProgramId(string $programId): array;

    public function findBySlug(string $slug): ?LoyaltyTier;

    public function findByProgramIdSorted(string $programId): array;

    public function save(LoyaltyTier $tier): LoyaltyTier;

    public function delete(string $id): void;
}
