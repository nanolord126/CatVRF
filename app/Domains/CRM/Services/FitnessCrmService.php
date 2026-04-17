<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFitnessProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * FitnessCrmService — CRM-логика для вертикали Фитнес/Спорт.
 *
 * Антропометрия, планы тренировок, абонементы, прогресс,
 * цели, здоровье, тренеры, пищевые добавки.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class FitnessCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать fitness-профиль CRM-клиента.
     */
    public function createFitnessProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?float $heightCm = null,
        ?float $weightKg = null,
        ?float $targetWeightKg = null,
        ?string $fitnessGoal = null,
        ?string $fitnessLevel = null,
        ?string $membershipType = null,
        ?string $membershipExpiresAt = null,
        array $healthConditions = [],
        array $preferredActivities = [],
        ?string $notes = null
    ): CrmFitnessProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_fitness_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $heightCm, $weightKg,
            $targetWeightKg, $fitnessGoal, $fitnessLevel, $membershipType,
            $membershipExpiresAt, $healthConditions, $preferredActivities, $notes
    ): CrmFitnessProfile {
            $profile = CrmFitnessProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'height_cm' => $heightCm,
                'weight_kg' => $weightKg,
                'target_weight_kg' => $targetWeightKg,
                'fitness_goal' => $fitnessGoal,
                'fitness_level' => $fitnessLevel,
                'membership_type' => $membershipType,
                'membership_expires_at' => $membershipExpiresAt,
                'health_conditions' => $healthConditions,
                'preferred_activities' => $preferredActivities,
                'disliked_activities' => [],
                'training_schedule' => [],
                'body_measurements' => [],
                'progress_photos' => [],
                'supplements_used' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Fitness CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'goal' => $fitnessGoal,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_fitness_profile_created',
                CrmFitnessProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Зафиксировать замеры тела (прогресс).
     */
    public function recordBodyMeasurements(
        CrmFitnessProfile $profile,
        float $weightKg,
        string $correlationId,
        ?float $bodyFatPct = null,
        array $measurements = []
    ): CrmFitnessProfile {
        return $this->db->transaction(function () use ($profile, $weightKg, $correlationId, $bodyFatPct, $measurements): CrmFitnessProfile {
            $history = $profile->body_measurements ?? [];
            $history[] = [
                'weight_kg' => $weightKg,
                'body_fat_pct' => $bodyFatPct,
                'measurements' => $measurements,
                'date' => now()->toDateString(),
            ];

            $updateData = [
                'weight_kg' => $weightKg,
                'body_measurements' => $history,
            ];

            if ($bodyFatPct !== null) {
                $updateData['body_fat_pct'] = $bodyFatPct;
            }

            $profile->update($updateData);

            $this->logger->info('Fitness measurements recorded', [
                'profile_id' => $profile->id,
                'weight_kg' => $weightKg,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать визит в зал.
     */
    public function recordGymVisit(
        CrmClient $client,
        string $correlationId,
        ?string $activityType = null,
        ?int $durationMinutes = null,
        ?string $trainerId = null
    ): void {
        $this->db->transaction(function () use ($client, $correlationId, $activityType, $durationMinutes, $trainerId): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: 'Посещение зала' . ($activityType !== null ? ": {$activityType}" : ''),
                    metadata: [
                        'activity_type' => $activityType,
                        'duration_minutes' => $durationMinutes,
                        'trainer_id' => $trainerId,
                    ]
    )
    );

            $profile = CrmFitnessProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmFitnessProfile) {
                $profile->increment('visits_per_week');

                if ($trainerId !== null) {
                    $profile->update(['preferred_trainer_id' => $trainerId]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
                }
            }
        });
    }

    /**
     * Обновить абонемент.
     */
    public function updateMembership(
        CrmFitnessProfile $profile,
        string $membershipType,
        string $expiresAt,
        string $correlationId
    ): CrmFitnessProfile {
        return $this->db->transaction(function () use ($profile, $membershipType, $expiresAt, $correlationId): CrmFitnessProfile {
            $profile->update([
                'membership_type' => $membershipType,
                'membership_expires_at' => $expiresAt,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_fitness_membership_updated',
                CrmFitnessProfile::class,
                $profile->id,
                [],
                ['membership_type' => $membershipType, 'expires_at' => $expiresAt],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Оценить прогресс к цели.
     */
    public function evaluateProgress(CrmFitnessProfile $profile): array
    {
        $current = $profile->weight_kg;
        $target = $profile->target_weight_kg;
        $measurements = $profile->body_measurements ?? [];

        if ($current === null || $target === null || count($measurements) < 2) {
            return ['status' => 'insufficient_data', 'message' => 'Недостаточно данных для оценки'];
        }

        $firstWeight = $measurements[0]['weight_kg'] ?? $current;
        $totalChange = abs($firstWeight - $current);
        $targetChange = abs($firstWeight - $target);
        $progressPct = $targetChange > 0 ? round(($totalChange / $targetChange) * 100, 1) : 0.0;

        $trend = $current < ($measurements[count($measurements) - 2]['weight_kg'] ?? $current)
            ? 'decreasing'
            : 'increasing';

        return [
            'status' => 'ok',
            'goal' => $profile->fitness_goal,
            'start_weight' => $firstWeight,
            'current_weight' => $current,
            'target_weight' => $target,
            'progress_pct' => min($progressPct, 100.0),
            'trend' => $trend,
            'measurements_count' => count($measurements),
        ];
    }

    /**
     * Клиенты с истекающим абонементом.
     */
    public function getExpiringMemberships(int $tenantId, int $daysAhead = 14): Collection
    {
        return CrmFitnessProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('membership_expires_at')
            ->whereBetween('membership_expires_at', [now(), now()->addDays($daysAhead)])
            ->with('client')
            ->orderBy('membership_expires_at')
            ->get();
    }

    /**
     * «Спящие» фитнес-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 14): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('fitness')
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
        return $this->db->transaction($callback);
    }
}
