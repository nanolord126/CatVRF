<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Infrastructure\Services;

use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
use Modules\FraudDetection\Application\DTOs\FraudCheckData;
use Modules\FraudDetection\Domain\DTOs\FraudScore;
use Modules\FraudDetection\Domain\Services\FraudScoringServiceInterface;
use Modules\FraudDetection\Domain\Exceptions\FraudServiceException;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class HttpFraudScoringService implements FraudScoringServiceInterface
{
    use WithAuditLogging;

    public function __construct(
        private readonly HttpClientFactory $httpClientFactory,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    public function getScore(FraudCheckData $data): FraudScore
    {
        $endpoint = $this->config->get('frauddetection.services.endpoint');
        $timeout = (int) $this->config->get('frauddetection.services.timeout', 5);

        if (empty($endpoint)) {
            throw new FraudServiceException('Fraud scoring service endpoint is not configured.');
        }

        try {
            $response = $this->httpClientFactory->timeout($timeout)->post($endpoint, [
                'transaction_id' => $data->transactionId,
                'user_id' => $data->userId,
                'amount' => $data->amount,
                'currency' => $data->metadata['currency'] ?? 'RUB',
                'payment_method' => $data->metadata['payment_method'] ?? 'unknown',
                'ip_address' => $data->ipAddress,
                'device_id' => $data->deviceFingerprint,
                'correlation_id' => $data->correlationId,
                'extra_metadata' => $data->metadata,
            ]);

            $response->throw();

            $score = (float) $response->json('score', 0.0);

            $this->logAction(
                action: 'fraud_score_calculated',
                entityType: 'FraudCheck',
                entityId: $data->transactionId,
                context: [
                    'user_id' => $data->userId,
                    'amount' => $data->amount,
                    'score' => $score,
                    'correlation_id' => $data->correlationId,
                ],
                userId: $data->userId,
                tenantId: null
            );

            return new FraudScore($score);
        } catch (RequestException $e) {
            $this->logger->error('Fraud scoring service request failed.', [
                'error' => $e->getMessage(),
                'correlation_id' => $data->correlationId,
            ]);

            $this->logAction(
                action: 'fraud_score_failed',
                entityType: 'FraudCheck',
                entityId: $data->transactionId,
                context: [
                    'user_id' => $data->userId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $data->correlationId,
                ],
                userId: $data->userId,
                tenantId: null
            );

            // Fallback to a neutral score in case of service failure
            // as per production-ready canon
            return new FraudScore(0.1);
        }
    }
}
