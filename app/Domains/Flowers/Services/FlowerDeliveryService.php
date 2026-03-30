<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerDeliveryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function assignDelivery(
            int $orderId,
            string $courierName,
            string $courierPhone,
            string $correlationId = '',
        ): FlowerDelivery {
            $correlationId = $correlationId ?: (string)Str::uuid()->toString();

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($orderId, $courierName, $courierPhone, $correlationId) {
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
                    'assigned_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $order->update(['status' => 'ready']);

                Log::channel('audit')->info('Flower delivery assigned', [
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'courier_name' => $courierName,
                    'correlation_id' => $correlationId,
                ]);

                return $delivery;
            });
        }

        public function updateDeliveryStatus(
            int $deliveryId,
            string $status,
            array $location = null,
            string $correlationId = '',
        ): FlowerDelivery {
            $correlationId = $correlationId ?: (string)Str::uuid()->toString();

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($deliveryId, $status, $location, $correlationId) {
                $delivery = FlowerDelivery::query()
                    ->where('id', $deliveryId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $data = ['status' => $status];
                if ($location) {
                    $data['current_location'] = $location;
                }
                if ($status === 'in_transit') {
                    $data['picked_up_at'] = now();
                } elseif ($status === 'delivered') {
                    $data['delivered_at'] = now();
                }

                $delivery->update($data);

                if ($status === 'delivered') {
                    FlowerDeliveryCompleted::dispatch($delivery, $correlationId);
                }

                Log::channel('audit')->info('Flower delivery status updated', [
                    'delivery_id' => $delivery->id,
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);

                return $delivery;
            });
        }

        public function trackDelivery(int $deliveryId): FlowerDelivery
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in Flowers', ['correlation_id' => $correlationId]);

            return FlowerDelivery::query()
                ->where('id', $deliveryId)
                ->with('order.shop')
                ->firstOrFail();
        }
}
