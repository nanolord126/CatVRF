<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Withdraw;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Ports\FraudCheckPort;

/**
 * UseCase: Снять с кошелька.
 */
final class WithdrawUseCase
{
    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly FraudCheckPort            $fraud,
    ) {}

    public function execute(WithdrawCommand $cmd): void
    {
        Log::channel('audit')->info('wallet.withdraw.start', [
            'correlation_id' => $cmd->correlationId,
            'user_id'        => $cmd->userId,
            'amount'         => $cmd->amountKopeks,
        ]);

        $this->fraud->check(
            userId:        $cmd->userId,
            operationType: 'wallet.withdraw',
            amount:        $cmd->amountKopeks,
            context:       [
                'tenant_id'      => $cmd->tenantId,
                'correlation_id' => $cmd->correlationId,
            ],
        );

        DB::transaction(function () use ($cmd): void {
            $wallet = $this->wallets->lockForUpdate($cmd->userId, $cmd->tenantId);

            if ($wallet === null) {
                throw WalletNotFoundException::forUser($cmd->userId, $cmd->tenantId);
            }

            $wallet->withdraw(
                Money::ofKopeks($cmd->amountKopeks),
                $cmd->description,
                $cmd->correlationId,
            );

            $this->wallets->save($wallet);

            foreach ($wallet->pullDomainEvents() as $event) {
                event($event);
            }
        });

        Log::channel('audit')->info('wallet.withdraw.success', [
            'correlation_id' => $cmd->correlationId,
            'user_id'        => $cmd->userId,
        ]);
    }
}
