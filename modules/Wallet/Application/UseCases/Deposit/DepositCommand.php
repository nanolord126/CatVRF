<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Deposit;

final readonly class DepositCommand
{
    public function __construct(
        public int    $userId,
        public int    $tenantId,
        public int    $amountKopeks,
        public string $description,
        public string $correlationId,
    ) {}
}
