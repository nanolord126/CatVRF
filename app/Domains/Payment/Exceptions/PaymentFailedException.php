<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use RuntimeException;

/**
 * Бросается при неудачном проведении платежа.
 *
 * Содержит paymentRecordId, провайдер, статус, correlationId
 * для диагностики и audit-лога.
 *
 * CANON CatVRF 2026 — Layer Exceptions.
 */
final class PaymentFailedException extends RuntimeException
{
    public function __construct(
        private readonly ?int   $paymentRecordId,
        private readonly string $providerCode,
        private readonly string $reason,
        private readonly string $correlationId,
        private readonly ?float $amount = null,
    ) {
        parent::__construct(
            sprintf(
                'Payment failed via %s (record=%s): %s [%s]',
                $this->providerCode,
                $this->paymentRecordId !== null ? (string) $this->paymentRecordId : 'new',
                $this->reason,
                $this->correlationId,
            ),
        );
    }

    public function getPaymentRecordId(): ?int
    {
        return $this->paymentRecordId;
    }

    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'payment_record_id' => $this->paymentRecordId,
            'provider_code'     => $this->providerCode,
            'reason'            => $this->reason,
            'amount'            => $this->amount,
            'correlation_id'    => $this->correlationId,
        ];
    }
}
