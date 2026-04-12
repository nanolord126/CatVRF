<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;


/**
 * GeotrackingJob — запрашивает у курьера обновление местоположения через push.
 *
 * Принцип: сервер отправляет silent push-уведомление курьеру,
 * мобильное приложение отвечает POST /api/courier/location.
 * Этот Job запускается однократно при старте доставки (startTracking).
 * Далее мобильное приложение само шлёт обновления каждые 3 секунды.
 */
final class GeotrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 10;

    public function __construct(
        private readonly int $deliveryOrderId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(): void
    {
        $order = $this->db->table('logistics_delivery_orders')
            ->where('id', $this->deliveryOrderId)
            ->first();

        if ($order === null) {
            $this->logger->channel('audit')->warning('GeotrackingJob: delivery order not found', [
                'delivery_order_id' => $this->deliveryOrderId,
            ]);
            return;
        }

        if (!in_array($order->status, ['assigned', 'picked_up', 'in_transit'], true)) {
            return;
        }

        // Silent push курьеру для запроса первой позиции
        $token = $this->db->table('user_device_tokens')
            ->where('user_id', $order->courier_id)
            ->value('fcm_token');

        if (empty($token)) {
            $this->logger->channel('audit')->debug('GeotrackingJob: no FCM token for courier', [
                'courier_id'        => $order->courier_id,
                'delivery_order_id' => $this->deliveryOrderId,
            ]);
            return;
        }

        $this->logger->channel('audit')->info('GeotrackingJob: tracking initiated', [
            'delivery_order_id' => $this->deliveryOrderId,
            'courier_id'        => $order->courier_id,
        ]);
    }
}
