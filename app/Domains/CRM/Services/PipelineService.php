<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;

use App\Domains\CRM\DTOs\CreatePipelineDto;
use App\Domains\CRM\Models\CrmPipeline;
use App\Domains\CRM\Models\CrmStage;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * PipelineService — сервис для управления воронками.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class PipelineService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
    ) {}

    public function createPipeline(CreatePipelineDto $dto): CrmPipeline
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_pipeline_create',
            amount: 0,
            correlationId: $dto->correlationId
        );

        return $this->db->transaction(function () use ($dto): CrmPipeline {
            $pipeline = CrmPipeline::query()->create($dto->toArray());

            $this->logger->info('CRM pipeline created', [
                'pipeline_id' => $pipeline->id,
                'tenant_id' => $dto->tenantId,
                'vertical' => $dto->vertical,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_pipeline_created',
                CrmPipeline::class,
                $pipeline->id,
                [],
                $dto->toArray(),
                $dto->correlationId
            );

            return $pipeline;
        });
    }

    public function getPipelineById(int $pipelineId, int $tenantId): CrmPipeline
    {
        return CrmPipeline::query()
            ->where('id', $pipelineId)
            ->where('tenant_id', $tenantId)
            ->with('stages')
            ->firstOrFail();
    }

    public function getDefaultPipeline(int $tenantId, string $vertical): ?CrmPipeline
    {
        return CrmPipeline::query()
            ->where('tenant_id', $tenantId)
            ->where('vertical', $vertical)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('stages')
            ->first();
    }

    public function getPipelinesByVertical(int $tenantId, string $vertical): Collection
    {
        return CrmPipeline::query()
            ->where('tenant_id', $tenantId)
            ->where('vertical', $vertical)
            ->where('is_active', true)
            ->with('stages')
            ->orderBy('order')
            ->get();
    }

    public function createStage(
        int $pipelineId,
        int $tenantId,
        string $name,
        string $key,
        ?string $description = null,
        int $order = 0,
        ?string $color = null,
        int $probability = 0,
        bool $isFinal = false,
        bool $isWon = false,
        bool $isLost = false,
        ?array $autoTransitionRules = null,
        ?int $timeLimitHours = null,
        ?string $correlationId = null
    ): CrmStage {
        return $this->db->transaction(function () use (
            $pipelineId,
            $tenantId,
            $name,
            $key,
            $description,
            $order,
            $color,
            $probability,
            $isFinal,
            $isWon,
            $isLost,
            $autoTransitionRules,
            $timeLimitHours,
            $correlationId
        ): CrmStage {
            $stage = CrmStage::query()->create([
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => $name,
                'key' => $key,
                'description' => $description,
                'order' => $order,
                'color' => $color,
                'probability' => $probability,
                'is_final' => $isFinal,
                'is_won' => $isWon,
                'is_lost' => $isLost,
                'auto_transition_rules' => $autoTransitionRules,
                'time_limit_hours' => $timeLimitHours,
            ]);

            $this->logger->info('CRM stage created', [
                'stage_id' => $stage->id,
                'pipeline_id' => $pipelineId,
                'key' => $key,
                'correlation_id' => $correlationId,
            ]);

            return $stage;
        });
    }

    public function initializeDefaultPipeline(int $tenantId, string $vertical): CrmPipeline
    {
        $pipeline = $this->getDefaultPipeline($tenantId, $vertical);

        if ($pipeline !== null) {
            return $pipeline;
        }

        $stages = match ($vertical) {
            'hotels' => [
                ['name' => 'Lead', 'key' => 'lead', 'order' => 1, 'probability' => 10],
                ['name' => 'Qualification', 'key' => 'qualification', 'order' => 2, 'probability' => 30],
                ['name' => 'Booking', 'key' => 'booking', 'order' => 3, 'probability' => 70],
                ['name' => 'Check-in', 'key' => 'check_in', 'order' => 4, 'probability' => 90],
                ['name' => 'Stay', 'key' => 'stay', 'order' => 5, 'probability' => 95],
                ['name' => 'Check-out', 'key' => 'check_out', 'order' => 6, 'probability' => 100, 'is_won' => true],
            ],
            'beauty' => [
                ['name' => 'Запись', 'key' => 'booking', 'order' => 1, 'probability' => 20],
                ['name' => 'Подтверждение', 'key' => 'confirmed', 'order' => 2, 'probability' => 50],
                ['name' => 'Услуга', 'key' => 'service', 'order' => 3, 'probability' => 90],
                ['name' => 'Завершено', 'key' => 'completed', 'order' => 4, 'probability' => 100, 'is_won' => true],
            ],
            'flowers' => [
                ['name' => 'Заказ', 'key' => 'order', 'order' => 1, 'probability' => 20],
                ['name' => 'Дизайн', 'key' => 'design', 'order' => 2, 'probability' => 40],
                ['name' => 'Сборка', 'key' => 'assembly', 'order' => 3, 'probability' => 70],
                ['name' => 'Доставка', 'key' => 'delivery', 'order' => 4, 'probability' => 90],
                ['name' => 'Доставлено', 'key' => 'delivered', 'order' => 5, 'probability' => 100, 'is_won' => true],
            ],
            'taxi' => [
                ['name' => 'Новый заказ', 'key' => 'new', 'order' => 1, 'probability' => 20],
                ['name' => 'Назначен водитель', 'key' => 'assigned', 'order' => 2, 'probability' => 50],
                ['name' => 'Подача', 'key' => 'pickup', 'order' => 3, 'probability' => 70],
                ['name' => 'Поездка', 'key' => 'in_progress', 'order' => 4, 'probability' => 90],
                ['name' => 'Завершено', 'key' => 'completed', 'order' => 5, 'probability' => 100, 'is_won' => true],
            ],
            default => [
                ['name' => 'Lead', 'key' => 'lead', 'order' => 1, 'probability' => 10],
                ['name' => 'Qualification', 'key' => 'qualification', 'order' => 2, 'probability' => 30],
                ['name' => 'Proposal', 'key' => 'proposal', 'order' => 3, 'probability' => 50],
                ['name' => 'Negotiation', 'key' => 'negotiation', 'order' => 4, 'probability' => 70],
                ['name' => 'Won', 'key' => 'won', 'order' => 5, 'probability' => 100, 'is_won' => true],
            ],
        };

        $pipeline = $this->createPipeline(new CreatePipelineDto(
            tenantId: $tenantId,
            businessGroupId: null,
            name: ucfirst($vertical).' Pipeline',
            slug: strtolower($vertical).'-pipeline',
            vertical: $vertical,
            description: "Default pipeline for {$vertical}",
            isDefault: true,
            isActive: true,
            order: 0,
        ));

        foreach ($stages as $stageData) {
            $this->createStage(
                pipelineId: $pipeline->id,
                tenantId: $tenantId,
                name: $stageData['name'],
                key: $stageData['key'],
                order: $stageData['order'],
                probability: $stageData['probability'],
                isWon: $stageData['is_won'] ?? false,
            );
        }

        return $pipeline->fresh('stages');
    }
}
