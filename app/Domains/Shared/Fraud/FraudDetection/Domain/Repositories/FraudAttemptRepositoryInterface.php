<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Domain\Repositories;

use Modules\FraudDetection\Domain\Entities\FraudAttempt;

interface FraudAttemptRepositoryInterface
{
    public function create(array $data): FraudAttempt;

    public function findByTransactionId(string $transactionId): ?FraudAttempt;
}
