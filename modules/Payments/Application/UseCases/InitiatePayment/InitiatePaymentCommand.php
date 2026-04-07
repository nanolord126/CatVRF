<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\InitiatePayment;

/**
 * Command для инициации платежа.
 */
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public int     $tenantId,
        public int     $userId,
        public int     $amountKopeks,
        public string  $currency,
        public string  $idempotencyKey,
        public string  $correlationId,
        public string  $description,
        public string  $successUrl,
        public string  $failUrl,
        public bool    $hold,
        public bool    $recurring,
        public array   $metadata = [],
    ) {}
}
