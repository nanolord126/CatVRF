<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Events;

final readonly class PaymentInitiated
{
    public function __construct(
        public string $paymentId,
        public int $tenantId,
        public int $userId,
        public int $amountKopeks,
        public string $correlationId,
    ) {}
}
