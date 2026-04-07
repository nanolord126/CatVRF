<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Exceptions;

use RuntimeException;

/**
 * Бросается при неудачной операции с кошельком.
 *
 * Содержит walletId, тип операции, correlationId
 * для диагностики и audit-лога.
 *
 * CANON CatVRF 2026 — Layer Exceptions.
 */
final class WalletOperationException extends RuntimeException
{
    public function __construct(
        private readonly int    $walletId,
        private readonly string $operationType,
        private readonly string $reason,
        private readonly string $correlationId,
    ) {
        parent::__construct(
            sprintf(
                'Wallet operation "%s" failed for wallet %d: %s [%s]',
                $this->operationType,
                $this->walletId,
                $this->reason,
                $this->correlationId,
            ),
        );
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'wallet_id'      => $this->walletId,
            'operation_type' => $this->operationType,
            'reason'         => $this->reason,
            'correlation_id' => $this->correlationId,
        ];
    }
}
