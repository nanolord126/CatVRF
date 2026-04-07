<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Взаимодействие с модулем Wallet (outgoing).
 * Реализуется WalletAdapter → Wallet UseCases.
 */
interface WalletPort
{
    public function deposit(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void;

    public function withdraw(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void;

    public function getBalance(int $userId, int $tenantId): int;
}
