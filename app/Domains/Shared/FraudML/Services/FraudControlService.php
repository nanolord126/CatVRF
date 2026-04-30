<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Psr\Log\LoggerInterface;

use App\DTOs\Fraud\FraudCheckDTO;
use App\DTOs\Fraud\FraudResultDTO;
use Illuminate\Log\LogManager;
use Laravel\Pennant\Feature;

/**
 * Fraud Control Service with Feature Flag Integration
 *
 * This service uses feature flags to control the rollout of new FraudML models.
 * Supports shadow mode for comparing new vs old models.
 */
final class FraudControlService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudMLServiceV1 $modelV1,
        private readonly FraudMLServiceV2 $modelV2,
        private readonly LogManager $log,) {}

    /**
     * Check transaction for fraud using appropriate model based on feature flags
     */
    public function check(FraudCheckDTO $dto): FraudResultDTO
    {
        $useV2 = Feature::active('fraud-ml-model-v2');
        $shadowMode = Feature::active('fraud-ml-model-v2') && $this->isShadowMode();

        if ($shadowMode) {
            // Shadow mode: run both models, use V1 for decision, log V2 for comparison
            return $this->checkInShadowMode($dto);
        }

        if ($useV2) {
            $this->log->$this->logger->info('Using FraudML Model V2', ['transaction_id' => $dto->transactionId]);

            return $this->modelV2->check($dto);
        }

        $this->log->$this->logger->info('Using FraudML Model V1 (default)', ['transaction_id' => $dto->transactionId]);

        return $this->modelV1->check($dto);
    }

    /**
     * Get model version being used for a specific tenant
     */
    public function getModelVersionForTenant(int $tenantId): string
    {
        if (Feature::for($tenantId)->active('fraud-ml-model-v2')) {
            return 'v2';
        }

        return 'v1';
    }

    /**
     * Enable V2 model for specific tenant (for testing/beta)
     */
    public function enableV2ForTenant(int $tenantId): void
    {
        Feature::for($tenantId)->activate('fraud-ml-model-v2');
        $this->log->$this->logger->info('FraudML V2 enabled for tenant', ['tenant_id' => $tenantId]);
    }

    /**
     * Disable V2 model for specific tenant (rollback)
     */
    public function disableV2ForTenant(int $tenantId): void
    {
        Feature::for($tenantId)->deactivate('fraud-ml-model-v2');
        $this->log->$this->logger->info('FraudML V2 disabled for tenant', ['tenant_id' => $tenantId]);
    }

    /**
     * Check in shadow mode - run both models, use V1 for decision
     */
    private function checkInShadowMode(FraudCheckDTO $dto): FraudResultDTO
    {
        $resultV1 = $this->modelV1->check($dto);
        $resultV2 = $this->modelV2->check($dto);

        // Log comparison for monitoring
        $this->log->$this->logger->info('FraudML Shadow Mode Comparison', [
            'transaction_id' => $dto->transactionId,
            'v1_score' => $resultV1->fraudScore,
            'v2_score' => $resultV2->fraudScore,
            'v1_decision' => $resultV1->isFraud,
            'v2_decision' => $resultV2->isFraud,
            'agreement' => $resultV1->isFraud === $resultV2->isFraud,
        ]);

        // Use V1 for actual decision, but track V2 performance
        return $resultV1;
    }

    /**
     * Check if shadow mode is active
     */
    private function isShadowMode(): bool
    {
        // Shadow mode is enabled when V2 feature is active but not fully rolled out
        // This can be controlled via percentage or specific tenants
        return Feature::value('fraud-ml-model-v2') === 'shadow';
    }
}
