<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageOrderService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param WalletService $walletService
         * @param FraudControlService $fraudService
         */
        public function __construct(
            private WalletService $walletService,
            private FraudControlService $fraudService
        ) {}

        /**
         * Create a new beverage order with all 2026 canons.
         *
         * @param array $data
         * @param string|null $correlationId
         * @return BeverageOrder
         * @throws Exception
         */
        public function createOrder(array $data, ?string $correlationId = null): BeverageOrder
        {
            $correlationId = $correlationId ?? (string) \Illuminate\Support\Str::uuid();

            Log::channel('audit')->info('Creating beverage order', [
                'correlation_id' => $correlationId,
                'data' => $data,
            ]);

            // 1. Fraud Check
            $this->fraudService->check('beverage_order_create', [
                'user_id' => $data['customer_id'],
                'shop_id' => $data['shop_id'],
                'amount' => $data['total_amount'],
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($data, $correlationId) {
                // 2. Validate availability and snapshots
                $items = BeverageItem::whereIn('id', collect($data['items'])->pluck('id'))
                    ->where('shop_id', $data['shop_id'])
                    ->get();

                if ($items->count() !== count($data['items'])) {
                    throw new Exception("Some items are no longer available in this shop.");
                }

                // 3. Create order record
                $order = BeverageOrder::create([
                    'tenant_id' => $data['tenant_id'],
                    'business_group_id' => $data['business_group_id'] ?? null,
                    'shop_id' => $data['shop_id'],
                    'customer_id' => $data['customer_id'],
                    'status' => 'pending',
                    'total_amount' => $data['total_amount'],
                    'payment_status' => 'pending',
                    'payment_method' => $data['payment_method'],
                    'items_snapshot' => $items->toArray(),
                    'delivery_type' => $data['delivery_type'],
                    'address' => $data['address'] ?? null,
                    'correlation_id' => $correlationId,
                    'idempotency_key' => $data['idempotency_key'] ?? (string) \Illuminate\Support\Str::random(32),
                ]);

                // 4. Reserve items (Inventory integration if needed)
                foreach ($items as $item) {
                    if ($item->stock_count > 0) {
                        $item->decrement('stock_count');
                    }
                }

                Log::channel('audit')->info('Beverage order created successfully', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        /**
         * Process order status transition.
         */
        public function updateStatus(int $orderId, string $newStatus, ?string $correlationId = null): BeverageOrder
        {
            return DB::transaction(function () use ($orderId, $newStatus, $correlationId) {
                $order = BeverageOrder::findOrFail($orderId);
                $oldStatus = $order->status;

                $order->update([
                    'status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Beverage order status updated', [
                    'order_id' => $orderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        /**
         * Get orders for specific user/tenant.
         */
        public function getActiveOrders(string $tenantId, ?int $customerId = null): Collection
        {
            $query = BeverageOrder::where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'processing', 'ready']);

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            return $query->latest()->get();
        }
}
