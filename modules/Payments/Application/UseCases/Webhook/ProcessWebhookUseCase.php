<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Webhook;

use Modules\Payments\Application\Ports\EventDispatcherPort;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentGatewayPort;
use Modules\Payments\Application\Ports\PaymentRepositoryPort;
use Modules\Payments\Application\Ports\TransactionManagerPort;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Throwable;

final readonly class ProcessWebhookUseCase
{
    /**
     * Constructs the orchestration component necessary for interpreting provider webhooks securely.
     */
    public function __construct(
        private PaymentGatewayPort $gateway,
        private PaymentRepositoryPort $repository,
        private TransactionManagerPort $transactionManager,
        private EventDispatcherPort $eventDispatcher,
        private LoggerPort $logger,
    ) {
    }

    /**
     * Engages the complete business pipeline for accepting provider webhooks,
     * including cryptographic validation and idempotent execution locking.
     * 
     * @param ProcessWebhookCommand $command
     * @return ProcessWebhookResult
     * @throws PaymentDomainException|Throwable
     */
    public function execute(ProcessWebhookCommand $command): ProcessWebhookResult
    {
        $this->logger->info('Processing provider incoming webhook asynchronously', [
            'provider'      => $command->getProviderCode(),
            'correlationId' => $command->getCorrelationId()
        ]);

        try {
            // Step 1: Gateway specifically verifies the signature constraints
            $isValid = $this->gateway->validateWebhook(
                payload: $command->getPayload(),
                signature: $command->getSignature()
            );

            if (!$isValid) {
                $this->logger->error('Invalid webhook signature blocked actively', [
                    'correlationId' => $command->getCorrelationId(),
                ]);
                throw new PaymentDomainException('Webhook possesses an invalid signature or checksum structure.');
            }

            // Step 2: Gateway converts arbitrary JSON dictionary to predictable object properties
            $parsedData = $this->gateway->parseWebhook($command->getPayload());
            
            return $this->transactionManager->transaction(function () use ($parsedData, $command) {
                
                // Step 3: Extract entity dynamically locking the row specifically if implementing Pessimistic Locks
                $payment = $this->repository->findById($parsedData->internalPaymentId);

                if ($payment === null) {
                    $this->logger->error('Webhook targets a non-existing internal payment record.', [
                        'paymentId'     => $parsedData->internalPaymentId,
                        'correlationId' => $command->getCorrelationId(),
                    ]);
                    throw new PaymentDomainException("Payment internal instance {$parsedData->internalPaymentId} missing from strict boundaries.");
                }

                $domainStatus = $payment->getStatus();

                // Step 4: Guard against repetitive or outdated status adjustments avoiding race conditions
                if ($domainStatus->value === $parsedData->newStatus) {
                    $this->logger->info('Payment webhook status identical, silently discarding processing', [
                        'paymentId' => $payment->getId(),
                        'correlationId' => $command->getCorrelationId(),
                    ]);

                    return new ProcessWebhookResult(
                        paymentId: $payment->getId(),
                        finalStatus: $domainStatus->value,
                        processedSilently: true,
                        correlationId: $command->getCorrelationId(),
                    );
                }

                // Step 5: Process targeted status transitions (e.g. captured, failed, refunded) map logically
                if ($parsedData->newStatus === PaymentStatus::CAPTURED->value) {
                    $payment->capture(
                        providerPaymentId: $parsedData->providerPaymentId,
                        paymentUrl: $payment->getPaymentUrl() ?? ''
                    );
                } elseif ($parsedData->newStatus === PaymentStatus::FAILED->value) {
                    $reason = $command->getPayload()['Error'] ?? 'Unknown Gateway Failure';
                    $payment->markAsFailed($reason);
                } else {
                    $this->logger->warning('Webhook contains unhandled or abstract transition mapping state fallback', [
                        'newStatus'     => $parsedData->newStatus,
                        'correlationId' => $command->getCorrelationId(),
                    ]);
                }

                // Step 6: Flush mutated Aggregate Root changes explicitly
                $this->repository->save($payment);

                // Step 7: Push buffered outbox events to queues handling Wallet balance and Notifications externally
                $events = $payment->releaseEvents();
                foreach ($events as $event) {
                    $this->eventDispatcher->dispatch($event);
                }

                $this->logger->info('Payment webhook strictly synchronized states successfully', [
                    'paymentId'   => $payment->getId(),
                    'finalStatus' => $payment->getStatus()->value,
                    'correlationId'=> $command->getCorrelationId(),
                ]);

                // Step 8: Complete standard application result loop structurally
                return new ProcessWebhookResult(
                    paymentId: $payment->getId(),
                    finalStatus: $payment->getStatus()->value,
                    processedSilently: false,
                    correlationId: $command->getCorrelationId(),
                );
            });

        } catch (Throwable $e) {
            $this->logger->error('Fundamental failure interrupting the processing logic pipeline asynchronously.', [
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
                'correlationId' => $command->getCorrelationId(),
            ]);

            throw $e;
        }
    }
}
