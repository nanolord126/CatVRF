<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\LoyaltyReward;

interface LoyaltyRewardRepositoryInterface
{
    public function findById(string $id): ?LoyaltyReward;

    public function findByUuid(string $uuid): ?LoyaltyReward;

    public function findByProgramId(string $programId): array;

    public function findAvailableByProgramId(string $programId): array;

    public function findByProgramIdSorted(string $programId): array;

    public function save(LoyaltyReward $reward): LoyaltyReward;

    public function delete(string $id): void;
}
