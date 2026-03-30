<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud, private readonly WalletService $wallet) {}


        public function createOrder(int $sellerId, array $items, string $correlationId = ""): ElectronicOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            if (RateLimiter::tooManyAttempts("electronics:order:".auth()->id(), 15)) throw new \RuntimeException("Too many orders", 429);


            RateLimiter::hit("electronics:order:".auth()->id(), 3600);


            return DB::transaction(function () use ($sellerId, $items, $correlationId) {


                $total = 0;


                foreach ($items as $item) {


                    $product = ElectronicProduct::where('id', $item['product_id'])->firstOrFail();


                    $total += $product->price_kopecks * $item['quantity'];


                    if ($product->stock < $item['quantity']) throw new \RuntimeException("Out of stock", 400);


                }


                $fraud = $this->fraud->check(['user_id' => auth()->id() ?? 0, 'operation_type' => 'electronic_order', 'correlation_id' => $correlationId, 'amount' => $total]);


                if ($fraud['decision'] === 'block') {


                    Log::channel('audit')->error('Electronic order blocked', ['user_id' => auth()->id(), 'correlation_id' => $correlationId]);


                    throw new \RuntimeException("Security block", 403);


                }


                $order = ElectronicOrder::create([


                    'uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'seller_id' => $sellerId, 'client_id' => auth()->id() ?? 0,


                    'correlation_id' => $correlationId, 'status' => 'pending_payment', 'total_kopecks' => $total,


                    'payout_kopecks' => $total - (int) ($total * 0.14), 'payment_status' => 'pending', 'items_json' => $items,


                    'tags' => ['electronics' => true],


                ]);


                Log::channel('audit')->info('Electronic order created', ['order_id' => $order->id, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function completeOrder(int $orderId, string $correlationId = ""): ElectronicOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            return DB::transaction(function () use ($orderId, $correlationId) {


                $order = ElectronicOrder::findOrFail($orderId);


                if ($order->payment_status !== 'completed') throw new \RuntimeException("Order not paid", 400);


                foreach ($order->items_json as $item) {


                    ElectronicProduct::findOrFail($item['product_id'])->decrement('stock', $item['quantity']);


                }


                $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);


                $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'electronic_payout', ['correlation_id' => $correlationId, 'order_id' => $order->id]);


                Log::channel('audit')->info('Electronic order completed', ['order_id' => $order->id, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function cancelOrder(int $orderId, string $correlationId = ""): ElectronicOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            return DB::transaction(function () use ($orderId, $correlationId) {


                $order = ElectronicOrder::findOrFail($orderId);


                if ($order->status === 'completed') throw new \RuntimeException("Cannot cancel completed", 400);


                $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);


                if ($order->payment_status === 'completed') {


                    $this->wallet->credit(tenant()->id, $order->total_kopecks, 'electronic_refund', ['correlation_id' => $correlationId, 'order_id' => $order->id]);


                }


                Log::channel('audit')->info('Electronic order cancelled', ['order_id' => $order->id, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function getOrder(int $orderId): ElectronicOrder { return ElectronicOrder::findOrFail($orderId); }


        public function getUserOrders(int $clientId) { return ElectronicOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get(); }
}
