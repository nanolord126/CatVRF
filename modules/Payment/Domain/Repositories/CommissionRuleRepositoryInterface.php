<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

interface CommissionRuleRepositoryInterface
{
    public function findActiveRule(?string $verticalCode, ?string $subVerticalCode, ?int $businessGroupId): ?object;
}
