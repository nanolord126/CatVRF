<?php

declare(strict_types=1);

namespace Modules\Finances\Data;

use Modules\Finances\Enums\PaymentStatus;
use Spatie\LaravelData\Data;

final class PaymentTransactionData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenant_id,
        public readonly string $idempotency_key,
        public readonly string $provider_code,
        public readonly PaymentStatus $status,
        public readonly int $amount,
        public readonly bool $hold,
        public readonly string $correlation_id,
        public readonly ?string $provider_payment_id,
        public readonly ?array $meta,
    ) {
    }
}
