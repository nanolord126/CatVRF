<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFurnitureProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * FurnitureCrmService — CRM-логика для вертикали Мебель/Интерьер/Ремонт.
 *
 * Дизайн-проекты, замеры комнат, этапы ремонта, бюджет,
 * предпочтения стилей и материалов, доставка/сборка.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class FurnitureCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать furniture-профиль CRM-клиента.
     */
    public function createFurnitureProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $interiorStyle = null,
        ?string $propertyType = null,
        ?float $propertyAreaSqm = null,
        ?int $roomsCount = null,
        ?float $renovationBudget = null,
        bool $needsDelivery = false,
        bool $needsAssembly = false,
        bool $needsDesignProject = false,
        ?string $notes = null
    ): CrmFurnitureProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_furniture_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $interiorStyle,
            $propertyType, $propertyAreaSqm, $roomsCount, $renovationBudget,
            $needsDelivery, $needsAssembly, $needsDesignProject, $notes
    ): CrmFurnitureProfile {
            $profile = CrmFurnitureProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'interior_style' => $interiorStyle,
                'property_type' => $propertyType,
                'property_area_sqm' => $propertyAreaSqm,
                'rooms_count' => $roomsCount,
                'renovation_budget' => $renovationBudget,
                'needs_delivery' => $needsDelivery,
                'needs_assembly' => $needsAssembly,
                'needs_design_project' => $needsDesignProject,
                'preferred_materials' => [],
                'preferred_colors' => [],
                'room_dimensions' => [],
                'renovation_stages' => [],
                'purchased_items_history' => [],
                'measurements_data' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Furniture CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'style' => $interiorStyle,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_furniture_profile_created',
                CrmFurnitureProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Сохранить замеры комнаты.
     */
    public function saveRoomMeasurements(
        CrmFurnitureProfile $profile,
        string $roomName,
        array $dimensions,
        string $correlationId
    ): CrmFurnitureProfile {
        return $this->db->transaction(function () use ($profile, $roomName, $dimensions, $correlationId): CrmFurnitureProfile {
            $rooms = $profile->room_dimensions ?? [];
            $rooms[$roomName] = array_merge($dimensions, ['measured_at' => now()->toDateString()]);

            $profile->update(['room_dimensions' => $rooms]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Room measurements saved', [
                'profile_id' => $profile->id,
                'room' => $roomName,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить этап ремонта (демонтаж → электрика → сантехника → стены → пол → мебель).
     */
    public function updateRenovationStage(
        CrmFurnitureProfile $profile,
        string $stageName,
        string $status,
        string $correlationId,
        ?float $stageSpent = null,
        ?string $comment = null
    ): CrmFurnitureProfile {
        return $this->db->transaction(function () use ($profile, $stageName, $status, $correlationId, $stageSpent, $comment): CrmFurnitureProfile {
            $stages = $profile->renovation_stages ?? [];
            $found = false;

            foreach ($stages as &$stage) {
                if (($stage['name'] ?? '') === $stageName) {
                    $stage['status'] = $status;
                    $stage['updated_at'] = now()->toDateString();
                    if ($stageSpent !== null) {
                        $stage['spent'] = $stageSpent;
                    }
                    if ($comment !== null) {
                        $stage['comment'] = $comment;
                    }
                    $found = true;
                    break;
                }
            }
            unset($stage);

            if (!$found) {
                $stages[] = [
                    'name' => $stageName,
                    'status' => $status,
                    'spent' => $stageSpent,
                    'comment' => $comment,
                    'created_at' => now()->toDateString(),
                    'updated_at' => now()->toDateString(),
                ];
            }

            $profile->update(['renovation_stages' => $stages]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_furniture_renovation_stage_updated',
                CrmFurnitureProfile::class,
                $profile->id,
                [],
                ['stage' => $stageName, 'status' => $status],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать покупку мебели.
     */
    public function recordPurchase(
        CrmClient $client,
        string $itemName,
        float $amount,
        string $correlationId,
        ?string $category = null,
        bool $withDelivery = false,
        bool $withAssembly = false
    ): void {
        $this->db->transaction(function () use ($client, $itemName, $amount, $correlationId, $category, $withDelivery, $withAssembly): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'marketplace',
                    direction: 'inbound',
                    content: "Покупка мебели: {$itemName}",
                    metadata: [
                        'item' => $itemName,
                        'category' => $category,
                        'amount' => $amount,
                        'with_delivery' => $withDelivery,
                        'with_assembly' => $withAssembly,
                    ]
    )
    );

            $profile = CrmFurnitureProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmFurnitureProfile) {
                $history = $profile->purchased_items_history ?? [];
                $history[] = [
                    'item' => $itemName,
                    'category' => $category,
                    'amount' => $amount,
                    'date' => now()->toDateString(),
                ];
                $profile->update(['purchased_items_history' => $history]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);
        });
    }

    /**
     * Клиенты с незавершённым ремонтом.
     */
    public function getActiveRenovationProjects(int $tenantId): Collection
    {
        return CrmFurnitureProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('needs_design_project', true)
            ->whereNotNull('renovation_stages')
            ->with('client')
            ->get()
            ->filter(function (CrmFurnitureProfile $profile): bool {
                $stages = $profile->renovation_stages ?? [];
                foreach ($stages as $stage) {
                    if (($stage['status'] ?? '') !== 'completed') {
                        return true;
                    }
                }
                return false;
            });
    }

    /**
     * «Спящие» мебельные клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 90): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('furniture')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
