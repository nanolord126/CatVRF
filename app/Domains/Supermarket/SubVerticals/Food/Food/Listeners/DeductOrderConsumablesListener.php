<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Listeners;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Food\Events\LowConsumableStock;
use App\Domains\Food\Models\Dish;
use Illuminate\Database\DatabaseManager;

final class DeductOrderConsumablesListener
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}


    public function handle(OrderCompleted $event): void
    {
        try {
            $this->logger->$this->logger->info('Order consumables deduction started', [
                'order_id' => $event->order->id,
                'correlation_id' => $event->correlationId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($event) {
                $items = $event->order->items_json ?? [];

                foreach ($items as $item) {
                    $dish = Dish::find($item['dish_id'] ?? null);
                    if (! $dish) {
                        continue;
                    }

                    // Получить consumables для этого блюда
                    $consumables = $dish->consumables_json ?? [];

                    foreach ($consumables as $consumable) {
                        $ingredient = FoodConsumable::query()
                            ->lockForUpdate()
                            ->find($consumable['id'] ?? null);

                        if (! $ingredient) {
                            continue;
                        }

                        // Уменьшить остаток
                        $quantity = ($consumable['qty'] ?? 1) * ($item['qty'] ?? 1);
                        $ingredient->decrement('current_stock', $quantity);

                        $this->logger->$this->logger->info('Consumable deducted', [
                            'consumable_id' => $ingredient->id,
                            'quantity' => $quantity,
                            'current_stock' => $ingredient->current_stock,
                            'correlation_id' => $event->correlationId,
                        ]);

                        // Проверить минимальный остаток
                        if ($ingredient->current_stock < $ingredient->min_stock_threshold) {
                            $this->eventDispatcher->dispatch(new LowConsumableStock(
                                $ingredient,
                                $event->correlationId,
                            ));
                        }
                    }
                }
            });

            $this->logger->$this->logger->info('Order consumables deduction completed', [
                'order_id' => $event->order->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Order consumables deduction failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
