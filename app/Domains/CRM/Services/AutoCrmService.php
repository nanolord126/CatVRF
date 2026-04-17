<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmAutoProfile;
use App\Domains\CRM\Models\CrmClient;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * AutoCrmService — CRM-логика для вертикали Авто (СТО, запчасти, тюнинг).
 *
 * VIN-трекинг, плановое ТО, страховки, дефектовка, история ремонтов,
 * напоминания о замене масла/шин, мониторинг пробега.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class AutoCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать авто-профиль CRM-клиента.
     */
    public function createAutoProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $vin = null,
        ?string $carBrand = null,
        ?string $carModel = null,
        ?int $carYear = null,
        ?string $carColor = null,
        ?int $mileageKm = null,
        ?string $engineType = null,
        ?string $transmission = null,
        ?string $driversLicenseCategory = null,
        bool $hasGarage = false,
        ?string $notes = null
    ): CrmAutoProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_auto_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $vin, $carBrand,
            $carModel, $carYear, $carColor, $mileageKm, $engineType,
            $transmission, $driversLicenseCategory, $hasGarage, $notes
    ): CrmAutoProfile {
            $profile = CrmAutoProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'vin' => $vin,
                'car_brand' => $carBrand,
                'car_model' => $carModel,
                'car_year' => $carYear,
                'car_color' => $carColor,
                'mileage_km' => $mileageKm,
                'engine_type' => $engineType,
                'transmission' => $transmission,
                'drivers_license_category' => $driversLicenseCategory,
                'has_garage' => $hasGarage,
                'service_history' => [],
                'preferred_parts_brands' => [],
                'car_preferences' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Auto CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'vin' => $vin,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_auto_profile_created',
                CrmAutoProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать сервисное обслуживание (ТО, ремонт, замена масла и т.д.).
     */
    public function recordServiceVisit(
        CrmAutoProfile $profile,
        string $serviceType,
        float $amount,
        string $correlationId,
        ?int $newMileageKm = null,
        ?string $description = null,
        array $partsUsed = [],
        ?string $mechanicId = null
    ): CrmAutoProfile {
        return $this->db->transaction(function () use (
            $profile, $serviceType, $amount, $correlationId,
            $newMileageKm, $description, $partsUsed, $mechanicId
    ): CrmAutoProfile {
            $history = $profile->service_history ?? [];
            $history[] = [
                'type' => $serviceType,
                'amount' => $amount,
                'mileage_km' => $newMileageKm ?? $profile->mileage_km,
                'description' => $description,
                'parts_used' => $partsUsed,
                'mechanic_id' => $mechanicId,
                'date' => now()->toDateString(),
            ];

            $updateData = ['service_history' => $history];

            if ($newMileageKm !== null) {
                $updateData['mileage_km'] = $newMileageKm;
            }

            $profile->update($updateData);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "СТО-визит: {$serviceType}",
                    metadata: [
                        'service_type' => $serviceType,
                        'amount' => $amount,
                        'mileage_km' => $newMileageKm,
                        'parts_used' => $partsUsed,
                    ]
    )
    );

            $client = $profile->client;

            if ($client instanceof CrmClient) {
                $client->increment('total_orders');
                $client->increment('total_spent', $amount);
                $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            $this->logger->info('Auto service visit recorded', [
                'profile_id' => $profile->id,
                'service_type' => $serviceType,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить пробег автомобиля.
     */
    public function updateMileage(CrmAutoProfile $profile, int $mileageKm, string $correlationId): CrmAutoProfile
    {
        return $this->db->transaction(function () use ($profile, $mileageKm, $correlationId): CrmAutoProfile {
            $old = $profile->mileage_km;

            $profile->update(['mileage_km' => $mileageKm]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Auto mileage updated', [
                'profile_id' => $profile->id,
                'old_mileage' => $old,
                'new_mileage' => $mileageKm,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Установить дату следующего планового ТО.
     */
    public function scheduleNextService(CrmAutoProfile $profile, string $date, string $correlationId): CrmAutoProfile
    {
        return $this->db->transaction(function () use ($profile, $date, $correlationId): CrmAutoProfile {
            $profile->update(['next_service_at' => $date]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_auto_next_service_scheduled',
                CrmAutoProfile::class,
                $profile->id,
                [],
                ['next_service_at' => $date],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить дату окончания страховки.
     */
    public function updateInsurance(CrmAutoProfile $profile, string $expiresAt, string $correlationId): CrmAutoProfile
    {
        return $this->db->transaction(function () use ($profile, $expiresAt, $correlationId): CrmAutoProfile {
            $profile->update(['insurance_expires_at' => $expiresAt]);

            $this->logger->info('Auto insurance updated', [
                'profile_id' => $profile->id,
                'insurance_expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Клиенты с истекающей страховкой (в ближайшие N дней).
     */
    public function getExpiringInsurance(int $tenantId, int $daysAhead = 30): Collection
    {
        return CrmAutoProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('insurance_expires_at')
            ->whereBetween('insurance_expires_at', [now(), now()->addDays($daysAhead)])
            ->with('client')
            ->orderBy('insurance_expires_at')
            ->get();
    }

    /**
     * Клиенты с предстоящим плановым ТО.
     */
    public function getUpcomingServiceDue(int $tenantId, int $daysAhead = 14): Collection
    {
        return CrmAutoProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('next_service_at')
            ->whereBetween('next_service_at', [now(), now()->addDays($daysAhead)])
            ->with('client')
            ->orderBy('next_service_at')
            ->get();
    }

    /**
     * Рекомендации ТО на основе пробега (каждые 10 000 км).
     */
    public function getMileageBasedRecommendations(CrmAutoProfile $profile): array
    {
        $mileage = $profile->mileage_km ?? 0;
        $recommendations = [];

        if ($mileage >= 10000 && $mileage % 10000 < 1000) {
            $recommendations[] = ['type' => 'oil_change', 'priority' => 'high', 'label' => 'Замена масла'];
        }

        if ($mileage >= 30000 && $mileage % 30000 < 2000) {
            $recommendations[] = ['type' => 'brake_check', 'priority' => 'high', 'label' => 'Проверка тормозов'];
        }

        if ($mileage >= 60000 && $mileage % 60000 < 3000) {
            $recommendations[] = ['type' => 'timing_belt', 'priority' => 'critical', 'label' => 'Замена ремня ГРМ'];
        }

        if ($mileage >= 40000 && $mileage % 40000 < 2000) {
            $recommendations[] = ['type' => 'air_filter', 'priority' => 'medium', 'label' => 'Замена воздушного фильтра'];
            $recommendations[] = ['type' => 'spark_plugs', 'priority' => 'medium', 'label' => 'Замена свечей зажигания'];
        }

        $now = now();

        if ($now->month >= 10 && $now->month <= 11) {
            $recommendations[] = ['type' => 'winter_tires', 'priority' => 'high', 'label' => 'Сезонная замена шин (зима)'];
        }

        if ($now->month >= 3 && $now->month <= 4) {
            $recommendations[] = ['type' => 'summer_tires', 'priority' => 'high', 'label' => 'Сезонная замена шин (лето)'];
        }

        return $recommendations;
    }

    /**
     * «Спящие» авто-клиенты без визитов > N дней.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 90): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('auto')
            ->sleeping($daysInactive)
            ->with('autoProfile')
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
        return $this->db->transaction($callback);
    }
}
