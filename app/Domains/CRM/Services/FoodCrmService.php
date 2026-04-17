<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFoodProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * FoodCrmService — CRM-логика для вертикали Еда/Рестораны.
 *
 * Управление диетическими ограничениями, аллергенами, КБЖУ-трекинг,
 * избранные блюда, корпоративное питание, оптимизация меню.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class FoodCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать food-профиль CRM-клиента.
     */
    public function createFoodProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        array $dietaryRestrictions = [],
        array $allergies = [],
        array $favoriteCuisines = [],
        ?string $mealPlanType = null,
        ?int $dailyCalorieTarget = null,
        bool $isCorporateClient = false,
        ?int $corporateHeadcount = null,
        ?string $notes = null
    ): CrmFoodProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_food_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $dietaryRestrictions,
            $allergies, $favoriteCuisines, $mealPlanType, $dailyCalorieTarget,
            $isCorporateClient, $corporateHeadcount, $notes
    ): CrmFoodProfile {
            $profile = CrmFoodProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'dietary_restrictions' => $dietaryRestrictions,
                'allergies' => $allergies,
                'favorite_cuisines' => $favoriteCuisines,
                'favorite_dishes' => [],
                'disliked_ingredients' => [],
                'macros_target' => [],
                'meal_plan_type' => $mealPlanType,
                'daily_calorie_target' => $dailyCalorieTarget,
                'is_corporate_client' => $isCorporateClient,
                'corporate_headcount' => $corporateHeadcount,
                'corporate_schedule' => [],
                'delivery_time_preferences' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Food CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'is_corporate' => $isCorporateClient,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_food_profile_created',
                CrmFoodProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Проверить аллергены блюда для клиента.
     */
    public function checkAllergens(CrmFoodProfile $profile, array $dishIngredients): array
    {
        $clientAllergies = $profile->allergies ?? [];
        $restrictions = $profile->dietary_restrictions ?? [];
        $disliked = $profile->disliked_ingredients ?? [];
        $warnings = [];

        foreach ($dishIngredients as $ingredient) {
            foreach ($clientAllergies as $allergy) {
                if (mb_stripos($ingredient, $allergy) !== false) {
                    $warnings[] = [
                        'type' => 'allergy',
                        'ingredient' => $ingredient,
                        'match' => $allergy,
                        'severity' => 'critical',
                        'message' => "АЛЛЕРГИЯ: «{$allergy}» обнаружен в ингредиенте «{$ingredient}»!",
                    ];
                }
            }

            foreach ($disliked as $dislikedItem) {
                if (mb_stripos($ingredient, $dislikedItem) !== false) {
                    $warnings[] = [
                        'type' => 'disliked',
                        'ingredient' => $ingredient,
                        'match' => $dislikedItem,
                        'severity' => 'low',
                        'message' => "Клиент не любит «{$dislikedItem}».",
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Записать заказ еды и обновить профиль.
     */
    public function recordFoodOrder(
        CrmClient $client,
        array $dishes,
        float $amount,
        string $correlationId,
        ?string $channel = 'delivery',
        ?int $rating = null
    ): void {
        $this->db->transaction(function () use ($client, $dishes, $amount, $correlationId, $channel, $rating): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: $channel ?? 'delivery',
                    direction: 'inbound',
                    content: 'Заказ еды: ' . implode(', ', array_column($dishes, 'name')),
                    metadata: [
                        'dishes' => $dishes,
                        'amount' => $amount,
                        'rating' => $rating,
                    ]
    )
    );

            $profile = CrmFoodProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmFoodProfile) {
                $favorites = $profile->favorite_dishes ?? [];
                foreach ($dishes as $dish) {
                    $found = false;
                    foreach ($favorites as &$fav) {
                        if (($fav['name'] ?? '') === ($dish['name'] ?? '')) {
                            $fav['order_count'] = ($fav['order_count'] ?? 0) + 1;
                            $fav['last_ordered'] = now()->toDateString();
                            $found = true;
                            break;
                        }
                    }
                    unset($fav);

                    if (!$found) {
                        $favorites[] = [
                            'name' => $dish['name'] ?? '',
                            'order_count' => 1,
                            'last_ordered' => now()->toDateString(),
                        ];
                    }
                }

                $profile->update(['favorite_dishes' => $favorites]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Food order recorded', [
                'client_id' => $client->id,
                'amount' => $amount,
                'dishes_count' => count($dishes),
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Обновить корпоративное расписание питания.
     */
    public function updateCorporateSchedule(
        CrmFoodProfile $profile,
        array $schedule,
        int $headcount,
        string $correlationId
    ): CrmFoodProfile {
        return $this->db->transaction(function () use ($profile, $schedule, $headcount, $correlationId): CrmFoodProfile {
            $profile->update([
                'corporate_schedule' => $schedule,
                'corporate_headcount' => $headcount,
                'is_corporate_client' => true,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_food_corporate_schedule_updated',
                CrmFoodProfile::class,
                $profile->id,
                [],
                ['schedule' => $schedule, 'headcount' => $headcount],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Получить клиентов-аллергиков для предупреждений при изменении меню.
     */
    public function getClientsWithAllergy(int $tenantId, string $allergen): Collection
    {
        return CrmFoodProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereJsonContains('allergies', $allergen)
            ->with('client')
            ->get();
    }

    /**
     * «Спящие» food-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 30): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('food')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Рекомендации персональных блюд на основе профиля.
     */
    public function getPersonalizedMenuRecommendations(CrmFoodProfile $profile): array
    {
        $recommendations = [];
        $favorites = $profile->favorite_dishes ?? [];
        $cuisines = $profile->favorite_cuisines ?? [];
        $restrictions = $profile->dietary_restrictions ?? [];

        foreach ($favorites as $fav) {
            if (($fav['order_count'] ?? 0) >= 3) {
                $recommendations[] = [
                    'type' => 'repeat_favorite',
                    'dish' => $fav['name'],
                    'reason' => "Заказывалось {$fav['order_count']} раз",
                ];
            }
        }

        if (in_array('vegan', $restrictions, true) || in_array('vegetarian', $restrictions, true)) {
            $recommendations[] = [
                'type' => 'dietary_highlight',
                'label' => 'Показать растительное меню',
                'restriction' => implode(', ', $restrictions),
            ];
        }

        return $recommendations;
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
