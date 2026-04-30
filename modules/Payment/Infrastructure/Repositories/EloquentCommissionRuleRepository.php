<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Repositories\CommissionRuleRepositoryInterface;

final class EloquentCommissionRuleRepository implements CommissionRuleRepositoryInterface
{
    public function findActiveRule(?string $verticalCode, ?string $subVerticalCode, ?int $businessGroupId): ?object
    {
        // TODO: Implement commission rule model and migration
        // For now, return null - will use default from config
        return null;
    }
}
