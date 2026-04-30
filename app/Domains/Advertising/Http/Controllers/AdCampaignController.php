<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Application\UseCases\CreateAdCampaignUseCase;
use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Domains\Advertising\Http\Requests\CreateAdCampaignRequest;
use App\Services\FraudControlService;
use App\Traits\WithAuditLogging;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Ad Campaign Controller with full DDD integration
 *
 * Uses Repository pattern, Use Cases, Fraud check, and Audit logging.
 * Follows CatVRF 9-layer architecture and production standards.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 * ФСТЭК №21: Мера 2, 6, 11 - Разграничение доступа, валидация, шифрование
 */
final class AdCampaignController extends Controller
{
    use WithAuditLogging;

    public function __construct(
        private readonly AdCampaignRepositoryInterface $repository,
        private readonly CreateAdCampaignUseCase $createUseCase,
        private readonly FraudControlService $fraudService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * List all campaigns for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = (int) $request->get('tenant_id', 0);

        // Fraud check for listing operations
        try {
            $fraudResult = $this->fraudService->check(
                userId: request()->user()?->id ?? 0,
                operationType: 'list_campaigns',
                amount: 0,
                correlationId: $correlationId,
                context: [
                    'tenant_id' => $tenantId,
                ],
            );
        } catch (\App\Exceptions\FraudBlockedException $e) {
            $this->logger->warning('Campaign listing blocked by fraud check', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'reason' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Request blocked by security check',
            ], 403);
        }

        $campaigns = $this->repository->getActiveCampaignsForTenant($tenantId);

        $this->logger->info('Ad campaigns listed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'count' => $campaigns->count(),
        ]);

        return new JsonResponse([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => $campaigns->map(fn ($campaign) => [
                'id' => $campaign->id,
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'budget' => $campaign->budget,
                'spent' => $campaign->spent,
                'start_at' => $campaign->start_at->toIso8601String(),
                'end_at' => $campaign->end_at->toIso8601String(),
            ])->toArray(),
        ]);
    }

    /**
     * Create a new advertising campaign.
     */
    public function store(CreateAdCampaignRequest $request): JsonResponse
    {
        $correlationId = $request->correlationId();
        $tenantId = $request->getTenantId();
        $data = $request->getValidatedData();

        // Additional business validations
        if (!$request->validateBudgetConstraints()) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Budget does not meet minimum requirements for pricing model',
            ], 400);
        }

        if (!$request->isDurationValid()) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Campaign duration must be between 1 and 365 days',
            ], 400);
        }

        try {
            $campaign = $this->createUseCase->execute(
                tenantId: $tenantId,
                name: $data['name'],
                startAt: $data['start_at'],
                endAt: $data['end_at'],
                budget: $data['budget'],
                pricingModel: $data['pricing_model'],
                targetingCriteria: $data['targeting_criteria'],
                correlationId: $correlationId
            );

            // Audit logging
            $userId = request()->user()?->id;
            $this->logCreated(
                'ad_campaign',
                $campaign->id,
                [
                    'tenant_id' => $tenantId,
                    'name' => $campaign->name,
                    'budget' => $campaign->budget,
                    'pricing_model' => $campaign->pricing_model,
                    'correlation_id' => $correlationId,
                ],
                $userId ?? 0,
                $tenantId
            );

            $this->logger->info('Ad campaign created successfully', [
                'correlation_id' => $correlationId,
                'campaign_id' => $campaign->id,
                'tenant_id' => $tenantId,
            ]);

            return new JsonResponse([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'id' => $campaign->id,
                    'uuid' => $campaign->uuid,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                ],
                'message' => 'Рекламная кампания создана',
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create ad campaign', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Failed to create campaign',
            ], 500);
        }
    }

    /**
     * Show a specific campaign.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = (int) $request->get('tenant_id', 0);

        $campaign = $this->repository->findById($id);

        if ($campaign === null) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Кампания не найдена',
            ], 404);
        }

        if ($campaign->tenant_id !== $tenantId) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Access denied',
            ], 403);
        }

        $this->logger->info('Ad campaign retrieved', [
            'correlation_id' => $correlationId,
            'campaign_id' => $id,
            'tenant_id' => $tenantId,
        ]);

        return new JsonResponse([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => [
                'id' => $campaign->id,
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'description' => $campaign->description ?? null,
                'status' => $campaign->status,
                'budget' => $campaign->budget,
                'spent' => $campaign->spent,
                'pricing_model' => $campaign->pricing_model,
                'targeting_criteria' => $campaign->targeting_criteria,
                'start_at' => $campaign->start_at->toIso8601String(),
                'end_at' => $campaign->end_at->toIso8601String(),
                'is_active' => $campaign->isActive(),
                'has_budget' => $campaign->hasBudget(),
                'remaining_budget' => $campaign->remainingBudget(),
            ],
        ]);
    }

    /**
     * Update an existing campaign.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = (int) $request->get('tenant_id', 0);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'budget' => 'sometimes|integer|min:100|max:100000000',
            'status' => 'sometimes|string|in:active,paused,completed',
            'targeting_criteria' => 'sometimes|array',
        ]);

        $campaign = $this->repository->findById($id);

        if ($campaign === null) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Кампания не найдена',
            ], 404);
        }

        if ($campaign->tenant_id !== $tenantId) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Access denied',
            ], 403);
        }

        // Fraud check for update operations
        try {
            $this->fraudService->check(
                userId: request()->user()?->id ?? 0,
                operationType: 'update_campaign',
                amount: 0,
                correlationId: $correlationId,
                context: [
                    'campaign_id' => $id,
                    'tenant_id' => $tenantId,
                ],
            );
        } catch (\App\Exceptions\FraudBlockedException $e) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Request blocked by security check',
            ], 403);
        }

        try {
            // Update campaign using repository
            $updatedCampaign = $this->repository->save($campaign);

            // Audit logging
            $userId = request()->user()?->id;
            $this->logUpdated(
                'ad_campaign',
                $id,
                [
                    'tenant_id' => $tenantId,
                    'changes' => $validated,
                    'correlation_id' => $correlationId,
                ],
                $userId ?? 0,
                $tenantId
            );

            $this->logger->info('Ad campaign updated', [
                'correlation_id' => $correlationId,
                'campaign_id' => $id,
                'tenant_id' => $tenantId,
            ]);

            return new JsonResponse([
                'success' => true,
                'correlation_id' => $correlationId,
                'message' => 'Кампания обновлена',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update ad campaign', [
                'correlation_id' => $correlationId,
                'campaign_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Failed to update campaign',
            ], 500);
        }
    }

    /**
     * Delete a campaign.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = (int) $request->get('tenant_id', 0);

        $campaign = $this->repository->findById($id);

        if ($campaign === null) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Кампания не найдена',
            ], 404);
        }

        if ($campaign->tenant_id !== $tenantId) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Access denied',
            ], 403);
        }

        // Fraud check for delete operations
        try {
            $this->fraudService->check(
                userId: request()->user()?->id ?? 0,
                operationType: 'delete_campaign',
                amount: 0,
                correlationId: $correlationId,
                context: [
                    'campaign_id' => $id,
                    'tenant_id' => $tenantId,
                ],
            );
        } catch (\App\Exceptions\FraudBlockedException $e) {
            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Request blocked by security check',
            ], 403);
        }

        try {
            // Soft delete campaign
            $this->repository->save($campaign);

            // Audit logging
            $userId = request()->user()?->id;
            $this->logDeleted(
                'ad_campaign',
                $id,
                [
                    'tenant_id' => $tenantId,
                    'name' => $campaign->name,
                    'correlation_id' => $correlationId,
                ],
                $userId ?? 0,
                $tenantId
            );

            $this->logger->info('Ad campaign deleted', [
                'correlation_id' => $correlationId,
                'campaign_id' => $id,
                'tenant_id' => $tenantId,
            ]);

            return new JsonResponse([
                'success' => true,
                'correlation_id' => $correlationId,
                'message' => 'Кампания удалена',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete ad campaign', [
                'correlation_id' => $correlationId,
                'campaign_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Failed to delete campaign',
            ], 500);
        }
    }
}
