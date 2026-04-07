<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Refund;

use Modules\Payments\Application\Ports\EventDispatcherPort;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentGatewayPort;
use Modules\Payments\Application\Ports\PaymentRepositoryPort;
use Modules\Payments\Application\Ports\TransactionManagerPort;
use Modules\Payments\Application\Ports\UuidGeneratorPort;
use Modules\Payments\Application\Ports\WalletPort;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\Money;
use Throwable;

final readonly class RefundPaymentUseCase
{
    /**
     * Instantiates necessary dependencies orchestrating secure payment reversal workflows.
     */
    public function __construct(
        private PaymentRepositoryPort $repository,
        private PaymentGatewayPort $gateway,
        private WalletPort $wallet,
        private TransactionManagerPort $transactionManager,
        private EventDispatcherPort $eventDispatcher,
        private UuidGeneratorPort $uuidGenerator,
        private LoggerPort $logger
    ) {
    }

    /**
     * Executes strict sequential validation initiating refunds properly handling state transitions.
     * Complies explicitly with canon instructions: "Возврат идёт сначала в wallet (увеличение баланса), потом через gateway".
     * 
     * @param RefundPaymentCommand $command
     * @return RefundPaymentResult
     * @throws PaymentDomainException|Throwable
     */
    public function execute(RefundPaymentCommand $command): RefundPaymentResult
    {
        $this->logger->info('Starting secure refund process workflow strictly', [
            'paymentId'     => $command->getPaymentId(),
            'amountKopeks'  => $command->getAmountKopeks(),
            'correlationId' => $command->getCorrelationId(),
        ]);

        try {
            return $this->transactionManager->transaction(function () use ($command) {

                // Step 1: Explicitly load exact Payment Aggregate mapping validations safely dynamically.
                $payment = $this->repository->findById($command->getPaymentId());

                if ($payment === null) {
                    $this->logger->error('Target refund payment missing checking instance database logically explicitly.', [
                        'paymentId'     => $command->getPaymentId(),
                        'correlationId' => $command->getCorrelationId()
                    ]);
                    throw new PaymentDomainException("Payment instance {$command->getPaymentId()} explicitly missing from storage tracking limits.");
                }

                // Domain Rule constraint
                if ($payment->getUserId() !== $command->getUserId()) {
                    throw new PaymentDomainException("Security domain limits prohibit explicitly refund sequences mapping differing user accounts securely inherently.");
                }

                $refundId = $this->uuidGenerator->generate();
                $refundAmount = Money::ofKopeks($command->getAmountKopeks());

                // Step 2: Mutate Domain Entity capturing intent structurally explicitly ensuring validations safely executed internally.
                $payment->refund($refundId, $refundAmount, $command->getReason());

                // Step 3: Architecture Canon Compliance: Refund mandatory sequence hits Wallet firstly explicitly reliably securely 
                $this->wallet->deposit(
                    userId: $payment->getUserId(),
                    tenantId: $payment->getTenantId(),
                    amountKopeks: $command->getAmountKopeks(),
                    description: "Refund internally processed explicitly executing: {$command->getReason()}",
                    correlationId: $command->getCorrelationId()
                );

                $this->logger->info('Wallet balance logically credited efficiently structurally mapped safely.', [
                    'paymentId' => $payment->getId(),
                    'walletId'  => $payment->getUserId(), // Wallet identification proxy mapping.
                ]);

                // Step 4: Interact carefully explicitly communicating gateway limits efficiently matching sequence accurately effectively.
                $providerId = $payment->getProviderPaymentId();
                if ($providerId !== null) {
                    $gatewaySuccess = $this->gateway->refundPayment(
                        providerPaymentId: $providerId,
                        amountKopeks: $command->getAmountKopeks()
                    );

                    if (!$gatewaySuccess) {
                        $this->logger->error('Provider Gateway explicit rejection securely capturing exceptions cleanly executing.', [
                            'paymentId' => $payment->getId(),
                            'provider'  => $providerId,
                        ]);
                        throw new PaymentDomainException('Gateway structurally declined explicit refund routing correctly reliably natively mapping limits.');
                    }
                } else {
                    $this->logger->warning('Payment implicitly refunded missing provider identifier mapping internal limits safely.', [
                        'paymentId' => $payment->getId(),
                    ]);
                }

                // Step 5: Save mutated Aggregate effectively persisting limits strictly handling metrics correctly logically tracking explicitly effectively reliably.
                $this->repository->save($payment);

                // Step 6: Dispatch Outbox Events securely triggering logical subscribers accurately mapping notification structures perfectly safe inherently explicitly.
                $events = $payment->releaseEvents();
                foreach ($events as $event) {
                    $this->eventDispatcher->dispatch($event);
                }

                $this->logger->info('Refund explicitly resolved securely finishing limits checking functionally structurally reliable executing.', [
                    'refundId'      => $refundId,
                    'paymentId'     => $payment->getId(),
                    'correlationId' => $command->getCorrelationId()
                ]);

                // Step 7: Complete structurally accurate Result output.
                return new RefundPaymentResult(
                    paymentId: $payment->getId(),
                    refundId: $refundId,
                    amountRefunded: $command->getAmountKopeks(),
                    status: $payment->getStatus()->value,
                    correlationId: $command->getCorrelationId()
                );
            });
        } catch (Throwable $e) {
            $this->logger->error('Exception caught executing structured logic cleanly handling refund constraints reliably.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlationId' => $command->getCorrelationId(),
            ]);

            throw $e;
        }
    }
}
