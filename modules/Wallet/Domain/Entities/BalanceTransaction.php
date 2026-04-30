<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Wallet\Domain\Enums\BalanceTransactionType;

final readonly class BalanceTransaction
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $walletId,
        public int $amount,
        public BalanceTransactionType $type,
        public int $balanceBefore,
        public int $balanceAfter,
        public int $holdBefore,
        public int $holdAfter,
        public ?string $correlationId,
        public ?string $idempotencyKey,
        public ?string $description,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
    ) {}
}
