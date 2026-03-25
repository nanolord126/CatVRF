<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Services;

use App\Domains\HealthyFood\Models\DietPlan;
use App\Domains\HealthyFood\Models\HealthyMeal;
use App\Domains\HealthyFood\Models\HealthProfile;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис управления здоровым питанием и диетами — КАНОН 2026.
 * Полная интеграция с профилем здоровья и ML-рекомендациями.
 */
final class HealthyFoodService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly RecommendationService $recommendation,
    ) {}

    /**
     * Создание персонального плана питания на основе целей.
     */
    public function createDietPlan(int $userId, array $goals, string $correlationId = ""): DietPlan
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting — защита от спама генерации планов
        if (RateLimiter::tooManyAttempts("healthy:plan:{$userId}", 2)) {
            throw new \RuntimeException("План уже генерируется. Попробуйте завтра.", 429);
        }
        RateLimiter::hit("healthy:plan:{$userId}", 86400);

        return $this->db->transaction(function () use ($userId, $goals, $correlationId) {
            // 2. Получение профиля здоровья (если нет — создаем базовый)
            $profile = HealthProfile::firstOrCreate(["user_id" => $userId]);

            // 3. Fraud Check (защита от злоупотреблений квотами AI)
            $fraud = $this->fraud->check([
                "user_id" => $userId,
                "operation_type" => "diet_plan_create",
                "correlation_id" => $correlationId,
                "meta" => ["goals" => $goals]
            ]);

            if ($fraud["decision"] === "block") {
                 throw new \RuntimeException("Запрос отклонен безопасностью.", 403);
            }

            // 4. Генерация плана через RecommendationService (AI)
            $meals = $this->recommendation->getForUser($userId, "healthy_food", [
                "goals" => $goals,
                "restrictions" => $profile->allergens_json ?? []
            ]);

            // 5. Сохранение плана
            $plan = DietPlan::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $profile->tenant_id,
                "user_id" => $userId,
                "correlation_id" => $correlationId,
                "status" => "active",
                "goals_json" => $goals,
                "meals_json" => $meals->map(fn($m) => [
                    "meal_id" => $m->id,
                    "name" => $m->name,
                    "calories" => $m->calories,
                    "day" => $m->day_number ?? 1
                ])->toArray(),
                "tags" => ["goal:" . ($goals["type"] ?? "general"), "generated:ai"]
            ]);

            $this->log->channel("audit")->info("HealthyFood: diet plan generated", ["user_id" => $userId, "plan_id" => $plan->id]);

            return $plan;
        });
    }

    /**
     * Списание блюда по плану (факт приема пищи).
     */
    public function logMealConsumption(int $userId, int $mealId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        
        $this->db->transaction(function () use ($userId, $mealId, $correlationId) {
            $meal = HealthyMeal::findOrFail($mealId);

            // Списание ингредиентов
            foreach ($meal->ingredients_json as $ingredient) {
                $this->inventory->deductStock(
                    itemId: $ingredient["item_id"],
                    quantity: $ingredient["quantity"],
                    reason: "User Meal Logged: {$mealId}",
                    sourceType: "meal_log",
                    sourceId: $userId,
                    correlationId: $correlationId
                );
            }

            $this->log->channel("audit")->info("HealthyFood: meal consumed", ["user_id" => $userId, "meal_id" => $mealId]);
        });
    }

    /**
     * Обновление профиля здоровья.
     */
    public function updateHealthProfile(int $userId, array $data): void
    {
        $profile = HealthProfile::where("user_id", $userId)->firstOrFail();
        
        $profile->update([
            "allergens_json" => $data["allergens"] ?? $profile->allergens_json,
            "weight_kg" => $data["weight"] ?? $profile->weight_kg,
            "height_cm" => $data["height"] ?? $profile->height_cm,
            "meta" => array_merge($profile->meta ?? [], ["last_updated" => now()->toIso8601String()])
        ]);
        
        $this->log->channel("audit")->info("HealthyFood: profile updated", ["user_id" => $userId]);
    }
}
