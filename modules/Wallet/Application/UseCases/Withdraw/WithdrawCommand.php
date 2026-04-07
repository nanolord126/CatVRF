<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Withdraw;

final readonly class WithdrawCommand
{
    public function __construct(
        public int    $userId,
        public int    $tenantId,
        public int    $amountKopeks,
        public string $description,
        public string $correlationId,
    ) {}
}
