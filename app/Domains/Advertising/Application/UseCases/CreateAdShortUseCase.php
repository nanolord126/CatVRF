<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Application\UseCases;

use App\Domains\Advertising\Domain\Entities\AdShort;
use App\Domains\Advertising\Domain\Interfaces\AdShortRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;

/**
 * Create Ad Short Use Case
 *
 * Handles creation of short video advertisements.
 * Includes fraud checks, validation, and audit logging.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class CreateAdShortUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly AdShortRepositoryInterface $repository,
        private readonly FraudControlService $fraudService,
        private readonly Dispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(
        int $tenantId,
        string $title,
        string $videoUrl,
        string $thumbnailUrl,
        int $durationSeconds,
        int $budget,
        string $pricingModel,
        array $targetingCriteria,
        int $userId = 0,
        ?string $correlationId = null,
    ): AdShort {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Creating ad short', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'title' => $title,
        ]);

        // Validate duration
        if ($durationSeconds < 15 || $durationSeconds > 60) {
            throw new \InvalidArgumentException('Short duration must be between 15 and 60 seconds');
        }

        // Validate budget
        if ($budget < 10000) { // Minimum 100 RUB
            throw new \InvalidArgumentException('Minimum budget is 100 RUB');
        }

        // Validate pricing model
        if (!in_array($pricingModel, ['cpm', 'cpc', 'cpa', 'cpv'], true)) {
            throw new \InvalidArgumentException('Invalid pricing model');
        }

        // Fraud check
        try {
            $fraudResult = $this->fraudService->check(
                userId: $userId,
                operationType: 'create_ad_short',
                amount: $budget,
                ipAddress: request()?->ip(),
                deviceFingerprint: request()?->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
                context: ['title' => $title, 'video_url' => $videoUrl],
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Request blocked by security check');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Fraud check failed for ad short creation', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $this->db->transaction(function () use (
            $tenantId,
            $title,
            $videoUrl,
            $thumbnailUrl,
            $durationSeconds,
            $budget,
            $pricingModel,
            $targetingCriteria,
            $userId,
            $correlationId,
        ) {
            // Create ad short entity
            $adShort = AdShort::create(
                tenantId: $tenantId,
                title: $title,
                videoUrl: $videoUrl,
                thumbnailUrl: $thumbnailUrl,
                durationSeconds: $durationSeconds,
                budget: $budget,
                pricingModel: $pricingModel,
                targetingCriteria: $targetingCriteria,
                correlationId: $correlationId,
            );

            // Save to repository
            $savedShort = $this->repository->save($adShort);

            // Audit logging
            $this->logCreated(
                entityType: 'ad_short',
                entityId: $savedShort->id,
                context: [
                    'tenant_id' => (string)$tenantId,
                    'title' => $title,
                    'duration' => $durationSeconds,
                    'budget' => $budget,
                    'pricing_model' => $pricingModel,
                    'correlation_id' => $correlationId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            // Dispatch event
            $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\AdShortCreated(
                adShortId: $savedShort->id,
                tenantId: $tenantId,
                correlationId: $correlationId,
            ));

            $this->logger->info('Ad short created successfully', [
                'correlation_id' => $correlationId,
                'ad_short_id' => $savedShort->id,
                'tenant_id' => $tenantId,
            ]);

            return $savedShort;
        });
    }
}
