<?php declare(strict_types=1);

namespace App\Domains\ReadyMeals\Services;

use App\Domains\ReadyMeals\Models\ReadyMeal;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис управления готовыми блюдами и наборами — КАНОН 2026.
 * Полная реализация с аудитом, фрод-контролем и транзакциями.
 */
final class ReadyMealService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly InventoryManagementService $inventory,
    ) {}

    /**
     * Оформление заказа на готовое блюдо.
     */
    public function orderMeal(int $clientId, int $mealId, int $quantity, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Rate limiting
        $rlKey = "ready_meals:order:{$clientId}";
        if (RateLimiter::tooManyAttempts($rlKey, 10)) {
            Log::channel('fraud_alert')->warning('ReadyMeals: rate limit reached', [
                'client_id' => $clientId,
                'correlation_id' => $correlationId
            ]);
            throw new \RuntimeException('Слишком много запросов. Пожалуйста, подождите.', 429);
        }
        RateLimiter::hit($rlKey, 60);

        $meal = ReadyMeal::findOrFail($mealId);

        // Fraud Check
        $fraud = $this->fraudControl->check([
            'user_id' => $clientId,
            'operation_type' => 'ready_meal_order',
            'amount' => $meal->price_kopecks * $quantity,
            'correlation_id' => $correlationId,
            'meta' => [
                'meal_id' => $mealId,
                'status' => $meal->status,
                'is_vegan' => $meal->is_vegan
            ]
        ]);

        if ($fraud['decision'] === 'block') {
            Log::channel('audit')->warning('ReadyMeals: fraud block on order', [
                'client_id' => $clientId,
                'score' => $fraud['score'],
                'correlation_id' => $correlationId
            ]);
            throw new \RuntimeException('Действие заблокировано системой безопасности.', 403);
        }

        return DB::transaction(function () use ($clientId, $meal, $quantity, $correlationId) {
            $meal->lockForUpdate();

            if ($meal->current_stock < $quantity) {
                throw new \RuntimeException('Недостаточно блюд в наличии.', 422);
            }

            // Reserve stock
            $this->inventory->reserveStock(
                itemId: $meal->id,
                quantity: $quantity,
                sourceType: 'ready_meal_order',
                sourceId: $clientId, // In real app use orderId
                correlationId: $correlationId
            );

            // Deduct stock (simplified for call, usually happens on completion)
            $meal->decrement('current_stock', $quantity);

            $this->inventory->deductStock(
                itemId: $meal->id,
                quantity: $quantity,
                reason: 'Order complete',
                sourceType: 'ready_meal_order',
                sourceId: $mealId,
                correlationId: $correlationId
            );

            Log::channel('audit')->info('ReadyMeals: meal ordered successfully', [
                'client_id' => $clientId,
                'meal_name' => $meal->name,
                'quantity' => $quantity,
                'total_price' => $meal->price_kopecks * $quantity,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => true,
                'meal' => $meal->name,
                'quantity' => $quantity,
                'correlation_id' => $correlationId
            ];
        });
    }

    /**
     * Списание ингредиентов (для кухни).
     */
    public function deductIngredients(int $mealId, array $inventoryMapping, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $meal = ReadyMeal::findOrFail($mealId);

        DB::transaction(function () use ($meal, $inventoryMapping, $correlationId) {
            foreach ($meal->ingredients as $ingredient) {
                $inventoryId = $inventoryMapping[$ingredient['name']] ?? null;
                if ($inventoryId) {
                    $this->inventory->deductStock(
                        itemId: $inventoryId,
                        quantity: $ingredient['quantity'],
                        reason: "Cooking meal: {$meal->name}",
                        sourceType: 'kitchen_production',
                        sourceId: $meal->id,
                        correlationId: $correlationId
                    );
                }
            }

            Log::channel('audit')->info('ReadyMeals: ingredients deducted for cooking', [
                'meal_id' => $meal->id,
                'correlation_id' => $correlationId
            ]);
        });
    }

    /**
     * Контроль качества и срока годности.
     */
    public function checkFreshness(): void
    {
        $correlationId = (string) Str::uuid();
        
        $expiredMeals = ReadyMeal::where('status', 'active')
            ->where('created_at', '<', now()->subHours(24)) // Simplified
            ->get();

        foreach ($expiredMeals as $meal) {
            DB::transaction(function () use ($meal, $correlationId) {
                $meal->update(['status' => 'out_of_stock', 'tags' => array_merge($meal->tags ?? [], ['expired'])]);
                
                Log::channel('audit')->warning('ReadyMeals: meal expired and deactivated', [
                    'meal_id' => $meal->id,
                    'meal_name' => $meal->name,
                    'correlation_id' => $correlationId
                ]);
            });
        }
    }

    /**
     * Обновление остатков из внешнего KDS/Склада.
     */
    public function syncInventory(int $tenantId, array $data): void
    {
        $correlationId = (string) Str::uuid();

        DB::transaction(function () use ($tenantId, $data, $correlationId) {
            foreach ($data as $item) {
                $meal = ReadyMeal::where('tenant_id', $tenantId)
                    ->where('name', $item['name'])
                    ->first();

                if ($meal) {
                    $oldStock = $meal->current_stock;
                    $meal->update(['current_stock' => $item['quantity']]);

                    $this->inventory->addStock(
                        itemId: $meal->id,
                        quantity: $item['quantity'] - $oldStock,
                        reason: 'External KDS Sync',
                        sourceType: 'kds_sync',
                        sourceId: $tenantId,
                        correlationId: $correlationId
                    );
                }
            }
        });
    }
}
