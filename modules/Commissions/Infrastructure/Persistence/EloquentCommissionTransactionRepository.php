<?php

declare(strict_types=1);

namespace Modules\Commissions\Infrastructure\Persistence;

use Modules\Commissions\Domain\Entities\CommissionTransaction;
use Modules\Commissions\Domain\Repositories\CommissionTransactionRepositoryInterface;

final class EloquentCommissionTransactionRepository implements CommissionTransactionRepositoryInterface
{
    public function create(array $data): CommissionTransaction
    {
        return CommissionTransaction::create($data);
    }

    public function findById(int $id): ?CommissionTransaction
    {
        return CommissionTransaction::find($id);
    }
}
