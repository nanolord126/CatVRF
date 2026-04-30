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
 * Manage Budget Use Case
 *
 * Handles budget allocation, top-up, and refund operations
 * for advertising campaigns with fraud checks and audit logging.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class ManageBudgetUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly AdCampaignRepositoryInterface $repository,
        private readonly FraudControlService $fraudService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Add funds to campaign budget
     */
    public function topUpBudget(
        int $campaignId,
        int $tenantId,
        int $amount,
        int $userId = 0,
        string $correlationId = '',
    ): AdCampaign {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Fraud check for budget top-up
        $this->fraudService->check(
            userId: $userId,
            operationType: 'budget_topup',
            amount: $amount,
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
        if ($amount < 100) {
            throw new \InvalidArgumentException('Minimum top-up amount is 100 cents');
        }

        if ($campaign->status === 'completed' || $campaign->status === 'cancelled') {
            throw new \InvalidArgumentException('Cannot top-up budget for completed or cancelled campaigns');
        }

        // Update budget
        $newBudget = $campaign->budget + $amount;
        $updatedCampaign = new AdCampaign(
            id: $campaign->id,
            uuid: $campaign->uuid,
            tenant_id: $tenantId,
            name: $campaign->name,
            status: $campaign->status,
            start_at: $campaign->start_at,
            end_at: $campaign->end_at,
            budget: $newBudget,
            spent: $campaign->spent,
            pricing_model: $campaign->pricing_model,
            targeting_criteria: $campaign->targeting_criteria,
            correlation_id: $correlationId,
        );

        $savedCampaign = $this->repository->save($updatedCampaign);

        // Audit logging
        $this->logAction(
            action: 'budget_topup',
            entityType: 'ad_campaign',
            entityId: $campaignId,
            context: [
                'previous_budget' => $campaign->budget,
                'new_budget' => $newBudget,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->logger->info('Campaign budget topped up successfully', [
            'correlation_id' => $correlationId,
            'campaign_id' => (string)$campaignId,
            'tenant_id' => (string)$tenantId,
            'amount' => $amount,
            'new_budget' => $newBudget,
        ]);

        return $savedCampaign;
    }

    /**
     * Record spend against campaign budget
     */
    public function recordSpend(
        int $campaignId,
        int $tenantId,
        int $amount,
        string $reason = '',
        int $userId = 0,
        string $correlationId = '',
    ): void {
        $correlationId = $correlationId ?: (string) Str::uuid();

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
        $newSpent = $campaign->spent + $amount;

        if ($newSpent > $campaign->budget) {
            throw new \InvalidArgumentException('Insufficient budget. Cannot spend more than allocated budget.');
        }

        // Update spent amount
        $this->repository->updateSpent($campaignId, $amount);

        // Audit logging
        $this->logAction(
            action: 'record_spend',
            entityType: 'ad_campaign',
            entityId: $campaignId,
            context: [
                'amount' => $amount,
                'reason' => $reason,
                'previous_spent' => $campaign->spent,
                'new_spent' => $newSpent,
                'remaining_budget' => $campaign->budget - $newSpent,
                'correlation_id' => $correlationId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->logger->info('Campaign spend recorded successfully', [
            'correlation_id' => $correlationId,
            'campaign_id' => $campaignId,
            'tenant_id' => $tenantId,
            'amount' => $amount,
            'new_spent' => $newSpent,
        ]);
    }

    /**
     * Get budget summary for campaign
     */
    public function getBudgetSummary(
        int $campaignId,
        int $tenantId,
    ): array {
        $campaign = $this->repository->findById($campaignId);

        if ($campaign === null) {
            throw new \InvalidArgumentException('Campaign not found');
        }

        if ($campaign->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Access denied: Campaign does not belong to tenant');
        }

        $remaining = $campaign->budget - $campaign->spent;
        $utilization = $campaign->budget > 0 ? ($campaign->spent / $campaign->budget) * 100 : 0;

        return [
            'campaign_id' => $campaign->id,
            'budget' => $campaign->budget,
            'spent' => $campaign->spent,
            'remaining' => max(0, $remaining),
            'utilization_percent' => round($utilization, 2),
            'is_over_budget' => $remaining < 0,
            'status' => $campaign->status,
            'pricing_model' => $campaign->pricing_model,
        ];
    }

    /**
     * Check if campaign has sufficient budget
     */
    public function hasSufficientBudget(
        int $campaignId,
        int $tenantId,
        int $requiredAmount,
    ): bool {
        $campaign = $this->repository->findById($campaignId);

        if ($campaign === null) {
            return false;
        }

        if ($campaign->tenant_id !== $tenantId) {
            return false;
        }

        return ($campaign->budget - $campaign->spent) >= $requiredAmount;
    }
}
