<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFlowerClientProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager;

/**
 * FlowerCrmService — CRM-логика для вертикали Flowers (Цветочные магазины, Доставка цветов).
 *
 * Любимые цветы, поводы/даты, частые получатели, корпоративные клиенты,
 * предпочтения по упаковке, аллергии на цветы, предложение «Букет месяца».
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class FlowerCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать профиль цветочного клиента.
     */
    public function createFlowerProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        array $favoriteFlowers = [],
        array $dislikedFlowers = [],
        array $flowerAllergies = [],
        array $occasions = [],
        array $frequentRecipients = [],
        ?string $packagingPreference = null,
        ?string $budgetRange = null,
        bool $isCorporate = false,
        ?array $corporateHolidays = null,
        ?string $notes = null
    ): CrmFlowerClientProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_flower_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId,
            $tenantId,
            $correlationId,
            $favoriteFlowers,
            $dislikedFlowers,
            $flowerAllergies,
            $occasions,
            $frequentRecipients,
            $packagingPreference,
            $budgetRange,
            $isCorporate,
            $corporateHolidays,
            $notes
    ): CrmFlowerClientProfile {
            $profile = CrmFlowerClientProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'favorite_flowers' => $favoriteFlowers,
                'disliked_flowers' => $dislikedFlowers,
                'flower_allergies' => $flowerAllergies,
                'occasions' => $occasions,
                'frequent_recipients' => $frequentRecipients,
                'packaging_preference' => $packagingPreference,
                'budget_range' => $budgetRange,
                'is_corporate' => $isCorporate,
                'corporate_holidays' => $corporateHolidays ?? [],
                'notes' => $notes,
            ]);

            $this->logger->info('Flower CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'is_corporate' => $isCorporate,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_flower_profile_created',
                CrmFlowerClientProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать заказ цветов и обновить профиль.
     */
    public function recordFlowerOrder(
        CrmClient $client,
        string $recipientName,
        array $flowers,
        float $amount,
        string $occasion,
        string $correlationId,
        ?string $packaging = null,
        ?string $deliveryAddress = null
    ): void {
        $this->db->transaction(function () use (
            $client,
            $recipientName,
            $flowers,
            $amount,
            $occasion,
            $correlationId,
            $packaging,
            $deliveryAddress
    ): void {
            // Записываем interaction
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'online',
                    direction: 'inbound',
                    content: "Заказ цветов: {$occasion} для {$recipientName}",
                    metadata: [
                        'recipient' => $recipientName,
                        'flowers' => $flowers,
                        'amount' => $amount,
                        'occasion' => $occasion,
                        'packaging' => $packaging,
                        'delivery_address' => $deliveryAddress,
                    ]
    )
    );

            // Обновляем профиль — добавляем получателя в frequent_recipients
            $profile = $client->flowerClientProfile;

            if ($profile instanceof CrmFlowerClientProfile) {
                $this->updateFrequentRecipient($profile, $recipientName, $deliveryAddress);
                $this->updateFavoriteFlowers($profile, $flowers);

                if ($packaging !== null) {
                    $profile->update(['packaging_preference' => $packaging]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
                }
            }

            // Обновляем финансовые данные
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

            $this->logger->info('Flower CRM order recorded', [
                'client_id' => $client->id,
                'recipient' => $recipientName,
                'amount' => $amount,
                'occasion' => $occasion,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Добавить повод/дату (occasion) в профиль клиента.
     */
    public function addOccasion(
        CrmFlowerClientProfile $profile,
        string $name,
        string $date,
        string $recipientName,
        string $correlationId,
        ?string $recipientRelation = null,
        ?string $preferredBouquetType = null
    ): CrmFlowerClientProfile {
        return $this->db->transaction(function () use (
            $profile,
            $name,
            $date,
            $recipientName,
            $correlationId,
            $recipientRelation,
            $preferredBouquetType
    ): CrmFlowerClientProfile {
            $occasions = $profile->occasions ?? [];
            $occasions[] = [
                'name' => $name,
                'date' => $date,
                'recipient' => $recipientName,
                'relation' => $recipientRelation,
                'bouquet_type' => $preferredBouquetType,
                'created_at' => now()->toIso8601String(),
            ];

            $profile->update(['occasions' => $occasions]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Flower CRM occasion added', [
                'profile_id' => $profile->id,
                'occasion' => $name,
                'date' => $date,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Получить ближайшие поводы для всех клиентов (напоминания флористам).
     */
    public function getUpcomingOccasions(int $tenantId, int $daysAhead = 7): Collection
    {
        $clients = CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('flowers')
            ->with('flowerClientProfile')
            ->get();

        $upcoming = collect();

        foreach ($clients as $client) {
            $profile = $client->flowerClientProfile;

            if (! $profile instanceof CrmFlowerClientProfile) {
                continue;
            }

            foreach ($profile->occasions ?? [] as $occasion) {
                $occasionDate = $occasion['date'] ?? null;

                if ($occasionDate === null) {
                    continue;
                }

                $parsed = Carbon::createFromFormat('m-d', $occasionDate);

                if ($parsed === false) {
                    continue;
                }

                $thisYear = $parsed->copy()->year(now()->year);

                if ($thisYear->isPast()) {
                    $thisYear->addYear();
                }

                $daysUntil = now()->diffInDays($thisYear, false);

                if ($daysUntil >= 0 && $daysUntil <= $daysAhead) {
                    $upcoming->push([
                        'client' => $client,
                        'occasion' => $occasion,
                        'days_until' => $daysUntil,
                        'date' => $thisYear->toDateString(),
                    ]);
                }
            }
        }

        return $upcoming->sortBy('days_until');
    }

    /**
     * Получить корпоративных клиентов с ближайшими корпоративными праздниками.
     */
    public function getUpcomingCorporateHolidays(int $tenantId, int $daysAhead = 14): Collection
    {
        $clients = CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('flowers')
            ->where('client_type', 'corporate')
            ->with('flowerClientProfile')
            ->get();

        $upcoming = collect();

        foreach ($clients as $client) {
            $profile = $client->flowerClientProfile;

            if (! $profile instanceof CrmFlowerClientProfile) {
                continue;
            }

            foreach ($profile->corporate_holidays ?? [] as $holiday) {
                $holidayDate = $holiday['date'] ?? null;

                if ($holidayDate === null) {
                    continue;
                }

                $parsed = Carbon::parse($holidayDate);
                $daysUntil = now()->diffInDays($parsed, false);

                if ($daysUntil >= 0 && $daysUntil <= $daysAhead) {
                    $upcoming->push([
                        'client' => $client,
                        'holiday' => $holiday,
                        'days_until' => $daysUntil,
                        'date' => $parsed->toDateString(),
                    ]);
                }
            }
        }

        return $upcoming->sortBy('days_until');
    }

    /**
     * Получить клиентов-кандидатов для «Букет месяца» (постоянные клиенты с 5+ заказами).
     */
    public function getBouquetOfMonthCandidates(int $tenantId): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('flowers')
            ->where('total_orders', '>=', 5)
            ->where('status', 'active')
            ->with('flowerClientProfile')
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Проверить аллергии получателя перед составлением букета.
     */
    public function checkFlowerAllergies(CrmFlowerClientProfile $profile, array $bouquetFlowers): array
    {
        $allergies = $profile->flower_allergies ?? [];
        $disliked = $profile->disliked_flowers ?? [];
        $warnings = [];

        foreach ($bouquetFlowers as $flower) {
            foreach ($allergies as $allergy) {
                if (mb_stripos($flower, $allergy) !== false) {
                    $warnings[] = [
                        'type' => 'allergy',
                        'flower' => $flower,
                        'allergy' => $allergy,
                        'severity' => 'critical',
                        'message' => "АЛЛЕРГИЯ! Клиент/получатель имеет аллергию на «{$allergy}» — цветок «{$flower}» ЗАПРЕЩЁН!",
                    ];
                }
            }

            foreach ($disliked as $dislikedFlower) {
                if (mb_stripos($flower, $dislikedFlower) !== false) {
                    $warnings[] = [
                        'type' => 'disliked',
                        'flower' => $flower,
                        'disliked' => $dislikedFlower,
                        'severity' => 'warning',
                        'message' => "Клиент не любит «{$dislikedFlower}» — рекомендуем заменить «{$flower}».",
                    ];
                }
            }
        }

        return $warnings;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function updateFrequentRecipient(CrmFlowerClientProfile $profile, string $name, ?string $address): void
    {
        $recipients = $profile->frequent_recipients ?? [];
        $found = false;

        foreach ($recipients as &$recipient) {
            if (mb_strtolower($recipient['name'] ?? '') === mb_strtolower($name)) {
                $recipient['orders_count'] = ($recipient['orders_count'] ?? 0) + 1;
                $recipient['last_order_at'] = now()->toIso8601String();

                if ($address !== null) {
                    $recipient['address'] = $address;
                }

                $found = true;
                break;
            }
        }
        unset($recipient);

        if (! $found) {
            $recipients[] = [
                'name' => $name,
                'address' => $address,
                'orders_count' => 1,
                'last_order_at' => now()->toIso8601String(),
            ];
        }

        $profile->update(['frequent_recipients' => $recipients]);
    }

    private function updateFavoriteFlowers(CrmFlowerClientProfile $profile, array $flowers): void
    {
        $favorites = $profile->favorite_flowers ?? [];

        foreach ($flowers as $flower) {
            if (! in_array($flower, $favorites, true)) {
                $favorites[] = $flower;
            }
        }

        $profile->update(['favorite_flowers' => array_values(array_unique($favorites))]);
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
