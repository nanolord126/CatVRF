<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Food\Models\Dish;
use Illuminate\Support\Facades\DB;

final class DishConsumableService
{
    public function __construct()
    {
    }

    /**
     * Списать ингредиенты при создании заказа
     */
    public function deductIngredients(int $orderId, array $dishes, string $correlationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'deductIngredients'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL deductIngredients', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($orderId, $dishes, $correlationId) {
                foreach ($dishes as $dishId => $quantity) {
                    $dish = Dish::lockForUpdate()->findOrFail($dishId);

                    if ($dish->current_stock < $quantity) {
                        throw new \Exception("Insufficient ingredient stock for dish {$dishId}");
                    }

                    $dish->decrement('current_stock', $quantity);

                    Log::channel('audit')->info('Dish ingredients deducted', [
                        'order_id' => $orderId,
                        'dish_id' => $dishId,
                        'quantity' => $quantity,
                        'remaining' => $dish->current_stock,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Dish ingredient deduction failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
