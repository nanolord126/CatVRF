<?php declare(strict_types=1);

namespace App\Domains\Food\TeaHouses\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TeaHousesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
        ) {}

        public function createOrder(int $houseId, array $items, array $data, string $correlationId = ""): TeaOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            if (RateLimiter::tooManyAttempts("tea:order:".auth()->id(), 15)) {
                throw new \RuntimeException("Too many orders", 429);
            }
            RateLimiter::hit("tea:order:".auth()->id(), 3600);

            return DB::transaction(function () use ($houseId, $items, $data, $correlationId) {
                $house = TeaHouse::findOrFail($houseId);
                $total = 0;

                foreach ($items as $item) {
                    $tea = TeaType::where('id', $item['tea_id'])
                        ->where('house_id', $houseId)->firstOrFail();
                    $total += $tea->price_kopecks * $item['quantity'];
                }

                $fraud = $this->fraud->check([
                    'user_id' => auth()->id() ?? 0,
                    'operation_type' => 'tea_order_create',
                    'correlation_id' => $correlationId,
                    'amount' => $total,
                ]);

                if ($fraud['decision'] === 'block') {
                    Log::channel('audit')->error('Tea order blocked', [
                        'user_id' => auth()->id(),
                        'score' => $fraud['score'],
                        'correlation_id' => $correlationId,
                    ]);
                    throw new \RuntimeException("Security block", 403);
                }

                $order = TeaOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'house_id' => $houseId,
                    'client_id' => auth()->id() ?? 0,
                    'correlation_id' => $correlationId,
                    'status' => 'pending_payment',
                    'total_kopecks' => $total,
                    'payout_kopecks' => $total - (int) ($total * 0.14),
                    'payment_status' => 'pending',
                    'items_json' => $items,
                    'ceremony_type' => $data['ceremony_type'] ?? 'casual',
                    'tags' => ['tea' => true, 'items_count' => count($items)],
                ]);

                Log::channel('audit')->info('Tea order created', [
                    'order_id' => $order->id,
                    'house_id' => $houseId,
                    'total_kopecks' => $total,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function completeOrder(int $orderId, string $correlationId = ""): TeaOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            return DB::transaction(function () use ($orderId, $correlationId) {
                $order = TeaOrder::findOrFail($orderId);

                if ($order->payment_status !== 'completed') {
                    throw new \RuntimeException("Order not paid", 400);
                }

                $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);

                $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'tea_payout', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);

                Log::channel('audit')->info('Tea order completed', [
                    'order_id' => $order->id,
                    'payout_kopecks' => $order->payout_kopecks,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function cancelOrder(int $orderId, string $correlationId = ""): TeaOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            return DB::transaction(function () use ($orderId, $correlationId) {
                $order = TeaOrder::findOrFail($orderId);

                if ($order->status === 'completed') {
                    throw new \RuntimeException("Cannot cancel completed", 400);
                }

                $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);

                if ($order->payment_status === 'completed') {
                    $this->wallet->credit(tenant()->id, $order->total_kopecks, 'tea_refund', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                    ]);
                }

                Log::channel('audit')->info('Tea order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        public function getOrder(int $orderId): TeaOrder
        {
            return TeaOrder::with(['house'])->findOrFail($orderId);
        }

        public function getUserOrders(int $clientId)
        {
            return TeaOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get();
        }

        public function getHouseTeas(int $houseId)
        {
            return TeaType::where('house_id', $houseId)->orderBy('name')->get();
        }
}
