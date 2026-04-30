<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Transfer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Ports\FraudCheckPort;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * UseCase: Перевод между кошельками (оба lockForUpdate, одна транзакция).
 */
final readonly class TransferUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly FraudCheckPort $fraud,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly AuditService $audit,
    ) {}

    public function execute(TransferCommand $cmd): void
    {
        $this->log->channel('audit')->info('wallet.transfer.start', [
            'correlation_id' => $cmd->correlationId,
            'from_user'      => $cmd->fromUserId,
            'to_user'        => $cmd->toUserId,
            'amount'         => $cmd->amountKopeks,
        ]);

        $this->fraud->check(
            userId:        $cmd->fromUserId,
            operationType: 'wallet.transfer',
            amount:        $cmd->amountKopeks,
            context:       [
                'tenant_id'      => $cmd->tenantId,
                'correlation_id' => $cmd->correlationId,
            ],
        );

        $this->db->transaction(function () use ($cmd): void {
            // Всегда блокируем по возрастанию userId, чтобы избежать deadlock
            [$lower, $higher] = $cmd->fromUserId < $cmd->toUserId
                ? [$cmd->fromUserId, $cmd->toUserId]
                : [$cmd->toUserId, $cmd->fromUserId];

            $lowerWallet  = $this->wallets->lockForUpdate($lower, $cmd->tenantId)
                ?? throw WalletNotFoundException::forUser($lower, $cmd->tenantId);
            $higherWallet = $this->wallets->lockForUpdate($higher, $cmd->tenantId)
                ?? throw WalletNotFoundException::forUser($higher, $cmd->tenantId);

            [$fromWallet, $toWallet] = $cmd->fromUserId === $lower
                ? [$lowerWallet, $higherWallet]
                : [$higherWallet, $lowerWallet];

            $fromWallet->transferTo(
                $toWallet,
                Money::ofKopeks($cmd->amountKopeks),
                $cmd->description,
                $cmd->correlationId,
            );

            $this->wallets->save($fromWallet);
            $this->wallets->save($toWallet);

            foreach ($fromWallet->pullDomainEvents() as $event) {
                event($event);
            }
            foreach ($toWallet->pullDomainEvents() as $event) {
                event($event);
            }
        });

        $this->log->channel('audit')->info('wallet.transfer.success', [
            'correlation_id' => $cmd->correlationId,
        ]);
    }
}
