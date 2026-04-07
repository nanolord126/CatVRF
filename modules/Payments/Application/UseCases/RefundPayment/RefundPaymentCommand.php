<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\RefundPayment;

final readonly class RefundPaymentCommand
{
    public function __construct(
        public string $paymentId,
        public int    $tenantId,
        public int    $amountKopeks,
        public string $reason,
        public string $correlationId,
    ) {}
}
