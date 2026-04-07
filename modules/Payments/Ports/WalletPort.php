<?php

declare(strict_types=1);

namespace Modules\Payments\Ports;

/**
 * Outgoing Port: Взаимодействие с модулем Wallet.
 */
interface WalletPort
{
    /** Пополнить кошелёк пользователя */
    public function deposit(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void;

    /** Снять с кошелька */
    public function withdraw(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void;

    /** Получить баланс */
    public function getBalance(int $userId, int $tenantId): int;
}
