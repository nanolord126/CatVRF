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

final class CheckTransactionForFraudUseCase
{
    public function __construct(
        private readonly FraudScoringServiceInterface $scoringService,
        private readonly FraudAttemptRepositoryInterface $fraudAttemptRepository,
        private readonly AnalyticsIntegrationService $analyticsService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Dispatcher $eventDispatcher,
        private readonly ConfigRepository $config
    ) {
    }

    /**
     * @throws FraudulentTransactionException
     */
    public function execute(FraudCheckData $data): void
    {
        $this->db->transaction(function () use ($data): void {
            $fraudScore = $this->scoringService->getScore($data);

            $this->analyticsService->trackEvent('transaction_scored', [
                'transaction_id' => $data->transactionId,
                'score' => $fraudScore->getScore(),
                'correlation_id' => $data->correlationId,
            ]);

            $threshold = (float) $this->config->get('frauddetection.threshold', 0.85);

            if ($fraudScore->isFraudulent($threshold)) {
                $this->fraudAttemptRepository->create([
                    'transaction_id' => $data->transactionId,
                    'user_id' => $data->userId,
                    'score' => $fraudScore->getScore(),
                    'details' => $data->toArray(),
                    'correlation_id' => $data->correlationId,
                ]);

                $this->eventDispatcher->dispatch(new FraudDetected(
                    transactionId: $data->transactionId,
                    userId: $data->userId,
                    score: $fraudScore->getScore(),
                    correlationId: $data->correlationId
                ));

                $this->logger->warning('Fraudulent transaction detected and blocked.', [
                    'transaction_id' => $data->transactionId,
                    'user_id' => $data->userId,
                    'score' => $fraudScore->getScore(),
                    'correlation_id' => $data->correlationId,
                ]);

                throw new FraudulentTransactionException(
                    "Transaction {$data->transactionId} is likely fraudulent."
                );
            }

            $this->logger->info('Transaction passed fraud check.', [
                'transaction_id' => $data->transactionId,
                'score' => $fraudScore->getScore(),
                'correlation_id' => $data->correlationId,
            ]);
        });
    }
}
