<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Domains\Fashion\Events\OrderPlaced;
use App\Domains\Fashion\Models\FashionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class OrderService
{
    public function createOrder(
        int $tenantId,
        int $storeId,
        int $customerId,
        array $items,
        float $subtotal,
        float $shippingCost,
        string $shippingAddress,
        ?string $correlationId = null,
    ): FashionOrder {
        try {
            $correlationId ??= Str::uuid();
            $discountAmount = 0;
            $commissionAmount = $subtotal * 0.14;
            $totalAmount = $subtotal + $shippingCost - $discountAmount;

            $order = DB::transaction(function () use (
                $tenantId,
                $storeId,
                $customerId,
                $items,
                $subtotal,
                $shippingCost,
                $discountAmount,
                $commissionAmount,
                $totalAmount,
                $shippingAddress,
                $correlationId,
            ) {
                $order = FashionOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenantId,
                    'fashion_store_id' => $storeId,
                    'customer_id' => $customerId,
                    'order_number' => 'ORD-'.Str::upper(Str::random(8)),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'shipping_address' => $shippingAddress,
                    'items' => collect($items),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fashion order created', [
                    'order_id' => $order->id,
                    'fashion_store_id' => $storeId,
                    'customer_id' => $customerId,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $correlationId,
                ]);

                event(new OrderPlaced($order, $correlationId));

                return $order;
            });

            return $order;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create fashion order', [
                'error' => $e->getMessage(),
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }

    public function cancelOrder(FashionOrder $order, string $reason, ?string $correlationId = null): void
    {
        try {
            $correlationId ??= Str::uuid();

            DB::transaction(function () use ($order, $reason, $correlationId) {
                $order->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fashion order cancelled', [
                    'order_id' => $order->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to cancel fashion order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }

    public function updateOrderStatus(FashionOrder $order, string $status, ?string $correlationId = null): void
    {
        try {
            $correlationId ??= Str::uuid();

            DB::transaction(function () use ($order, $status, $correlationId) {
                $updateData = [
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ];

                if ($status === 'shipped') {
                    $updateData['shipped_at'] = now();
                } elseif ($status === 'delivered') {
                    $updateData['delivered_at'] = now();
                }

                $order->update($updateData);

                Log::channel('audit')->info('Fashion order status updated', [
                    'order_id' => $order->id,
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update fashion order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }
}
