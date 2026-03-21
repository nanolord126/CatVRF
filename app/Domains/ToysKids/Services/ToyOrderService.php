<?php declare(strict_types=1);

namespace App\Domains\ToysKids\Services;

use App\Domains\ToysKids\Models\ToyOrder;
use App\Domains\ToysKids\Models\ToyProduct;
use App\Domains\ToysKids\Events\ToyOrderCreated;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class ToyOrderService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createOrder(int $productId, int $quantity, bool $giftWrapping, Carbon $deliveryDate, int $clientId, int $tenantId, string $correlationId): ToyOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createOrder', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($productId, $quantity, $giftWrapping, $deliveryDate, $clientId, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'toy_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $product = ToyProduct::lockForUpdate()->findOrFail($productId);

            if ($product->current_stock < $quantity) {
                throw new \Exception("Insufficient stock for toy {$productId}");
            }

            $giftWrappingPrice = $giftWrapping ? (int)($product->price * 0.05) : 0;
            $totalPrice = ($product->price * $quantity) + $giftWrappingPrice;

            $order = ToyOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'product_id' => $productId,
                'client_id' => $clientId,
                'quantity' => $quantity,
                'gift_wrapping' => $giftWrapping,
                'total_price' => $totalPrice,
                'delivery_date' => $deliveryDate,
                'status' => 'pending',
                'idempotency_key' => md5("{$clientId}:{$productId}:{$quantity}:{$deliveryDate}:{$tenantId}"),
            ]);

            $product->decrement('current_stock', $quantity);

            ToyOrderCreated::dispatch($order->id, $tenantId, $clientId, $totalPrice, $correlationId);
            Log::channel('audit')->info('Toy order created', [
                'order_id' => $order->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function markDelivered(int $orderId, int $tenantId, string $correlationId): ToyOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'markDelivered'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markDelivered', ['domain' => __CLASS__]);

        $order = ToyOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (!$order->isPending()) {
            throw new \Exception("Order {$orderId} is not in pending state");
        }

        $order->update(['status' => 'delivered']);

        Log::channel('audit')->info('Toy order delivered', [
            'order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }
}
