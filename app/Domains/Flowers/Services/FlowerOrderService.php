<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Events\FlowerOrderPlaced;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\FlowerOrderItem;
use App\Domains\Flowers\Models\FlowerProduct;
use App\Services\FraudControlService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FlowerOrderService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createPublicOrder(
        int $tenantId,
        int $userId,
        int $shopId,
        array $items,
        array $deliveryData,
        string $correlationId = '',
    ): FlowerOrder {
        $correlationId = $correlationId ?: (string)Str::uuid();

        try {
            $this->fraudControlService->check(
                tenantId: $tenantId,
                userId: $userId,
                action: 'flower_order_create',
                correlationId: $correlationId,
            );

            return DB::transaction(function () use ($tenantId, $userId, $shopId, $items, $deliveryData, $correlationId) {
                $subtotal = 0;
                $orderItems = [];

                foreach ($items as $item) {
                    $product = FlowerProduct::query()
                        ->where('id', $item['product_id'])
                        ->where('shop_id', $shopId)
                        ->where('tenant_id', $tenantId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $quantity = $item['quantity'];
                    $unitPrice = $product->price;
                    $totalPrice = $unitPrice * $quantity;

                    $subtotal += $totalPrice;
                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'customizations' => $item['customizations'] ?? null,
                    ];
                }

                $commissionAmount = $subtotal * 0.14;
                $deliveryFee = $deliveryData['delivery_fee'] ?? 0;
                $totalAmount = $subtotal + $deliveryFee;

                $order = FlowerOrder::query()->create([
                    'tenant_id' => $tenantId,
                    'shop_id' => $shopId,
                    'user_id' => $userId,
                    'order_number' => $this->generateOrderNumber(),
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'commission_amount' => $commissionAmount,
                    'total_amount' => $totalAmount,
                    'recipient_name' => $deliveryData['recipient_name'],
                    'recipient_phone' => $deliveryData['recipient_phone'],
                    'delivery_address' => $deliveryData['delivery_address'],
                    'delivery_date' => $deliveryData['delivery_date'],
                    'delivery_time_slot' => $deliveryData['delivery_time_slot'] ?? null,
                    'message' => $deliveryData['message'] ?? null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                foreach ($orderItems as $itemData) {
                    FlowerOrderItem::query()->create([
                        'order_id' => $order->id,
                        ...$itemData,
                    ]);
                }

                Log::channel('audit')->info('Flower order created', [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $correlationId,
                ]);

                FlowerOrderPlaced::dispatch($order, $correlationId);

                return $order;
            });
        } catch (\Exception $exception) {
            Log::channel('audit')->error('Flower order creation failed', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $exception;
        }
    }

    public function getPublicOrders(int $tenantId, int $userId): Collection
    {
        return FlowerOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->with(['shop', 'items.product'])
            ->get();
    }

    public function updateOrderStatus(int $orderId, string $status, string $correlationId = ''): FlowerOrder
    {
        return DB::transaction(function () use ($orderId, $status, $correlationId) {
            $order = FlowerOrder::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $order->update(['status' => $status]);

            Log::channel('audit')->info('Flower order status updated', [
                'order_id' => $order->id,
                'status' => $status,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        return 'FLO-' . date('Ymd') . '-' . Str::upper(Str::random(8));
    }
}
