<?php

declare(strict_types=1);

namespace Modules\Commissions\Domain\Repositories;

use Modules\Commissions\Domain\Entities\CommissionRule;

interface CommissionRuleRepositoryInterface
{
    public function findByVerticalAndTenant(string $vertical, int $tenantId): ?CommissionRule;

    public function save(CommissionRule $rule): CommissionRule;
}
