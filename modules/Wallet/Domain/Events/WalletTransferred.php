<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Events;

use Modules\Wallet\Domain\ValueObjects\Money;

final readonly class WalletTransferred
{
    public function __construct(
        public int    $fromWalletId,
        public int    $toWalletId,
        public int    $tenantId,
        public Money  $amount,
        public string $correlationId,
    ) {}
}
