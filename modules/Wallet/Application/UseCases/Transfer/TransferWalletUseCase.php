<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Transfer;

use Modules\Wallet\Domain\WalletRepositoryPort;
use Modules\Wallet\Application\Ports\LoggerPort;
use Modules\Wallet\Application\Ports\EventDispatcherPort;
use Modules\Wallet\Application\Ports\TransactionManagerPort;
use Modules\Wallet\Application\Ports\FraudCheckPort;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Modules\Wallet\Domain\ValueObjects\Money;
use DateTimeImmutable;
use Throwable;

/**
 * Class TransferWalletUseCase
 *
 * Implements the cross-wallet transfer Application logic. This is responsible
 * for locating multiple aggregates (source and target wallets), coordinating
 * withdrawal and deposit side-by-side using the primary primitives, wrapping the
 * entire lifecycle securely within an isolating infrastructure database transaction.
 */
final readonly class TransferWalletUseCase
{
    /**
     * TransferWalletUseCase Constructor
     *
     * Injects primary infrastructure driven-ports avoiding rigid coupling to tools like Laravel.
     *
     * @param WalletRepositoryPort $walletRepository Loads/saves internal Wallet models safely.
     * @param FraudCheckPort $fraudCheck Validates whether internal conditions reflect high risk patterns.
     * @param TransactionManagerPort $transactionManager Coordinates isolation lock and commits boundaries.
     * @param LoggerPort $logger Implements structural audit logging.
     * @param EventDispatcherPort $eventDispatcher Fans out internal Domain changes.
     */
    public function __construct(
        private WalletRepositoryPort $walletRepository,
        private FraudCheckPort $fraudCheck,
        private TransactionManagerPort $transactionManager,
        private LoggerPort $logger,
        private EventDispatcherPort $eventDispatcher
    ) {
    }

    /**
     * Executes the secure inter-wallet transactional process.
     * Overdraft semantics prevent balances from skipping logical invariants, maintaining strict coherence.
     * 
     * @param TransferWalletCommand $command Verified command conveying validated primitive instructions.
     * @return TransferWalletResult Structured output signaling successful integration.
     *
     * @throws WalletNotFoundException Identifies missing dependencies.
     * @throws Throwable Triggers if locking constraints fail underneath the abstraction.
     */
    public function execute(TransferWalletCommand $command): TransferWalletResult
    {
        $this->logger->info('Initiating inter-wallet transfer sequence.', [
            'correlation_id' => $command->correlationId,
            'source_wallet_id' => $command->sourceWalletId,
            'target_wallet_id' => $command->targetWalletId,
            'transfer_amount' => $command->amount,
            'tenant_id' => $command->tenantId,
        ]);

        $this->fraudCheck->verifyOperation(
            $command->sourceWalletId,
            $command->amount,
            $command->tenantId,
            'transfer_out',
            $command->correlationId
        );

        return $this->transactionManager->transaction(function () use ($command) {
            // Load both models locked against parallel race conditions.
            $sourceWallet = $this->walletRepository->findByIdForUpdate($command->sourceWalletId);
            if ($sourceWallet === null) {
                $this->logger->error('Transfer aborted: Source wallet is inaccessible', [
                    'source_wallet_id' => $command->sourceWalletId,
                    'correlation_id' => $command->correlationId,
                ]);
                throw new WalletNotFoundException("Source Wallet {$command->sourceWalletId} lacking within storage.");
            }

            $targetWallet = $this->walletRepository->findByIdForUpdate($command->targetWalletId);
            if ($targetWallet === null) {
                $this->logger->error('Transfer aborted: Target wallet is inaccessible', [
                    'target_wallet_id' => $command->targetWalletId,
                    'correlation_id' => $command->correlationId,
                ]);
                throw new WalletNotFoundException("Target Wallet {$command->targetWalletId} lacking within storage.");
            }

            // Domain side effects mapping
            $transferValue = new Money($command->amount);
            
            // Execute operations - domain exceptions will inherently bubble and interrupt here
            $sourceWallet->withdraw($transferValue, "Transfer out to {$command->targetWalletId}. Reason: {$command->reason}", $command->correlationId);
            $targetWallet->deposit($transferValue, "Transfer in from {$command->sourceWalletId}. Reason: {$command->reason}", $command->correlationId);

            // Re-persist changes mapping natively into the store
            $this->walletRepository->save($sourceWallet);
            $this->walletRepository->save($targetWallet);

            // Harvest domain modifications internally attached
            $events = array_merge($sourceWallet->pullDomainEvents(), $targetWallet->pullDomainEvents());
            foreach ($events as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }

            $this->logger->info('Inter-wallet transfer process finalized gracefully.', [
                'correlation_id' => $command->correlationId,
                'source_wallet_id' => $command->sourceWalletId,
                'target_wallet_id' => $command->targetWalletId,
                'source_new_balance' => $sourceWallet->getBalance()->getAmount(),
                'target_new_balance' => $targetWallet->getBalance()->getAmount(),
            ]);

            return new TransferWalletResult(
                transactionId: uniqid('txn_t_', true),
                sourceNewBalance: $sourceWallet->getBalance()->getAmount(),
                targetNewBalance: $targetWallet->getBalance()->getAmount(),
                correlationId: $command->correlationId,
                transferredAt: new DateTimeImmutable()
            );
        });
    }
}
