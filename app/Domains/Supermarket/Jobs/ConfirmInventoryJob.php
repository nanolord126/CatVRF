<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Jobs;

use App\Domains\Supermarket\Services\InventoryReservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ConfirmInventoryJob - подтверждение резерва инвентаря после успешной оплаты.
 *
 * Запускается асинхронно после успешной оплаты заказа.
 * Подтверждает резервы товаров, продлевая их время жизни.
 */
final readonly class ConfirmInventoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $orderId ID заказа
     * @param  string|null  $correlationId Correlation ID для трассировки
     */
    public function __construct(
        public string $orderId,
        public ?string $correlationId = null,
    ) {
        $this->onQueue('supermarket-high');
    }

    public function handle(
        InventoryReservationService $inventoryReservationService,
        LoggerInterface $logger,
    ): void {
        try {
            $logger->info('Confirming inventory reservation for order', [
                'order_id' => $this->orderId,
                'correlation_id' => $this->correlationId,
            ]);

            $inventoryReservationService->confirmReservation($this->orderId);

            $logger->info('Inventory reservation confirmed successfully', [
                'order_id' => $this->orderId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Failed to confirm inventory reservation', [
                'order_id' => $this->orderId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
