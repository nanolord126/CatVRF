<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Jobs;

use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use App\Services\Inventory\InventoryManagementService;
use App\Services\Wallet\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Throwable;

/**
 * Обрабатывает доставку заказа и выполняет выплату магазину.
 *
 * Поток:
 * 1. Проверяет статус заказа (должен быть in_transit).
 * 2. Рассчитывает выплату (total - commission).
 * 3. Кредитует wallet магазина через WalletService.
 * 4. Обновляет статус заказа на delivered.
 * 5. Логирует результат с correlation_id.
 */
final class ProcessOrderDeliveryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;
    public int $maxExceptions = 2;

    public function __construct(
        public readonly GroceryOrder $order,
        public readonly string $correlationId,
    ) {
        $this->onQueue('grocery-delivery');
    }

    public function handle(
        InventoryManagementService $inventoryService,
        WalletService $walletService,
    ): void {
        try {
            app(\Illuminate\Database\DatabaseManager::class)->transaction(function () use ($inventoryService, $walletService): void {
                if ($this->order->status !== 'in_transit') {
                    app(\Psr\Log\LoggerInterface::class)->channel('audit')->warning('Order not in transit status', [
                        'order_id' => $this->order->id,
                        'status' => $this->order->status,
                        'correlation_id' => $this->correlationId,
                    ]);
                    return;
                }

                $payout = $this->order->total_price - $this->order->commission_amount;

                $walletService->credit(
                    tenantId: $this->order->store->tenant_id,
                    amount: $payout,
                    type: 'grocery_payout',
                    correlationId: $this->correlationId,
                );

                $this->order->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                app(\Psr\Log\LoggerInterface::class)->channel('audit')->info('Order processed and payout completed', [
                    'order_id' => $this->order->id,
                    'payout_amount' => $payout,
                    'commission_amount' => $this->order->commission_amount,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            app(\Psr\Log\LoggerInterface::class)->channel('audit')->error('ProcessOrderDeliveryJob failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        app(\Psr\Log\LoggerInterface::class)->channel('audit')->error('ProcessOrderDeliveryJob permanently failed', [
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
