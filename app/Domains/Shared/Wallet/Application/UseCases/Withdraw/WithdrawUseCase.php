<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Withdraw;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Ports\FraudCheckPort;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * UseCase: Снять с кошелька.
 */
final readonly class WithdrawUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly FraudCheckPort $fraud,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    public function execute(WithdrawCommand $cmd): void
    {
        $this->log->channel('audit')->info('wallet.withdraw.start', [
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

        $this->db->transaction(function () use ($cmd): void {
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

        $this->log->channel('audit')->info('wallet.withdraw.success', [
            'correlation_id' => $cmd->correlationId,
            'user_id'        => $cmd->userId,
        ]);
    }
}
