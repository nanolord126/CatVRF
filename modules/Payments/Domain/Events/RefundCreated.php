<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Events;

use Modules\Payments\Domain\ValueObjects\Money;

/** Событие: возврат создан */
final readonly class RefundCreated
{
    public function __construct(
        public string $refundId,
        public string $paymentId,
        public int    $tenantId,
        public int    $userId,
        public Money  $amount,
        public string $correlationId,
    ) {}
}
