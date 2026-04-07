<?php

declare(strict_types=1);

namespace Modules\Finances\Data;

use Spatie\LaravelData\Data;

final class WalletData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenant_id,
        public readonly ?int $business_group_id,
        public readonly int $current_balance,
        public readonly int $hold_amount,
        public readonly string $correlation_id,
    ) {
    }
}
