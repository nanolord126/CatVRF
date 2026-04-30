<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\LoyaltyRule;

interface LoyaltyRuleRepositoryInterface
{
    public function findById(string $id): ?LoyaltyRule;

    public function findByUuid(string $uuid): ?LoyaltyRule;

    public function findByProgramId(string $programId): array;

    public function findActiveByProgramId(string $programId): array;

    public function findByProgramIdSorted(string $programId): array;

    public function save(LoyaltyRule $rule): LoyaltyRule;

    public function delete(string $id): void;
}
