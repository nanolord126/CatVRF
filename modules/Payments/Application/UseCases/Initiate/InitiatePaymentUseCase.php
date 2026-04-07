<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Initiate;

use Modules\Payments\Application\Ports\EventDispatcherPort;
use Modules\Payments\Application\Ports\FraudCheckPort;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentGatewayPort;
use Modules\Payments\Application\Ports\PaymentRepositoryPort;
use Modules\Payments\Application\Ports\TransactionManagerPort;
use Modules\Payments\Application\Ports\UuidGeneratorPort;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Domain\ValueObjects\Money;
use Throwable;

final readonly class InitiatePaymentUseCase
{
    public function __construct(
        private PaymentRepositoryPort $paymentRepository,
        private PaymentGatewayPort $paymentGateway,
        private TransactionManagerPort $transactionManager,
        private LoggerPort $logger,
        private EventDispatcherPort $eventDispatcher,
        private FraudCheckPort $fraudCheck,
        private UuidGeneratorPort $uuidGenerator,
    ) {
    }

    /**
     * Executes the payment initiation process according to clean architecture principles.
     * Includes fraud checks, idempotency validation, transaction management, and event dispatching.
     *
     * @param InitiatePaymentCommand $command
     * @return InitiatePaymentResult
     * @throws PaymentDomainException|Throwable
     */
    public function execute(InitiatePaymentCommand $command): InitiatePaymentResult
    {
        $this->logger->info('Initiating payment process', [
            'tenantId' => $command->tenantId,
            'userId' => $command->userId,
            'amountKopeks' => $command->amountKopeks,
            'idempotencyKey' => $command->idempotencyKey,
            'correlationId' => $command->correlationId,
        ]);

        try {
            $idempotencyKey = new IdempotencyKey($command->idempotencyKey);
            $amount = new Money($command->amountKopeks);

            // Step 1: Perform Fraud Check before any writes or external calls
            $isFraud = $this->fraudCheck->isFraudulent(
                tenantId: $command->tenantId,
                userId: $command->userId,
                amountKopeks: $command->amountKopeks,
                metadata: $command->metadata,
            );

            if ($isFraud) {
                $this->logger->warning('Payment blocked by fraud check ML scoring', [
                    'userId' => $command->userId,
                    'tenantId' => $command->tenantId,
                    'amount' => $command->amountKopeks,
                    'correlationId' => $command->correlationId,
                ]);
                
                throw new PaymentDomainException('Payment blocked by security and fraud policy.');
            }

            // Step 2: Use Transaction Manager for atomic database operations
            return $this->transactionManager->transaction(function () use ($command, $idempotencyKey, $amount) {
                
                // Step 3: Check Idempotency inside the transaction specifically utilizing row locks if needed
                $existingPayment = $this->paymentRepository->findByIdempotencyKey($idempotencyKey);

                if ($existingPayment !== null) {
                    $this->logger->info('Duplicate payment request detected, returning existing payment to ensure idempotency', [
                        'paymentId' => $existingPayment->getId(),
                        'correlationId' => $command->correlationId,
                    ]);

                    return new InitiatePaymentResult(
                        paymentId: $existingPayment->getId(),
                        paymentUrl: $existingPayment->getPaymentUrl() ?? '',
                        status: $existingPayment->getStatus()->value,
                        isDuplicate: true,
                        correlationId: $command->correlationId,
                    );
                }

                // Step 4: Create internal Payment Entity
                $paymentId = $this->uuidGenerator->generate();
                $payment = Payment::initiate(
                    id: $paymentId,
                    tenantId: $command->tenantId,
                    userId: $command->userId,
                    amount: $amount,
                    idempotencyKey: $idempotencyKey,
                    correlationId: $command->correlationId,
                    metadata: $command->metadata,
                    recurrent: $command->recurrent,
                );

                // Step 5: Interact with external Payment Gateway (Tinkoff / Sber / Tochka)
                $gatewayResponse = $this->paymentGateway->initiatePayment(
                    paymentId: $payment->getId(),
                    amountKopeks: $command->amountKopeks,
                    description: $command->description,
                    recurrent: $command->recurrent,
                    metadata: $command->metadata,
                );

                // Update entity domain logic based on external gateway response. 
                // For simplified flow we consider initiation immediately sets up provider link.
                $payment->capture(
                    providerPaymentId: $gatewayResponse->providerPaymentId,
                    paymentUrl: $gatewayResponse->paymentUrl
                );

                // Step 6: Persist the modified entity to infrastructure via Repository
                $this->paymentRepository->save($payment);

                // Step 7: Dispatch Domain Events for any side-effects (Wallet hooks, notifications)
                $events = $payment->releaseEvents();
                foreach ($events as $event) {
                    $this->eventDispatcher->dispatch($event);
                }

                $this->logger->info('Payment successfully initiated and persisted', [
                    'paymentId' => $paymentId,
                    'providerPaymentId' => $gatewayResponse->providerPaymentId,
                    'domainEventsDispatched' => count($events),
                    'correlationId' => $command->correlationId,
                ]);

                // Step 8: Return concrete Result DTO
                return new InitiatePaymentResult(
                    paymentId: $paymentId,
                    paymentUrl: $gatewayResponse->paymentUrl,
                    status: $payment->getStatus()->value,
                    isDuplicate: false,
                    correlationId: $command->correlationId,
                );
            });
        } catch (Throwable $exception) {
            $this->logger->error('Strict failure during payment initiation', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'correlationId' => $command->correlationId ?? 'unknown',
            ]);

            throw $exception;
        }
    }
}
