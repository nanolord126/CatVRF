<?php

declare(strict_types=1);

namespace Modules\Commissions\Domain\Repositories;

use Modules\Commissions\Domain\Entities\CommissionTransaction;

interface CommissionTransactionRepositoryInterface
{
    public function create(array $data): CommissionTransaction;

    public function findById(int $id): ?CommissionTransaction;
}
