<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Jobs;

use LoggerInterface;

use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use App\Domains\GroceryAndDelivery\Services\FastDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Назначает курьера/партнёра доставки на заказ.
 *
 * Поток:
 * 1. Ищет доступного партнёра через FastDeliveryService.
 * 2. Привязывает партнёра к заказу и меняет статус на in_transit.
 * 3. При неудаче — повторяет до 10 раз с возрастающими интервалами.
 * 4. Логирует каждую попытку с correlation_id.
 */
final class AssignDeliveryPartnerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 10;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30, 60, 120, 300];

    public function __construct(private readonly LoggerInterface $loggerInterface,
        public readonly GroceryOrder $order,
        public readonly string $correlationId,) {
        $this->onQueue('grocery-assignment');
    }

    public function handle(FastDeliveryService $deliveryService, DatabaseManager $db, LoggerInterface $logger): void
    {
        try {
            $db->transaction(function () use ($deliveryService, $logger): void {
                $partner = $deliveryService->assignDeliveryPartner(
                    $this->order,
                    $this->correlationId,
                );

                $this->order->update([
                    'delivery_partner_id' => $partner->id,
                    'status' => 'in_transit',
                ]);

                $logger->channel('audit')->$this->logger->info('Delivery partner assigned', [
                    'order_id' => $this->order->id,
                    'partner_id' => $partner->id,
                    'partner_rating' => $partner->rating,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            if ($this->attempts() < $this->tries) {
                $logger->channel('audit')->warning('Will retry assignment', [
                    'order_id' => $this->order->id,
                    'attempt' => $this->attempts(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->release(60);
            } else {
                $logger->channel('audit')->error('AssignDeliveryPartnerJob permanently failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('groceryanddelivery job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
