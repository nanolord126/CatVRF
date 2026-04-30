<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Jobs;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use App\Services\Inventory\InventoryManagementService;
use App\Services\Wallet\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

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

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public int $tries = 5;

    public int $maxExceptions = 2;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        public readonly GroceryOrder $order,
        public readonly string $correlationId,) {
        $this->onQueue('grocery-delivery');
    }

    public function handle(
        InventoryManagementService $inventoryService,
        WalletService $walletService,
        DatabaseManager $db,
        LoggerInterface $logger,
    ): void {
        try {
            $db->transaction(function () use ($walletService, $logger): void {
                if ($this->order->status !== 'in_transit') {
                    $logger->channel('audit')->warning('Order not in transit status', [
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
                    'delivered_at' => \Carbon\CarbonImmutable::now(),
                ]);

                $logger->channel('audit')->$this->logger->info('Order processed and payout completed', [
                    'order_id' => $this->order->id,
                    'payout_amount' => $payout,
                    'commission_amount' => $this->order->commission_amount,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $logger->channel('audit')->error('ProcessOrderDeliveryJob failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->channel('audit')->error('ProcessOrderDeliveryJob permanently failed', [
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
