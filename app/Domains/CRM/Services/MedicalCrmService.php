<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmMedicalProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * MedicalCrmService — CRM-логика для вертикали Медицина/Здоровье.
 *
 * Медицинская карта, анамнез, аллергии, рецепты, вакцинации,
 * страховка, приёмы, лабораторные исследования.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class MedicalCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать medical-профиль CRM-клиента.
     */
    public function createMedicalProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $dateOfBirth = null,
        ?string $bloodType = null,
        array $chronicConditions = [],
        array $allergies = [],
        array $currentMedications = [],
        ?string $insuranceProvider = null,
        ?string $insurancePolicy = null,
        ?string $insuranceExpiresAt = null,
        ?string $emergencyContactName = null,
        ?string $emergencyContactPhone = null,
        ?string $notes = null
    ): CrmMedicalProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_medical_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $dateOfBirth, $bloodType,
            $chronicConditions, $allergies, $currentMedications,
            $insuranceProvider, $insurancePolicy, $insuranceExpiresAt,
            $emergencyContactName, $emergencyContactPhone, $notes
    ): CrmMedicalProfile {
            $profile = CrmMedicalProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'date_of_birth' => $dateOfBirth,
                'blood_type' => $bloodType,
                'chronic_conditions' => $chronicConditions,
                'allergies' => $allergies,
                'current_medications' => $currentMedications,
                'vaccination_history' => [],
                'lab_results_history' => [],
                'appointment_history' => [],
                'prescription_history' => [],
                'insurance_provider' => $insuranceProvider,
                'insurance_policy' => $insurancePolicy,
                'insurance_expires_at' => $insuranceExpiresAt,
                'has_disability' => false,
                'emergency_contact_name' => $emergencyContactName,
                'emergency_contact_phone' => $emergencyContactPhone,
                'notes' => $notes,
            ]);

            $this->logger->info('Medical CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_medical_profile_created',
                CrmMedicalProfile::class,
                $profile->id,
                [],
                ['profile_id' => $profile->id],
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Проверить совместимость лекарств перед назначением.
     */
    public function checkMedicationInteractions(CrmMedicalProfile $profile, string $newMedication): array
    {
        $current = $profile->current_medications ?? [];
        $allergies = $profile->allergies ?? [];
        $warnings = [];

        foreach ($allergies as $allergy) {
            if (mb_stripos($newMedication, $allergy) !== false) {
                $warnings[] = [
                    'type' => 'allergy',
                    'medication' => $newMedication,
                    'allergy' => $allergy,
                    'severity' => 'critical',
                    'message' => "АЛЛЕРГИЯ: пациент имеет аллергию на «{$allergy}», входящий в «{$newMedication}»!",
                ];
            }
        }

        if (count($current) > 0) {
            $warnings[] = [
                'type' => 'interaction_check_needed',
                'current_medications' => $current,
                'new_medication' => $newMedication,
                'severity' => 'medium',
                'message' => 'Рекомендуется проверить взаимодействие с текущими препаратами.',
            ];
        }

        return $warnings;
    }

    /**
     * Записать приём врача.
     */
    public function recordAppointment(
        CrmClient $client,
        string $doctorId,
        string $specialty,
        string $diagnosis,
        string $correlationId,
        ?float $amount = null,
        array $prescriptions = []
    ): void {
        $this->db->transaction(function () use ($client, $doctorId, $specialty, $diagnosis, $correlationId, $amount, $prescriptions): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Приём: {$specialty} — {$diagnosis}",
                    metadata: [
                        'doctor_id' => $doctorId,
                        'specialty' => $specialty,
                        'diagnosis' => $diagnosis,
                        'amount' => $amount,
                        'prescriptions' => $prescriptions,
                    ]
    )
    );

            $profile = CrmMedicalProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmMedicalProfile) {
                $appointments = $profile->appointment_history ?? [];
                $appointments[] = [
                    'doctor_id' => $doctorId,
                    'specialty' => $specialty,
                    'diagnosis' => $diagnosis,
                    'date' => now()->toDateString(),
                ];
                $updateData = ['appointment_history' => $appointments];

                if ($doctorId !== '') {
                    $updateData['preferred_doctor_id'] = $doctorId;
                }

                if (count($prescriptions) > 0) {
                    $history = $profile->prescription_history ?? [];
                    $history[] = [
                        'prescriptions' => $prescriptions,
                        'doctor_id' => $doctorId,
                        'date' => now()->toDateString(),
                    ];
                    $updateData['prescription_history'] = $history;
                }

                $profile->update($updateData);
            }

            if ($amount !== null && $amount > 0) {
                $client->increment('total_orders');
                $client->increment('total_spent', $amount);
            }

            $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Добавить вакцинацию.
     */
    public function recordVaccination(
        CrmMedicalProfile $profile,
        string $vaccineName,
        string $correlationId,
        ?string $nextDoseDate = null
    ): CrmMedicalProfile {
        return $this->db->transaction(function () use ($profile, $vaccineName, $correlationId, $nextDoseDate): CrmMedicalProfile {
            $vaccinations = $profile->vaccination_history ?? [];
            $vaccinations[] = [
                'vaccine' => $vaccineName,
                'date' => now()->toDateString(),
                'next_dose' => $nextDoseDate,
            ];

            $profile->update(['vaccination_history' => $vaccinations]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Vaccination recorded', [
                'profile_id' => $profile->id,
                'vaccine' => $vaccineName,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Добавить результат лабораторного исследования.
     */
    public function addLabResult(
        CrmMedicalProfile $profile,
        string $testName,
        string $result,
        string $correlationId,
        ?string $referenceRange = null
    ): CrmMedicalProfile {
        return $this->db->transaction(function () use ($profile, $testName, $result, $correlationId, $referenceRange): CrmMedicalProfile {
            $labs = $profile->lab_results_history ?? [];
            $labs[] = [
                'test' => $testName,
                'result' => $result,
                'reference_range' => $referenceRange,
                'date' => now()->toDateString(),
            ];

            $profile->update(['lab_results_history' => $labs]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Клиенты с истекающей страховкой.
     */
    public function getExpiringInsurance(int $tenantId, int $daysAhead = 30): Collection
    {
        return CrmMedicalProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('insurance_expires_at')
            ->whereBetween('insurance_expires_at', [now(), now()->addDays($daysAhead)])
            ->with('client')
            ->orderBy('insurance_expires_at')
            ->get();
    }

    /**
     * Клиенты с предстоящей вакцинацией.
     */
    public function getUpcomingVaccinations(int $tenantId, int $daysAhead = 14): Collection
    {
        return CrmMedicalProfile::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->get()
            ->filter(function (CrmMedicalProfile $profile) use ($daysAhead): bool {
                $vaccinations = $profile->vaccination_history ?? [];
                foreach ($vaccinations as $v) {
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
     * «Спящие» мед. клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 90): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('medical')
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
