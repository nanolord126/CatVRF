<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Debit;

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
 * Class DebitWalletUseCase
 *
 * Implements the Application logic for debiting funds from a Wallet.
 * This UseCase orchestrates the Domain Aggregate, Port repositories, logging,
 * and event dispatching. It rigorously ensures that the database transaction
 * is wrapped securely and domain rules are preserved without leaking domain
 * concepts beyond this Application boundary.
 */
final readonly class DebitWalletUseCase
{
    /**
     * DebitWalletUseCase constructor.
     * Injection of driven ports to interact with infrastructure independently.
     *
     * @param WalletRepositoryPort $walletRepository Handles the persistence of Wallet aggregates.
     * @param FraudCheckPort $fraudCheck Validates if the operation is flagged for fraud.
     * @param TransactionManagerPort $transactionManager Wraps state mutations in atomic DB transactions.
     * @param LoggerPort $logger Records audit and debug trail of the use case execution.
     * @param EventDispatcherPort $eventDispatcher Streams generated domain events to external channels.
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
     * Executes the debit operation adhering strictly to Domain logic and boundary isolation.
     *
     * @param DebitWalletCommand $command the strongly typed incoming request command.
     * @return DebitWalletResult the structured outbound result DTO.
     *
     * @throws WalletNotFoundException if the wallet identity doesn't resolve to an aggregate.
     * @throws Throwable any unexpected infrastructure or domain constraint violation.
     */
    public function execute(DebitWalletCommand $command): DebitWalletResult
    {
        $this->logger->info('Initiating wallet debit process', [
            'correlation_id' => $command->correlationId,
            'wallet_id' => $command->walletId,
            'debit_amount' => $command->amount,
            'tenant_id' => $command->tenantId,
        ]);

        // Pre-transaction anti-fraud check protecting the perimeter.
        $this->fraudCheck->verifyOperation(
            $command->walletId,
            $command->amount,
            $command->tenantId,
            'debit',
            $command->correlationId
        );

        return $this->transactionManager->transaction(function () use ($command) {
            $wallet = $this->walletRepository->findByIdForUpdate($command->walletId);

            if ($wallet === null) {
                $this->logger->error('Debit failed: Wallet not found', [
                    'correlation_id' => $command->correlationId,
                    'wallet_id' => $command->walletId,
                    'tenant_id' => $command->tenantId,
                ]);
                throw new WalletNotFoundException("Wallet with ID {$command->walletId} was not found.");
            }

            // Domain state mutation enforcing invariants
            $withdrawalAmount = new Money($command->amount);
            $wallet->withdraw($withdrawalAmount, $command->reason, $command->correlationId);

            $this->walletRepository->save($wallet);

            $events = $wallet->pullDomainEvents();
            foreach ($events as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }

            $this->logger->info('Wallet debit completed successfully', [
                'correlation_id' => $command->correlationId,
                'wallet_id' => $command->walletId,
                'new_balance' => $wallet->getBalance()->getAmount(),
                'tenant_id' => $command->tenantId,
            ]);

            return new DebitWalletResult(
                transactionId: uniqid('txn_d_', true), // Identifier for internal usage
                newBalance: $wallet->getBalance()->getAmount(),
                correlationId: $command->correlationId,
                walletId: $command->walletId,
                debitedAt: new DateTimeImmutable()
            );
        });
    }
}
