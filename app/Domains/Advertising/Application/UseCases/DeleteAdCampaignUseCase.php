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
 * Delete Ad Campaign Use Case
 *
 * Handles soft-deleting advertising campaigns with fraud checks
 * and audit logging.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class DeleteAdCampaignUseCase
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
        int $userId = 0,
        string $correlationId = '',
    ): bool {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Fraud check for delete operation
        $this->fraudService->check(
            userId: $userId,
            operationType: 'delete_ad_campaign',
            amount: 0,
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

        // Business validation: cannot delete active campaigns
        if ($campaign->status === 'active') {
            throw new \InvalidArgumentException('Cannot delete active campaign. Pause it first.');
        }

        // Soft delete by updating status to cancelled
        $deletedCampaign = new AdCampaign(
            id: $campaign->id,
            uuid: $campaign->uuid,
            tenant_id: $tenantId,
            name: $campaign->name,
            status: 'cancelled',
            start_at: $campaign->start_at,
            end_at: $campaign->end_at,
            budget: $campaign->budget,
            spent: $campaign->spent,
            pricing_model: $campaign->pricing_model,
            targeting_criteria: $campaign->targeting_criteria,
            correlation_id: $correlationId,
        );

        // Save cancelled status
        $this->repository->save($deletedCampaign);

        // Audit logging
        $this->logDeleted(
            'ad_campaign',
            $campaignId,
            [
                'tenant_id' => $tenantId,
                'name' => $campaign->name,
                'correlation_id' => $correlationId,
            ],
            $userId,
            $tenantId
        );

        $this->logger->info('Ad campaign deleted successfully', [
            'correlation_id' => $correlationId,
            'campaign_id' => $campaignId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ]);

        return true;
    }
}
