<?php declare(strict_types=1);

namespace App\Domains\Food\HealthyFood\Services;

use App\Domains\Food\HealthyFood\Models\DietPlan;
use App\Domains\Food\HealthyFood\Models\HealthyMeal;
use App\Domains\Food\HealthyFood\Models\DietSubscription;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\WalletService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис здорового питания и диетических рационов - КАНОН 2026.
 * Персонализация меню, подписки, расчет КБЖУ и 14% комиссия.
 */
final class DietService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly WalletService $wallet,
        private readonly RecommendationService $recommender,
    ) {}

    /**
     * Создание плана питания для пользователя.
     */
    public function createPersonalPlan(int $userId, array $goals, string $correlationId = ""): DietPlan
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($userId, $goals, $correlationId) {
            // 1. Интеграция с рекомендательной системой для подбора оптимальных блюд
            $recommendedItems = $this->recommender->getForUser(userId: $userId, vertical: "healthy_food");

            $plan = DietPlan::create([
                "uuid" => (string) Str::uuid(),
                "user_id" => $userId,
                "goals" => $goals,
                "status" => "active",
                "correlation_id" => $correlationId,
                "tags" => ["keto", "high_protein", "personal"]
            ]);

            Log::channel("audit")->info("Healthy: plan created", ["user" => $userId, "plan_id" => $plan->id]);

            return $plan;
        });
    }

    /**
     * Оформление подписки на рационы (например, на 30 дней).
     */
    public function subscribeToMeals(int $planId, int $days = 30, string $correlationId = ""): DietSubscription
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($planId, $days, $correlationId) {
            $plan = DietPlan::findOrFail($planId);
            
            // 2. Fraud Check - защита от накрутки подписок
            $this->fraud->check([
                "user_id" => $plan->user_id,
                "operation_type" => "diet_subscription",
                "correlation_id" => $correlationId
            ]);

            $subscription = DietSubscription::create([
                "uuid" => (string) Str::uuid(),
                "plan_id" => $planId,
                "user_id" => $plan->user_id,
                "duration_days" => $days,
                "total_price_kopecks" => $days * 350000, // 3500 руб/день
                "status" => "active",
                "expires_at" => now()->addDays($days),
                "correlation_id" => $correlationId
            ]);

            // 3. Резервация ингредиентов (InventoryManagementService)
            // В реальности списываем сложные композитные материалы (расходники)
            $this->inventory->reserveStock(
                itemId: 0, 
                quantity: $days,
                sourceType: "diet_subscription",
                sourceId: $subscription->id
            );

            Log::channel("audit")->info("Healthy: subscription active", ["sub_id" => $subscription->id]);

            return $subscription;
        });
    }

    /**
     * Обработка ежедневной доставки рационов.
     */
    public function processDailyRation(int $subId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $sub = DietSubscription::with("plan")->findOrFail($subId);

        DB::transaction(function () use ($sub, $correlationId) {
            // 4. Списание остатков
            $this->inventory->deductStock(
                itemId: 0, 
                quantity: 1, 
                reason: "Daily ration delivered for Sub #{$sub->id}",
                sourceType: "diet_ration",
                sourceId: $sub->id
            );

            // 5. Выплата кухне / поставщику (14% комиссия платформы)
            $dailyPrice = 350000;
            $fee = (int) ($dailyPrice * 0.14);
            $payout = $dailyPrice - $fee;

            $this->wallet->credit(
                userId: 999, // ID владельца кухни
                amount: $payout,
                type: "healthy_food_payout",
                reason: "Daily meal delivered: #{$sub->id}",
                correlationId: $correlationId
            );

            Log::channel("audit")->info("Healthy: payment released", ["sub_id" => $sub->id, "payout" => $payout]);
        });
    }
}
