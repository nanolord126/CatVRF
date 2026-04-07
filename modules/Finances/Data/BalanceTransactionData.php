<?php

declare(strict_types=1);

namespace Modules\Finances\Data;

use Modules\Finances\Enums\BalanceTransactionStatus;
use Modules\Finances\Enums\BalanceTransactionType;
use Spatie\LaravelData\Data;

final class BalanceTransactionData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $wallet_id,
        public readonly BalanceTransactionType $type,
        public readonly BalanceTransactionStatus $status,
        public readonly int $amount,
        public readonly string $correlation_id,
        public readonly ?array $meta,
    ) {
    }
}
