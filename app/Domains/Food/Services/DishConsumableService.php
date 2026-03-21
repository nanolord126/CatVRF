<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\Dish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
