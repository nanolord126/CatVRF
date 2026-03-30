<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FarmService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly WalletService $wallet,
        ) {}

        public function createOrder(int $farmId, array $items, array $data, string $correlationId = ""): FarmOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            if (RateLimiter::tooManyAttempts("farm:order:".auth()->id(), 10)) {
                throw new \RuntimeException("Too many orders", 429);
            }
            RateLimiter::hit("farm:order:".auth()->id(), 3600);

            return DB::transaction(function () use ($farmId, $items, $data, $correlationId) {
                $farm = Farm::findOrFail($farmId);
                $total = 0;

                foreach ($items as $item) {
                    $product = FarmProduct::where('id', $item['product_id'])
                        ->where('farm_id', $farmId)->firstOrFail();
                    $itemTotal = $product->price_kopecks * $item['quantity'];
                    $total += $itemTotal;

                    if ($product->available_quantity < $item['quantity']) {
                        throw new \RuntimeException("Product not available", 400);
                    }
                }

                $fraud = $this->fraud->check([
                    'user_id' => auth()->id() ?? 0,
                    'operation_type' => 'farm_order_create',
                    'correlation_id' => $correlationId,
                    'amount' => $total,
                    'ip_address' => request()->ip(),
                ]);

                if ($fraud['decision'] === 'block') {
                    Log::channel('audit')->error('Farm order blocked by fraud', [
                        'user_id' => auth()->id(),
                        'score' => $fraud['score'],
                        'correlation_id' => $correlationId,
                    ]);
                    throw new \RuntimeException("Security block", 403);
                }

                $order = FarmOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'farm_id' => $farmId,
                    'client_id' => auth()->id() ?? 0,
                    'correlation_id' => $correlationId,
                    'delivery_address' => $data['delivery_address'],
                    'delivery_datetime' => $data['delivery_datetime'],
                    'status' => 'pending_payment',
                    'total_kopecks' => $total,
                    'commission_kopecks' => (int) ($total * 0.12),
                    'payout_kopecks' => $total - (int) ($total * 0.12),
                    'payment_status' => 'pending',
                    'items_json' => $items,
                    'tags' => ['farm_direct' => true, 'total_items' => count($items), 'delivery_date' => now()->toDateString()],
                ]);

                Log::channel('audit')->info('Farm order created', [
                    'order_id' => $order->id,
                    'farm_id' => $farmId,
                    'total_kopecks' => $total,
                    'items_count' => count($items),
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function completeOrder(int $orderId, string $correlationId = ""): FarmOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            return DB::transaction(function () use ($orderId, $correlationId) {
                $order = FarmOrder::findOrFail($orderId);

                if ($order->payment_status !== 'completed') {
                    throw new \RuntimeException("Order not paid", 400);
                }

                if ($order->status !== 'pending_payment') {
                    throw new \RuntimeException("Order has incorrect status", 400);
                }

                foreach ($order->items_json as $item) {
                    $product = FarmProduct::findOrFail($item['product_id']);
                    $product->decrement('available_quantity', $item['quantity']);
                }

                $order->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                    'tags' => array_merge($order->tags ?? [], ['completed_at' => now()->toIso8601String()]),
                ]);

                $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'farm_payout', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);

                Log::channel('audit')->info('Farm order completed and payout credited', [
                    'order_id' => $order->id,
                    'farm_id' => $order->farm_id,
                    'payout_kopecks' => $order->payout_kopecks,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function cancelOrder(int $orderId, string $correlationId = ""): FarmOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            return DB::transaction(function () use ($orderId, $correlationId) {
                $order = FarmOrder::findOrFail($orderId);

                if ($order->status === 'completed') {
                    throw new \RuntimeException("Cannot cancel completed order", 400);
                }

                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'refunded',
                    'correlation_id' => $correlationId,
                    'tags' => array_merge($order->tags ?? [], ['cancelled_at' => now()->toIso8601String()]),
                ]);

                if ($order->payment_status === 'completed') {
                    $this->wallet->credit(tenant()->id, $order->total_kopecks, 'farm_refund', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                    ]);
                }

                Log::channel('audit')->info('Farm order cancelled', [
                    'order_id' => $order->id,
                    'farm_id' => $order->farm_id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function getOrder(int $orderId): FarmOrder
        {
            return FarmOrder::with(['farm'])->findOrFail($orderId);
        }

        public function getUserOrders(int $clientId, int $limit = 10)
        {
            return FarmOrder::where('client_id', $clientId)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        }

        public function getFarmOrders(int $farmId, int $limit = 20)
        {
            return FarmOrder::where('farm_id', $farmId)
                ->orderBy('delivery_datetime', 'desc')
                ->take($limit)
                ->get();
        }

        public function getFarmProducts(int $farmId)
        {
            return FarmProduct::where('farm_id', $farmId)
                ->where('available_quantity', '>', 0)
                ->orderBy('name')
                ->get();
        }
}
