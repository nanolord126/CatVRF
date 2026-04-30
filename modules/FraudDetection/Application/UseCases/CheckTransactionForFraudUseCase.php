<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Application\UseCases;

use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\FraudDetection\Application\DTOs\FraudCheckData;
use Modules\FraudDetection\Domain\Repositories\FraudAttemptRepositoryInterface;
use Modules\FraudDetection\Domain\Services\FraudScoringServiceInterface;
use Modules\FraudDetection\Domain\Events\FraudDetected;
use Modules\FraudDetection\Infrastructure\Services\AnalyticsIntegrationService;
use Modules\FraudDetection\Domain\Exceptions\FraudulentTransactionException;
use App\Domain\Audit\Events\AuditEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class CheckTransactionForFraudUseCase
{
    public function __construct(
        private readonly FraudScoringServiceInterface $scoringService,
        private readonly FraudAttemptRepositoryInterface $fraudAttemptRepository,
        private readonly AnalyticsIntegrationService $analyticsService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Dispatcher $eventDispatcher,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @throws FraudulentTransactionException
     */
    public function execute(FraudCheckData $data): void
    {
        $correlationId = $data->correlationId ?? (string) Str::uuid();
        
        $this->db->transaction(function () use ($data, $correlationId): void {
            $fraudScore = $this->scoringService->getScore($data);
            $threshold = (float) $this->config->get('frauddetection.threshold', 0.85);

            $this->analyticsService->trackEvent('transaction_scored', [
                'transaction_id' => $data->transactionId,
                'score' => $fraudScore->getScore(),
                'correlation_id' => $correlationId,
            ]);

            // AUDIT LOG - Fraud check initiated (Domain Event for Clean Architecture)
            Event::dispatch(AuditEvent::action(
                action: 'fraud_check_initiated',
                subjectType: 'Transaction',
                subjectId: $data->transactionId,
                context: [
                    'operation_type' => $data->operationType ?? 'unknown',
                    'amount' => $data->amount ?? null,
                    'currency' => $data->currency ?? null,
                    'user_id' => $data->userId,
                    'tenant_id' => $data->tenantId ?? null,
                ],
                correlationId: $correlationId
            ));

            if ($fraudScore->isFraudulent($threshold)) {
                // AUDIT LOG - Fraud detected (Domain Event for Clean Architecture)
                Event::dispatch(AuditEvent::action(
                    action: 'fraud_check_blocked',
                    subjectType: 'Transaction',
                    subjectId: $data->transactionId,
                    context: [
                        'operation_type' => $data->operationType ?? 'transaction',
                        'fraud_score' => $fraudScore->getScore(),
                        'decision' => 'blocked',
                        'transaction_id' => $data->transactionId,
                        'threshold' => $threshold,
                        'details' => array_slice($data->toArray(), 0, 10), // Limit context size
                        'user_id' => $data->userId,
                        'tenant_id' => $data->tenantId ?? null,
                    ],
                    correlationId: $correlationId
                ));

                $this->fraudAttemptRepository->create([
                    'transaction_id' => $data->transactionId,
                    'user_id' => $data->userId,
                    'score' => $fraudScore->getScore(),
                    'details' => $data->toArray(),
                    'correlation_id' => $correlationId,
                ]);

                $this->eventDispatcher->dispatch(new FraudDetected(
                    transactionId: $data->transactionId,
                    userId: $data->userId,
                    score: $fraudScore->getScore(),
                    correlationId: $correlationId
                ));

                $this->logger->warning('Fraudulent transaction detected and blocked.', [
                    'transaction_id' => $data->transactionId,
                    'user_id' => $data->userId,
                    'score' => $fraudScore->getScore(),
                    'correlation_id' => $correlationId,
                ]);

                throw new FraudulentTransactionException(
                    "Transaction {$data->transactionId} is likely fraudulent."
                );
            }

            // AUDIT LOG - Fraud check passed (Domain Event for Clean Architecture)
            Event::dispatch(AuditEvent::action(
                action: 'fraud_check_approved',
                subjectType: 'Transaction',
                subjectId: $data->transactionId,
                context: [
                    'operation_type' => $data->operationType ?? 'transaction',
                    'fraud_score' => $fraudScore->getScore(),
                    'decision' => 'approved',
                    'transaction_id' => $data->transactionId,
                    'threshold' => $threshold,
                    'user_id' => $data->userId,
                    'tenant_id' => $data->tenantId ?? null,
                ],
                correlationId: $correlationId
            ));

            $this->logger->info('Transaction passed fraud check.', [
                'transaction_id' => $data->transactionId,
                'score' => $fraudScore->getScore(),
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
