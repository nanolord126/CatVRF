<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Application\UseCases;

use App\Domains\Advertising\Domain\Entities\AdCampaign;
use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Services\FraudControlService;
use App\Traits\WithAuditLogging;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Update Ad Campaign Use Case
 *
 * Handles updating advertising campaigns with fraud checks,
 * business validation, and audit logging.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class UpdateAdCampaignUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly AdCampaignRepositoryInterface $repository,
        private readonly FraudControlService $fraudService,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(
        int $campaignId,
        int $tenantId,
        ?string $name = null,
        ?string $description = null,
        ?string $status = null,
        ?int $budget = null,
        ?string $pricingModel = null,
        ?CarbonImmutable $startAt = null,
        ?CarbonImmutable $endAt = null,
        ?array $targetingCriteria = null,
        ?array $tags = null,
        ?array $metadata = null,
        int $userId = 0,
        string $correlationId = '',
    ): AdCampaign {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Fraud check for update operation
        $this->fraudService->check(
            userId: $userId,
            operationType: 'update_ad_campaign',
            amount: $budget ?? 0,
            correlationId: $correlationId,
            context: [
                'campaign_id' => $campaignId,
                'tenant_id' => $tenantId,
            ],
        );

        // Get existing campaign
        $campaign = $this->repository->findById($campaignId);

        if ($campaign === null) {
            throw new \InvalidArgumentException('Campaign not found');
        }

        // Verify tenant ownership
        if ($campaign->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Access denied: Campaign does not belong to tenant');
        }

        // Business validation
        $this->validateUpdate($campaign, $status, $budget, $startAt, $endAt);

        // Update campaign entity
        $updatedCampaign = new AdCampaign(
            id: $campaign->id,
            uuid: $campaign->uuid,
            tenant_id: $tenantId,
            name: $name ?? $campaign->name,
            status: $status ?? $campaign->status,
            start_at: $startAt ?? $campaign->start_at,
            end_at: $endAt ?? $campaign->end_at,
            budget: $budget ?? $campaign->budget,
            spent: $campaign->spent,
            pricing_model: $pricingModel ?? $campaign->pricing_model,
            targeting_criteria: $targetingCriteria ?? $campaign->targeting_criteria,
            correlation_id: $correlationId,
        );

        // Save updated campaign
        $savedCampaign = $this->repository->save($updatedCampaign);

        // Audit logging
        $this->logAction(
            action: 'update',
            entityType: 'ad_campaign',
            entityId: $campaignId,
            context: [
                'changes' => [
                    'name' => $name,
                    'status' => $status,
                    'budget' => $budget,
                ],
                'correlation_id' => $correlationId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->logger->info('Ad campaign updated successfully', [
            'correlation_id' => $correlationId,
            'campaign_id' => $campaignId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ]);

        return $savedCampaign;
    }

    private function validateUpdate(
        AdCampaign $campaign,
        ?string $status,
        ?int $budget,
        ?CarbonImmutable $startAt,
        ?CarbonImmutable $endAt,
    ): void {
        // Validate status transitions
        if ($status !== null && !$this->isValidStatusTransition($campaign->status, $status)) {
            throw new \InvalidArgumentException("Invalid status transition from {$campaign->status} to {$status}");
        }

        // Validate budget cannot be less than spent
        if ($budget !== null && $budget < $campaign->spent) {
            throw new \InvalidArgumentException('Budget cannot be less than already spent amount');
        }

        // Validate date range
        if ($startAt !== null && $endAt !== null && $startAt->gte($endAt)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        // Validate campaign duration
        if ($startAt !== null && $endAt !== null) {
            $duration = $startAt->diffInDays($endAt);
            if ($duration > 365) {
                throw new \InvalidArgumentException('Campaign duration cannot exceed 365 days');
            }
        }
    }

    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'draft' => ['active', 'paused', 'cancelled'],
            'active' => ['paused', 'completed', 'cancelled'],
            'paused' => ['active', 'completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        return in_array($to, $validTransitions[$from] ?? [], true);
    }
}
