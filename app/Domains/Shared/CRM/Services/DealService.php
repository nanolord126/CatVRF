<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;

use App\Domains\CRM\DTOs\CreateDealDto;
use App\Domains\CRM\Models\CrmDeal;
use App\Domains\CRM\Models\CrmPipeline;
use App\Domains\CRM\Models\CrmStage;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * DealService — сервис для управления сделками.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class DealService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly PipelineService $pipelineService,
    ) {}

    public function createDeal(CreateDealDto $dto): CrmDeal
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_deal_create',
            amount: $dto->value,
            correlationId: $dto->correlationId
        );

        return $this->db->transaction(function () use ($dto): CrmDeal {
            $deal = CrmDeal::query()->create($dto->toArray());

            $this->logger->info('CRM deal created', [
                'deal_id' => $deal->id,
                'tenant_id' => $dto->tenantId,
                'pipeline_id' => $dto->pipelineId,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_deal_created',
                CrmDeal::class,
                $deal->id,
                [],
                $dto->toArray(),
                $dto->correlationId
            );

            return $deal;
        });
    }

    public function createDealFromOrder(
        int $tenantId,
        ?int $businessGroupId,
        int $customerId,
        int $orderId,
        string $orderTitle,
        int $orderValue,
        string $vertical,
        ?string $correlationId = null
    ): CrmDeal {
        $pipeline = $this->pipelineService->initializeDefaultPipeline($tenantId, $vertical);
        $firstStage = $pipeline->stages->first();

        if ($firstStage === null) {
            throw new \RuntimeException("Pipeline has no stages for vertical: {$vertical}");
        }

        return $this->createDeal(new CreateDealDto(
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            pipelineId: $pipeline->id,
            stageId: $firstStage->id,
            customerId: $customerId,
            title: $orderTitle,
            value: $orderValue,
            status: 'new',
            source: 'marketplace',
            marketplaceOrderId: $orderId,
            correlationId: $correlationId,
        ));
    }

    public function moveDealToStage(int $dealId, int $stageId, ?string $reason = null): CrmDeal
    {
        return $this->db->transaction(function () use ($dealId, $stageId, $reason): CrmDeal {
            $deal = CrmDeal::query()->findOrFail($dealId);
            $stage = CrmStage::query()->findOrFail($stageId);

            $deal->moveToStage($stage, $reason);

            $this->logger->info('CRM deal moved to stage', [
                'deal_id' => $deal->id,
                'stage_id' => $stageId,
                'status' => $deal->status,
                'reason' => $reason,
            ]);

            $this->audit->log(
                'crm_deal_stage_changed',
                CrmDeal::class,
                $deal->id,
                ['stage_id' => $deal->stage_id],
                ['stage_id' => $stageId, 'reason' => $reason],
            );

            return $deal->fresh();
        });
    }

    public function updateDeal(int $dealId, array $data, ?string $correlationId = null): CrmDeal
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_deal_update',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($dealId, $data, $correlationId): CrmDeal {
            $deal = CrmDeal::query()->findOrFail($dealId);
            $oldValues = $deal->toArray();

            $deal->update($data);

            $this->logger->info('CRM deal updated', [
                'deal_id' => $deal->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_deal_updated',
                CrmDeal::class,
                $deal->id,
                $oldValues,
                $data,
                $correlationId
            );

            return $deal->fresh();
        });
    }

    public function getDealById(int $dealId, int $tenantId): CrmDeal
    {
        return CrmDeal::query()
            ->where('id', $dealId)
            ->where('tenant_id', $tenantId)
            ->with(['pipeline', 'stage', 'customer', 'assignedTo', 'tasks'])
            ->firstOrFail();
    }

    public function listDeals(
        int $tenantId,
        ?string $status = null,
        ?int $pipelineId = null,
        ?int $stageId = null,
        ?int $assignedToId = null,
        ?string $search = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = CrmDeal::query()->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($pipelineId !== null) {
            $query->where('pipeline_id', $pipelineId);
        }

        if ($stageId !== null) {
            $query->where('stage_id', $stageId);
        }

        if ($assignedToId !== null) {
            $query->where('assigned_to_id', $assignedToId);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('contact_email', 'ilike', "%{$search}%");
            });
        }

        return $query->with(['pipeline', 'stage', 'customer', 'assignedTo'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getDealsByCustomer(int $customerId, int $tenantId): Collection
    {
        return CrmDeal::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->with(['pipeline', 'stage'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getOverdueDeals(int $tenantId): Collection
    {
        return CrmDeal::query()
            ->where('tenant_id', $tenantId)
            ->overdue()
            ->with(['pipeline', 'stage', 'customer', 'assignedTo'])
            ->get();
    }

    public function getPipelineStats(int $pipelineId): array
    {
        $stages = CrmStage::query()->where('pipeline_id', $pipelineId)->ordered()->get();
        $stats = [];

        foreach ($stages as $stage) {
            $dealsCount = CrmDeal::query()
                ->where('pipeline_id', $pipelineId)
                ->where('stage_id', $stage->id)
                ->whereIn('status', ['new', 'in_progress', 'negotiation'])
                ->count();

            $totalValue = CrmDeal::query()
                ->where('pipeline_id', $pipelineId)
                ->where('stage_id', $stage->id)
                ->whereIn('status', ['new', 'in_progress', 'negotiation'])
                ->sum('value');

            $stats[] = [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'deals_count' => $dealsCount,
                'total_value' => $totalValue,
            ];
        }

        return $stats;
    }
}
