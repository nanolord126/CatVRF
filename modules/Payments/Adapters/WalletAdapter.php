<?php

declare(strict_types=1);

namespace Modules\Payments\Adapters;

use Modules\Payments\Ports\WalletPort;
use Modules\Wallet\Application\UseCases\Deposit\DepositCommand;
use Modules\Wallet\Application\UseCases\Deposit\DepositUseCase;
use Modules\Wallet\Application\UseCases\Withdraw\WithdrawCommand;
use Modules\Wallet\Application\UseCases\Withdraw\WithdrawUseCase;

/**
 * Adapter: WalletPort → Wallet UseCases.
 * Соединяет модуль Payments с модулем Wallet через порт.
 */
final readonly class WalletAdapter implements WalletPort
{
    public function __construct(
        private DepositUseCase  $deposit,
        private WithdrawUseCase $withdraw,
    ) {}

    public function deposit(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void {
        $this->deposit->execute(new DepositCommand(
            userId:        $userId,
            tenantId:      $tenantId,
            amountKopeks:  $amountKopeks,
            description:   $description,
            correlationId: $correlationId,
        ));
    }

    public function withdraw(
        int    $userId,
        int    $tenantId,
        int    $amountKopeks,
        string $description,
        string $correlationId,
    ): void {
        $this->withdraw->execute(new WithdrawCommand(
            userId:        $userId,
            tenantId:      $tenantId,
            amountKopeks:  $amountKopeks,
            description:   $description,
            correlationId: $correlationId,
        ));
    }

    public function getBalance(int $userId, int $tenantId): int
    {
        // Делегировать Wallet модулю через фасад или direct DI
        return app(\Modules\Wallet\Application\Queries\GetWalletBalanceQuery::class)
            ->execute($userId, $tenantId);
    }
}
