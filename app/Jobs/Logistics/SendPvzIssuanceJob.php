<?php

declare(strict_types=1);

namespace App\Jobs\Logistics;

use Psr\Log\LoggerInterface;

use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Notifications\ChannelManager as NotificationDispatcher;
use App\Notifications\PvzIssuanceNotification;

/**
 * SendPvzIssuanceJob — отправка уведомления о выдаче в ПВЗ
 *
 * Генерирует QR-код и pickup code, отправляет уведомление пользователю.
 * Выполняется асинхронно через queue.
 */
final class SendPvzIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public string $queue = 'logistics';

    public function __construct(private readonly LoggerInterface $logger,
        public readonly Order $order,
        public readonly PickupPoint $pickupPoint,
        public readonly string $correlationId,
        private readonly LogManager $log,
        private readonly NotificationDispatcher $notification,) {}

    public function uniqueId(): string
    {
        return "pvz-issuance-{$this->order->id}";
    }

    public function handle(): void
    {
        $shipment = OrderShipment::where('order_id', $this->order->id)->first();

        if (! $shipment) {
            $this->log->warning('Shipment not found for PVZ issuance', [
                'order_id' => $this->order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // Генерируем pickup code
        $pickupCode = $shipment->generatePickupCode();

        // Генерируем QR код
        $qrCode = $shipment->generateQrCode();

        // Отправляем уведомление пользователю
        try {
            $this->notification->send(
                $this->order->user,
                new PvzIssuanceNotification(
                    order: $this->order,
                    pickupPoint: $this->pickupPoint,
                    pickupCode: $pickupCode,
                    qrCode: $qrCode,
                )
            );

            $this->log->channel('audit')->$this->logger->info('PVZ issuance notification sent', [
                'order_id' => $this->order->id,
                'shipment_id' => $shipment->id,
                'pvz_id' => $this->pickupPoint->id,
                'pickup_code' => $pickupCode,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->log->error('Failed to send PVZ issuance notification', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            // Не выбрасываем исключение, чтобы job не retry бесконечно
            // Уведомление можно отправить позже через retry механизм
        }
    }

    public function failed(Exception $exception): void
    {
        $this->log->error('SendPvzIssuanceJob failed', [
            'order_id' => $this->order->id,
            'pvz_id' => $this->pickupPoint->id,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
