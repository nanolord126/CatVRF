<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\Models\CrmBeautyProfile;
use App\Domains\CRM\Models\CrmClient;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * BeautyCrmService — CRM-логика для вертикали Beauty (Салоны, SPA, Барбершопы).
 *
 * Управление медицинскими картами, аллергиями, противопоказаниями,
 * фото до/после, предпочтениями мастеров, отслеживанием визитов.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class BeautyCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать beauty-профиль для CRM-клиента (медицинская карта).
     */
    public function createBeautyProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $skinType = null,
        ?string $hairType = null,
        array $allergies = [],
        array $contraindications = [],
        array $preferredMasters = [],
        ?string $notes = null
    ): CrmBeautyProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_beauty_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId,
            $tenantId,
            $correlationId,
            $skinType,
            $hairType,
            $allergies,
            $contraindications,
            $preferredMasters,
            $notes
    ): CrmBeautyProfile {
            $profile = CrmBeautyProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'skin_type' => $skinType,
                'hair_type' => $hairType,
                'allergies' => $allergies,
                'contraindications' => $contraindications,
                'preferred_masters' => $preferredMasters,
                'before_after_photos' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Beauty CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_beauty_profile_created',
                CrmBeautyProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Обновить медицинскую карту (аллергии, противопоказания, тип кожи/волос).
     */
    public function updateMedicalCard(
        CrmBeautyProfile $profile,
        array $data,
        string $correlationId
    ): CrmBeautyProfile {
        return $this->db->transaction(function () use ($profile, $data, $correlationId): CrmBeautyProfile {
            $oldValues = $profile->toArray();

            $profile->update($data);

            $this->logger->info('Beauty CRM medical card updated', [
                'profile_id' => $profile->id,
                'client_id' => $profile->crm_client_id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_beauty_medical_card_updated',
                CrmBeautyProfile::class,
                $profile->id,
                $oldValues,
                $data,
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Добавить фото «до/после» к профилю.
     */
    public function addBeforeAfterPhoto(
        CrmBeautyProfile $profile,
        string $beforeUrl,
        string $afterUrl,
        string $procedure,
        string $correlationId,
        ?string $masterId = null,
        ?string $comment = null
    ): CrmBeautyProfile {
        return $this->db->transaction(function () use (
            $profile,
            $beforeUrl,
            $afterUrl,
            $procedure,
            $correlationId,
            $masterId,
            $comment
    ): CrmBeautyProfile {
            $photos = $profile->before_after_photos ?? [];
            $photos[] = [
                'before' => $beforeUrl,
                'after' => $afterUrl,
                'procedure' => $procedure,
                'master_id' => $masterId,
                'comment' => $comment,
                'date' => now()->toDateString(),
            ];

            $profile->update(['before_after_photos' => $photos]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Beauty CRM before/after photo added', [
                'profile_id' => $profile->id,
                'procedure' => $procedure,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Проверить наличие аллергии у клиента перед процедурой.
     */
    public function checkAllergies(CrmBeautyProfile $profile, array $ingredients): array
    {
        $allergies = $profile->allergies ?? [];
        $warnings = [];

        foreach ($ingredients as $ingredient) {
            foreach ($allergies as $allergy) {
                if (mb_stripos($ingredient, $allergy) !== false) {
                    $warnings[] = [
                        'ingredient' => $ingredient,
                        'allergy' => $allergy,
                        'severity' => 'high',
                        'message' => "Клиент имеет аллергию на «{$allergy}» — компонент «{$ingredient}» ЗАПРЕЩЁН!",
                    ];
                }
            }
        }

        foreach ($profile->contraindications ?? [] as $contra) {
            $warnings[] = [
                'type' => 'contraindication',
                'description' => $contra,
                'severity' => 'medium',
            ];
        }

        return $warnings;
    }

    /**
     * Записать визит к мастеру и обновить историю.
     */
    public function recordVisit(
        CrmClient $client,
        string $masterId,
        string $service,
        float $amount,
        string $correlationId,
        ?string $masterFeedback = null
    ): void {
        $this->db->transaction(function () use (
            $client,
            $masterId,
            $service,
            $amount,
            $correlationId,
            $masterFeedback
    ): void {
            // Записываем как interaction
            $this->crmService->recordInteraction(
                new \App\Domains\CRM\DTOs\CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Визит: {$service}",
                    metadata: [
                        'master_id' => $masterId,
                        'service' => $service,
                        'amount' => $amount,
                        'feedback' => $masterFeedback,
                    ]
    )
    );

            // Обновляем preferred_masters в beauty profile
            $profile = $client->beautyProfile;

            if ($profile instanceof CrmBeautyProfile) {
                $masters = $profile->preferred_masters ?? [];

                if (! in_array($masterId, $masters, true)) {
                    $masters[] = $masterId;
                    $profile->update(['preferred_masters' => $masters]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
                }
            }

            // Обновляем финансовые данные клиента
            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update([
                'last_order_at' => now(),
                'average_order_value' => $client->total_orders > 0
                    ? round(($client->total_spent) / $client->total_orders, 2)
                    : $amount,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получить клиентов с днём рождения в ближайшие N дней.
     */
    public function getUpcomingBirthdays(int $tenantId, int $daysAhead = 7): \Illuminate\Support\Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('beauty')
            ->whereNotNull('birthday')
            ->get()
            ->filter(function (CrmClient $client) use ($daysAhead): bool {
                $birthday = $client->birthday;
                $thisYear = now()->year;
                $bday = $birthday->copy()->year($thisYear);

                if ($bday->isPast()) {
                    $bday->addYear();
                }

                return $bday->diffInDays(now()) <= $daysAhead;
            });
    }

    /**
     * Получить "спящих" Beauty-клиентов для реактивации.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 60): \Illuminate\Support\Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('beauty')
            ->sleeping($daysInactive)
            ->with('beautyProfile')
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
