<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\InitiatePayment;

/**
 * Результат инициации платежа.
 */
final readonly class InitiatePaymentResult
{
    public function __construct(
        public string  $paymentId,
        public string  $paymentUrl,
        public string  $status,
        public bool    $isDuplicate,
        public string  $correlationId,
    ) {}
}
