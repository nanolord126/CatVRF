<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\DeliveryOrder;
use App\Domains\Food\Models\FoodOrder;
use App\Domains\Food\Infrastructure\Gateways\FakeDeliveryServiceGateway;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FoodDeliveryService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly FakeDeliveryServiceGateway $deliveryGateway,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Создание записи доставки для заказа еды
     */
    public function createDeliveryForOrder(FoodOrder $order): DeliveryOrder
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $order->customer_id ?? 0,
            operationType: 'food_delivery_create',
            amount: (float) $order->total_price,
            correlationId: $correlationId
        );

        // Вызов внешнего сервиса доставки
        $deliveryResult = $this->deliveryGateway->scheduleDelivery(
            orderId: (string) $order->uuid,
            deliveryAddress: $order->delivery_address,
            correlationId: $correlationId
        );

        $delivery = DeliveryOrder::create([
            'tenant_id' => $order->tenant_id,
            'food_order_id' => $order->id,
            'uuid' => $deliveryResult['delivery_id'] ?? (string) Str::uuid(),
            'correlation_id' => $correlationId,
            'status' => 'pending',
            'customer_address' => $order->delivery_address,
            'delivery_lat' => $order->delivery_lat,
            'delivery_lon' => $order->delivery_lon,
            'eta_minutes' => $deliveryResult['estimated_time_minutes'] ?? null,
            'metadata' => [
                'external_delivery_id' => $deliveryResult['delivery_id'] ?? null,
                'estimated_time' => $deliveryResult['estimated_time_minutes'] ?? null,
            ],
        ]);

        $this->logger->info('Food delivery created', [
            'delivery_id' => $delivery->id,
            'food_order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            'created',
            DeliveryOrder::class,
            $delivery->id,
            [],
            $delivery->toArray(),
            $correlationId
        );

        return $delivery;
    }

    /**
     * Обновление статуса доставки
     */
    public function updateDeliveryStatus(DeliveryOrder $delivery, string $status): DeliveryOrder
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $delivery->order->customer_id ?? 0,
            operationType: 'food_delivery_update',
            amount: 0,
            correlationId: $correlationId
        );

        $oldStatus = $delivery->status;
        $delivery->update([
            'status' => $status,
            'correlation_id' => $correlationId,
        ]);

        if ($status === DeliveryOrder::STATUS_DELIVERED) {
            $delivery->update(['delivered_at' => now()]);
        } elseif ($status === DeliveryOrder::STATUS_CANCELLED) {
            $delivery->update(['cancelled_at' => now()]);
        }

        $this->logger->info('Food delivery status updated', [
            'delivery_id' => $delivery->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            'updated',
            DeliveryOrder::class,
            $delivery->id,
            ['status' => $oldStatus],
            ['status' => $status],
            $correlationId
        );

        return $delivery->fresh();
    }

    /**
     * Получение статуса доставки из внешнего сервиса
     */
    public function syncDeliveryStatus(DeliveryOrder $delivery): array
    {
        $correlationId = (string) Str::uuid();

        $status = $this->deliveryGateway->getDeliveryStatus(
            deliveryId: (string) $delivery->uuid,
            correlationId: $correlationId
        );

        $this->logger->info('Food delivery status synced', [
            'delivery_id' => $delivery->id,
            'external_status' => $status['status'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return $status;
    }

    /**
     * Отмена доставки
     */
    public function cancelDelivery(DeliveryOrder $delivery, string $reason = ''): DeliveryOrder
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $delivery->order->customer_id ?? 0,
            operationType: 'food_delivery_cancel',
            amount: 0,
            correlationId: $correlationId
        );

        $delivery->update([
            'status' => DeliveryOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        $this->logger->info('Food delivery cancelled', [
            'delivery_id' => $delivery->id,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            'cancelled',
            DeliveryOrder::class,
            $delivery->id,
            [],
            ['cancellation_reason' => $reason],
            $correlationId
        );

        return $delivery->fresh();
    }
}
