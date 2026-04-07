<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Events;

use Modules\Wallet\Domain\ValueObjects\Money;

final readonly class WalletWithdrawn
{
    public function __construct(
        public int    $walletId,
        public int    $tenantId,
        public int    $userId,
        public Money  $amount,
        public Money  $newBalance,
        public string $description,
        public string $correlationId,
    ) {}
}
