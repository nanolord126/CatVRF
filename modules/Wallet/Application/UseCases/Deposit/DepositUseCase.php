<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Deposit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Ports\FraudCheckPort;

/**
 * UseCase: Пополнить кошелёк.
 */
final class DepositUseCase
{
    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly FraudCheckPort            $fraud,
    ) {}

    public function execute(DepositCommand $cmd): void
    {
        Log::channel('audit')->info('wallet.deposit.start', [
            'correlation_id' => $cmd->correlationId,
            'user_id'        => $cmd->userId,
            'tenant_id'      => $cmd->tenantId,
            'amount'         => $cmd->amountKopeks,
        ]);

        // 1. Fraud check
        $this->fraud->check(
            userId:        $cmd->userId,
            operationType: 'wallet.deposit',
            amount:        $cmd->amountKopeks,
            context:       [
                'tenant_id'      => $cmd->tenantId,
                'correlation_id' => $cmd->correlationId,
            ],
        );

        // 2. Транзакция + lockForUpdate
        DB::transaction(function () use ($cmd): void {
            $wallet = $this->wallets->lockForUpdate($cmd->userId, $cmd->tenantId)
                ?? $this->wallets->findOrCreateByUser($cmd->userId, $cmd->tenantId);

            $wallet->deposit(
                Money::ofKopeks($cmd->amountKopeks),
                $cmd->description,
                $cmd->correlationId,
            );

            $this->wallets->save($wallet);

            // 3. Диспатч доменных событий
            foreach ($wallet->pullDomainEvents() as $event) {
                event($event);
            }
        });

        Log::channel('audit')->info('wallet.deposit.success', [
            'correlation_id' => $cmd->correlationId,
            'user_id'        => $cmd->userId,
        ]);
    }
}
