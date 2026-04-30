<?php

declare(strict_types=1);

namespace App\Domains\Medical\Services;

use Psr\Log\LoggerInterface;

use App\DTOs\AI\MedicalDiagnosisDTO;
use App\DTOs\AI\DiagnosisResultDTO;
use App\Services\AI\AIDiagnosticsService;
use Illuminate\Log\LogManager;
use Laravel\Pennant\Feature;

/**
 * Medical Diagnosis Service with Feature Flag Integration
 *
 * This service uses feature flags to control the rollout of AI-powered diagnosis.
 * When the feature is disabled, it falls back to traditional diagnosis methods.
 */
final class MedicalDiagnosisService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AIDiagnosticsService $aiService,
        private readonly TraditionalDiagnosisService $traditionalService,
        private readonly LogManager $log,) {}

    /**
     * Diagnose patient symptoms with AI or traditional method based on feature flag
     */
    public function diagnose(MedicalDiagnosisDTO $dto): DiagnosisResultDTO
    {
        // Use feature flag to determine which diagnosis method to use
        return Feature::when(
            'medical-ai-diagnosis',
            fn () => $this->diagnoseWithAI($dto),
            fn () => $this->diagnoseTraditional($dto)
        );
    }

    /**
     * Check if AI diagnosis is available for the current user/tenant
     */
    public function isAIDiagnosisAvailable(?int $userId = null, ?int $tenantId = null): bool
    {
        if ($userId !== null) {
            return Feature::for($userId)->active('medical-ai-diagnosis');
        }

        if ($tenantId !== null) {
            return Feature::for($tenantId)->active('medical-ai-diagnosis');
        }

        return Feature::active('medical-ai-diagnosis');
    }

    /**
     * AI-powered diagnosis (new feature)
     */
    private function diagnoseWithAI(MedicalDiagnosisDTO $dto): DiagnosisResultDTO
    {
        $this->log->$this->logger->info('Using AI-powered diagnosis', [
            'feature' => 'medical-ai-diagnosis',
            'symptoms' => $dto->symptoms,
        ]);

        return $this->aiService->diagnose($dto);
    }

    /**
     * Traditional diagnosis (fallback)
     */
    private function diagnoseTraditional(MedicalDiagnosisDTO $dto): DiagnosisResultDTO
    {
        $this->log->$this->logger->info('Using traditional diagnosis (feature flag disabled)', [
            'feature' => 'medical-ai-diagnosis',
            'symptoms' => $dto->symptoms,
        ]);

        return $this->traditionalService->diagnose($dto);
    }
}
