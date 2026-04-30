<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\LoyaltyTransaction;

interface LoyaltyTransactionRepositoryInterface
{
    public function findById(string $id): ?LoyaltyTransaction;

    public function findByUuid(string $uuid): ?LoyaltyTransaction;

    public function findByProfileId(string $profileId, int $limit = 50): array;

    public function findBySource(string $sourceType, int $sourceId): ?LoyaltyTransaction;

    public function save(LoyaltyTransaction $transaction): LoyaltyTransaction;

    public function delete(string $id): void;
}
