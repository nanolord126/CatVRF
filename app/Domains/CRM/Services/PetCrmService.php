<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmPetProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * PetCrmService — CRM-логика для вертикали Питомцы.
 *
 * Мульти-профиль питомцев, вакцинации, ветеринарная карта,
 * груминг, корм, выгул, передержка.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class PetCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать pet-профиль CRM-клиента.
     */
    public function createPetProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        array $pets = [],
        array $preferredBrands = [],
        bool $needsPetSitting = false,
        bool $needsDogWalking = false,
        ?float $monthlyPetBudget = null,
        ?string $notes = null
    ): CrmPetProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_pet_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $pets,
            $preferredBrands, $needsPetSitting, $needsDogWalking,
            $monthlyPetBudget, $notes
    ): CrmPetProfile {
            $profile = CrmPetProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'pets' => $pets,
                'vaccination_schedule' => [],
                'medical_conditions' => [],
                'dietary_needs' => [],
                'preferred_brands' => $preferredBrands,
                'grooming_schedule' => [],
                'vet_visit_history' => [],
                'insurance_info' => [],
                'needs_pet_sitting' => $needsPetSitting,
                'needs_dog_walking' => $needsDogWalking,
                'monthly_pet_budget' => $monthlyPetBudget,
                'notes' => $notes,
            ]);

            $this->logger->info('Pet CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'pets_count' => count($pets),
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_pet_profile_created',
                CrmPetProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Добавить питомца.
     */
    public function addPet(
        CrmPetProfile $profile,
        string $name,
        string $species,
        string $correlationId,
        ?string $breed = null,
        ?int $ageYears = null,
        ?float $weightKg = null,
        ?string $gender = null,
        bool $sterilized = false
    ): CrmPetProfile {
        return $this->db->transaction(function () use (
            $profile, $name, $species, $correlationId,
            $breed, $ageYears, $weightKg, $gender, $sterilized
    ): CrmPetProfile {
            $pets = $profile->pets ?? [];
            $pets[] = [
                'name' => $name,
                'species' => $species,
                'breed' => $breed,
                'age_years' => $ageYears,
                'weight_kg' => $weightKg,
                'gender' => $gender,
                'sterilized' => $sterilized,
                'added_at' => now()->toDateString(),
            ];

            $profile->update(['pets' => $pets]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pet added to profile', [
                'profile_id' => $profile->id,
                'pet_name' => $name,
                'species' => $species,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать вакцинацию питомца.
     */
    public function recordVaccination(
        CrmPetProfile $profile,
        string $petName,
        string $vaccineName,
        string $correlationId,
        ?string $nextDoseDate = null
    ): CrmPetProfile {
        return $this->db->transaction(function () use ($profile, $petName, $vaccineName, $correlationId, $nextDoseDate): CrmPetProfile {
            $schedule = $profile->vaccination_schedule ?? [];
            $schedule[] = [
                'pet_name' => $petName,
                'vaccine' => $vaccineName,
                'date' => now()->toDateString(),
                'next_dose' => $nextDoseDate,
            ];

            $profile->update(['vaccination_schedule' => $schedule]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pet vaccination recorded', [
                'profile_id' => $profile->id,
                'pet' => $petName,
                'vaccine' => $vaccineName,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать визит к ветеринару.
     */
    public function recordVetVisit(
        CrmClient $client,
        string $petName,
        string $reason,
        float $amount,
        string $correlationId,
        ?string $diagnosis = null,
        ?string $vetId = null
    ): void {
        $this->db->transaction(function () use ($client, $petName, $reason, $amount, $correlationId, $diagnosis, $vetId): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Вет.визит: {$petName} — {$reason}",
                    metadata: [
                        'pet_name' => $petName,
                        'reason' => $reason,
                        'diagnosis' => $diagnosis,
                        'amount' => $amount,
                        'vet_id' => $vetId,
                    ]
    )
    );

            $profile = CrmPetProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmPetProfile) {
                $history = $profile->vet_visit_history ?? [];
                $history[] = [
                    'pet_name' => $petName,
                    'reason' => $reason,
                    'diagnosis' => $diagnosis,
                    'amount' => $amount,
                    'vet_id' => $vetId,
                    'date' => now()->toDateString(),
                ];

                $updateData = ['vet_visit_history' => $history];

                if ($vetId !== null) {
                    $updateData['preferred_vet_id'] = $vetId;
                }

                $profile->update($updateData);
            }

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Записать сеанс груминга.
     */
    public function recordGrooming(
        CrmPetProfile $profile,
        string $petName,
        string $serviceType,
        string $correlationId,
        ?string $nextGroomingDate = null
    ): CrmPetProfile {
        return $this->db->transaction(function () use ($profile, $petName, $serviceType, $correlationId, $nextGroomingDate): CrmPetProfile {
            $schedule = $profile->grooming_schedule ?? [];
            $schedule[] = [
                'pet_name' => $petName,
                'service' => $serviceType,
                'date' => now()->toDateString(),
                'next_grooming' => $nextGroomingDate,
            ];

            $profile->update(['grooming_schedule' => $schedule]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Питомцы с предстоящими вакцинациями.
     */
    public function getUpcomingVaccinations(int $tenantId, int $daysAhead = 14): Collection
    {
        return CrmPetProfile::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->get()
            ->filter(function (CrmPetProfile $profile) use ($daysAhead): bool {
                foreach ($profile->vaccination_schedule ?? [] as $v) {
                    if (isset($v['next_dose']) && $v['next_dose'] !== null) {
                        $nextDose = \Carbon\Carbon::parse($v['next_dose']);
                        if ($nextDose->isBetween(now(), now()->addDays($daysAhead))) {
                            return true;
                        }
                    }
                }
                return false;
            });
    }

    /**
     * «Спящие» pet-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 60): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('pet')
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
