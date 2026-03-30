<?php declare(strict_types=1);

namespace App\Domains\Food\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductOrderConsumablesListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(OrderCompleted $event): void
        {
            try {
                Log::channel('audit')->info('Order consumables deduction started', [
                    'order_id' => $event->order->id,
                    'correlation_id' => $event->correlationId,
                ]);

                DB::transaction(function () use ($event) {
                    $items = $event->order->items_json ?? [];

                    foreach ($items as $item) {
                        $dish = \App\Domains\Food\Models\Dish::find($item['dish_id'] ?? null);
                        if (!$dish) {
                            continue;
                        }

                        // Получить consumables для этого блюда
                        $consumables = $dish->consumables_json ?? [];

                        foreach ($consumables as $consumable) {
                            $ingredient = FoodConsumable::query()
                                ->lockForUpdate()
                                ->find($consumable['id'] ?? null);

                            if (!$ingredient) {
                                continue;
                            }

                            // Уменьшить остаток
                            $quantity = ($consumable['qty'] ?? 1) * ($item['qty'] ?? 1);
                            $ingredient->decrement('current_stock', $quantity);

                            Log::channel('audit')->info('Consumable deducted', [
                                'consumable_id' => $ingredient->id,
                                'quantity' => $quantity,
                                'current_stock' => $ingredient->current_stock,
                                'correlation_id' => $event->correlationId,
                            ]);

                            // Проверить минимальный остаток
                            if ($ingredient->current_stock < $ingredient->min_stock_threshold) {
                                event(new \App\Domains\Food\Events\LowConsumableStock(
                                    $ingredient,
                                    $event->correlationId,
                                ));
                            }
                        }
                    }
                });

                Log::channel('audit')->info('Order consumables deduction completed', [
                    'order_id' => $event->order->id,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Order consumables deduction failed', [
                    'order_id' => $event->order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
