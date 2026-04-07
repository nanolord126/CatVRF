<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Exceptions;

use RuntimeException;

/**
 * Бросается при недостаточном балансе на кошельке.
 *
 * Содержит walletId, запрошенную и текущую сумму, correlationId
 * для диагностики.
 *
 * CANON CatVRF 2026 — Layer Exceptions.
 */
final class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        private readonly int    $walletId,
        private readonly float  $requested,
        private readonly float  $currentBalance,
        private readonly string $correlationId,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient balance on wallet %d: requested %.2f, available %.2f [%s]',
                $this->walletId,
                $this->requested,
                $this->currentBalance,
                $this->correlationId,
            ),
        );
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getRequested(): float
    {
        return $this->requested;
    }

    public function getCurrentBalance(): float
    {
        return $this->currentBalance;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'wallet_id'       => $this->walletId,
            'requested'       => $this->requested,
            'current_balance' => $this->currentBalance,
            'correlation_id'  => $this->correlationId,
        ];
    }
}
