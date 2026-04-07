<?php

declare(strict_types=1);

namespace App\Domains\Finances\Exceptions;

use RuntimeException;

/**
 * Бросается при невозможности выполнить финансовую операцию.
 *
 * Содержит context с tenantId, correlationId и причиной
 * для диагностики и audit-лога.
 *
 * CANON CatVRF 2026 — Layer Exceptions.
 */
final class FinanceOperationException extends RuntimeException
{
    public function __construct(
        private readonly string $reason,
        private readonly int    $tenantId,
        private readonly string $correlationId,
        private readonly ?int   $financeRecordId = null,
        private readonly ?float $amount = null,
    ) {
        parent::__construct(
            sprintf(
                'Finance operation failed for tenant %d: %s [correlation_id=%s]',
                $this->tenantId,
                $this->reason,
                $this->correlationId,
            ),
        );
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getFinanceRecordId(): ?int
    {
        return $this->financeRecordId;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'reason'            => $this->reason,
            'tenant_id'         => $this->tenantId,
            'finance_record_id' => $this->financeRecordId,
            'amount'            => $this->amount,
            'correlation_id'    => $this->correlationId,
        ];
    }
}
