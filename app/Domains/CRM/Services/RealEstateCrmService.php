<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmRealEstateProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * RealEstateCrmService — CRM-логика для вертикали Недвижимость.
 *
 * Сделки, ипотека, просмотры, объекты интереса, pipeline.
 * Покупатели, продавцы, арендаторы, инвесторы.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class RealEstateCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать realestate-профиль CRM-клиента.
     */
    public function createRealEstateProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        string $clientRole = 'buyer',
        ?float $budgetMin = null,
        ?float $budgetMax = null,
        array $preferredLocations = [],
        array $propertyRequirements = [],
        bool $mortgageNeeded = false,
        ?string $desiredMoveDate = null,
        ?string $notes = null
    ): CrmRealEstateProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_realestate_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $clientRole, $budgetMin,
            $budgetMax, $preferredLocations, $propertyRequirements,
            $mortgageNeeded, $desiredMoveDate, $notes
    ): CrmRealEstateProfile {
            $profile = CrmRealEstateProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'client_role' => $clientRole,
                'budget_min' => $budgetMin,
                'budget_max' => $budgetMax,
                'preferred_locations' => $preferredLocations,
                'property_requirements' => $propertyRequirements,
                'mortgage_needed' => $mortgageNeeded,
                'mortgage_approved' => false,
                'desired_move_date' => $desiredMoveDate,
                'viewed_properties' => [],
                'saved_properties' => [],
                'viewings_count' => 0,
                'deal_history' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('RealEstate CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'role' => $clientRole,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_realestate_profile_created',
                CrmRealEstateProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать просмотр объекта.
     */
    public function recordViewing(
        CrmRealEstateProfile $profile,
        string $propertyId,
        string $correlationId,
        ?string $feedback = null,
        ?int $rating = null
    ): CrmRealEstateProfile {
        return $this->db->transaction(function () use ($profile, $propertyId, $correlationId, $feedback, $rating): CrmRealEstateProfile {
            $viewed = $profile->viewed_properties ?? [];
            $viewed[] = [
                'property_id' => $propertyId,
                'feedback' => $feedback,
                'rating' => $rating,
                'date' => now()->toDateString(),
            ];

            $profile->update([
                'viewed_properties' => $viewed,
                'viewings_count' => ($profile->viewings_count ?? 0) + 1,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'outbound',
                    content: "Просмотр объекта: {$propertyId}",
                    metadata: [
                        'property_id' => $propertyId,
                        'feedback' => $feedback,
                        'rating' => $rating,
                    ]
    )
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Сохранить объект в избранное клиента.
     */
    public function saveProperty(CrmRealEstateProfile $profile, string $propertyId, string $correlationId): CrmRealEstateProfile
    {
        return $this->db->transaction(function () use ($profile, $propertyId, $correlationId): CrmRealEstateProfile {
            $saved = $profile->saved_properties ?? [];

            if (!in_array($propertyId, $saved, true)) {
                $saved[] = $propertyId;
                $profile->update(['saved_properties' => $saved]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить статус ипотеки.
     */
    public function updateMortgageStatus(
        CrmRealEstateProfile $profile,
        bool $approved,
        string $correlationId,
        ?string $bank = null,
        ?float $amount = null
    ): CrmRealEstateProfile {
        return $this->db->transaction(function () use ($profile, $approved, $correlationId, $bank, $amount): CrmRealEstateProfile {
            $data = ['mortgage_approved' => $approved];

            if ($bank !== null) {
                $data['mortgage_bank'] = $bank;
            }

            if ($amount !== null) {
                $data['mortgage_amount'] = $amount;
            }

            $profile->update($data);

            $this->audit->log(
                'crm_realestate_mortgage_updated',
                CrmRealEstateProfile::class,
                $profile->id,
                [],
                $data,
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать завершённую сделку.
     */
    public function recordDeal(
        CrmClient $client,
        string $propertyId,
        string $dealType,
        float $amount,
        string $correlationId
    ): void {
        $this->db->transaction(function () use ($client, $propertyId, $dealType, $amount, $correlationId): void {
            $profile = CrmRealEstateProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmRealEstateProfile) {
                $deals = $profile->deal_history ?? [];
                $deals[] = [
                    'property_id' => $propertyId,
                    'deal_type' => $dealType,
                    'amount' => $amount,
                    'date' => now()->toDateString(),
                ];
                $profile->update(['deal_history' => $deals]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Сделка по недвижимости: {$dealType}, объект {$propertyId}",
                    metadata: [
                        'property_id' => $propertyId,
                        'deal_type' => $dealType,
                        'amount' => $amount,
                    ]
    )
    );

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);
        });
    }

    /**
     * Подобрать объекты под требования клиента (матчинг).
     */
    public function getMatchingCriteria(CrmRealEstateProfile $profile): array
    {
        return [
            'client_role' => $profile->client_role,
            'budget_min' => $profile->budget_min,
            'budget_max' => $profile->budget_max,
            'locations' => $profile->preferred_locations ?? [],
            'requirements' => $profile->property_requirements ?? [],
            'property_type' => $profile->property_type_preference,
            'mortgage_ready' => $profile->mortgage_approved,
        ];
    }

    /**
     * «Спящие» клиенты недвижимости.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 30): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('real_estate')
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
