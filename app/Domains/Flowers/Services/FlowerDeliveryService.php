<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Events\FlowerDeliveryCompleted;
use App\Domains\Flowers\Models\FlowerDelivery;
use App\Domains\Flowers\Models\FlowerOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FlowerDeliveryService
{
    public function assignDelivery(
        int $orderId,
        string $courierName,
        string $courierPhone,
        string $correlationId = '',
    ): FlowerDelivery {
        $correlationId = $correlationId ?: (string)Str::uuid();

        return DB::transaction(function () use ($orderId, $courierName, $courierPhone, $correlationId) {
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
        $correlationId = $correlationId ?: (string)Str::uuid();

        return DB::transaction(function () use ($deliveryId, $status, $location, $correlationId) {
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
        return FlowerDelivery::query()
            ->where('id', $deliveryId)
            ->with('order.shop')
            ->firstOrFail();
    }
}
