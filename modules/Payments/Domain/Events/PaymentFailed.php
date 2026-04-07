<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Events;

use Modules\Payments\Domain\ValueObjects\Money;

/** Событие: платёж провалился */
final readonly class PaymentFailed
{
    public function __construct(
        public string $paymentId,
        public int    $tenantId,
        public int    $userId,
        public Money  $amount,
        public string $reason,
        public string $correlationId,
    ) {}
}
