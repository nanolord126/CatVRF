<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Events\FlowerDeliveryCompleted;
use App\Domains\Flowers\Models\FlowerDelivery;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerDeliveryService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Назначить курьера на доставку цветов.
     *
     * @param int    $orderId       ID заказа
     * @param string $courierName   Имя курьера
     * @param string $courierPhone  Телефон курьера
     * @param int    $userId        ID пользователя (для fraud-check)
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function assignDelivery(
        int $orderId,
        string $courierName,
        string $courierPhone,
        int $userId,
        string $correlationId = '',
    ): FlowerDelivery {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'flower_delivery_assign',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($orderId, $courierName, $courierPhone, $correlationId): FlowerDelivery {
                $order = FlowerOrder::query()
                    ->where('id', $orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $delivery = FlowerDelivery::query()->create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'shop_id' => $order->shop_id,
                    'courier_name' => $courierName,
                    'courier_phone' => $courierPhone,
                    'status' => 'assigned',
                    'assigned_at' => Carbon::now(),
                    'correlation_id' => $correlationId,
                ]);

                $order->update(['status' => 'ready']);

                $this->logger->info('Flower delivery assigned', [
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'courier_name' => $courierName,
                    'correlation_id' => $correlationId,
                ]);

                return $delivery;
            });
        }

    /**
     * Обновить статус доставки.
     *
     * @param int         $deliveryId    ID доставки
     * @param string      $status        Новый статус
     * @param int         $userId        ID пользователя (для fraud-check)
     * @param array|null  $location      Текущая локация
     * @param string      $correlationId Трейсинг-идентификатор
     */
    public function updateDeliveryStatus(
        int $deliveryId,
        string $status,
        int $userId,
        ?array $location = null,
        string $correlationId = '',
    ): FlowerDelivery {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'flower_delivery_status_update',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($deliveryId, $status, $location, $correlationId): FlowerDelivery {
                $delivery = FlowerDelivery::query()
                    ->where('id', $deliveryId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $data = ['status' => $status];
                if ($location) {
                    $data['current_location'] = $location;
                }
                if ($status === 'in_transit') {
                    $data['picked_up_at'] = Carbon::now();
                } elseif ($status === 'delivered') {
                    $data['delivered_at'] = Carbon::now();
                }

                $delivery->update($data);

                if ($status === 'delivered') {
                    FlowerDeliveryCompleted::dispatch($delivery, $correlationId);
                }

                $this->logger->info('Flower delivery status updated', [
                    'delivery_id' => $delivery->id,
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);

                return $delivery;
            });
        }

    /**
     * Получить текущий статус доставки.
     *
     * @param int $deliveryId ID доставки
     */
    public function trackDelivery(int $deliveryId): FlowerDelivery
    {
        return FlowerDelivery::query()
            ->where('id', $deliveryId)
            ->with('order.shop')
            ->firstOrFail();
    }
}
