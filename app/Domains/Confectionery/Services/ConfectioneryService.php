<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Services;

use App\Domains\Confectionery\Models\Cake;
use App\Domains\Confectionery\Models\BakeryOrder;
use App\Domains\Confectionery\Events\BakeryOrderCreated;
use App\Domains\Confectionery\Events\BakeryOrderReady;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class ConfectioneryService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createOrder(int $cakeId, int $clientId, Carbon $deliveryDate, int $tenantId, string $correlationId): BakeryOrder
    {


        return DB::transaction(function () use ($cakeId, $clientId, $deliveryDate, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'bakery_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $cake = Cake::findOrFail($cakeId);
            $order = BakeryOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'cake_id' => $cakeId,
                'client_id' => $clientId,
                'order_date' => now(),
                'delivery_date' => $deliveryDate,
                'total_price' => $cake->price,
                'status' => 'pending',
                'idempotency_key' => md5("{$clientId}:{$cakeId}:{$deliveryDate}:{$tenantId}"),
            ]);

            BakeryOrderCreated::dispatch($order->id, $tenantId, $clientId, $cake->price, $correlationId);
            Log::channel('audit')->info('Bakery order created', [
                'order_id' => $order->id,
                'cake_id' => $cakeId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function markReady(int $orderId, int $tenantId, string $correlationId): BakeryOrder
    {


        $order = BakeryOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (!$order->isPending()) {
            throw new \Exception("Order {$orderId} is not in pending state");
        }

        $order->update(['status' => 'ready']);

        BakeryOrderReady::dispatch($order->id, $tenantId, $correlationId);
        Log::channel('audit')->info('Bakery order marked ready', [
            'order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }

    public function markDelivered(int $orderId, int $tenantId, string $correlationId): BakeryOrder
    {


        $order = BakeryOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (!$order->isReady()) {
            throw new \Exception("Order {$orderId} is not ready for delivery");
        }

        $order->update(['status' => 'delivered']);

        Log::channel('audit')->info('Bakery order delivered', [
            'order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }
}
