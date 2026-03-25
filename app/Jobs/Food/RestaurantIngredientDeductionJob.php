<?php declare(strict_types=1);

namespace App\Jobs\Food;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class RestaurantIngredientDeductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $orderId,
        private readonly int $tenantId,
    ) {
        $this->onQueue('inventory');
    }

    public function tags(): array
    {
        return ['food', 'ingredient', 'inventory', $this->tenantId];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(20);
    }

    public function handle(InventoryManagementService $inventoryService): void
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->db->transaction(function () use ($inventoryService, $correlationId) {
                $order = $inventoryService->getRestaurantOrderWithDishes($this->orderId);

                if (! $order || $order->status !== 'completed') {
                    $this->log->channel('audit')->info('Order not ready for ingredient deduction', [
                        'correlation_id' => $correlationId,
                        'order_id' => $this->orderId,
                        'status' => $order?->status,
                    ]);

                    return;
                }

                foreach ($order->dishes as $dish) {
                    foreach ($dish->consumables as $consumable) {
                        $inventoryService->deductStock(
                            itemId: $consumable->id,
                            quantity: $consumable->quantity,
                            reason: "Order #{$this->orderId} completed",
                            sourceType: 'restaurant_order',
                            sourceId: $this->orderId
                        );

                        $this->log->channel('audit')->info('Ingredient deducted', [
                            'correlation_id' => $correlationId,
                            'ingredient_id' => $consumable->id,
                            'quantity' => $consumable->quantity,
                            'dish_id' => $dish->id,
                            'order_id' => $this->orderId,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ingredient deduction job failed', [
                'correlation_id' => $correlationId,
                'order_id' => $this->orderId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
