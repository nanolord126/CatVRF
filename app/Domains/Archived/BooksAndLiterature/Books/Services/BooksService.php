<?php declare(strict_types=1);

namespace App\Domains\Archived\BooksAndLiterature\Books\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BooksService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud, private readonly WalletService $wallet) {}


        public function createOrder(array $items, string $correlationId = ""): BookOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            if (RateLimiter::tooManyAttempts("books:order:".auth()->id(), 20)) throw new \RuntimeException("Too many orders", 429);


            RateLimiter::hit("books:order:".auth()->id(), 3600);


            return DB::transaction(function () use ($items, $correlationId) {


                $total = 0;


                foreach ($items as $item) {


                    $book = Book::findOrFail($item['book_id']);


                    $total += $book->price_kopecks * $item['quantity'];


                }


                $fraud = $this->fraud->check(['user_id' => auth()->id() ?? 0, 'operation_type' => 'book_order', 'correlation_id' => $correlationId, 'amount' => $total]);


                if ($fraud['decision'] === 'block') {


                    Log::channel('audit')->error('Book order blocked', ['user_id' => auth()->id(), 'score' => $fraud['score'], 'correlation_id' => $correlationId]);


                    throw new \RuntimeException("Security block", 403);


                }


                $order = BookOrder::create([


                    'uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'client_id' => auth()->id() ?? 0, 'correlation_id' => $correlationId,


                    'status' => 'pending_payment', 'total_kopecks' => $total, 'payout_kopecks' => $total - (int) ($total * 0.14),


                    'payment_status' => 'pending', 'items_json' => $items, 'tags' => ['books' => true],


                ]);


                Log::channel('audit')->info('Book order created', ['order_id' => $order->id, 'total_kopecks' => $total, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function completeOrder(int $orderId, string $correlationId = ""): BookOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            return DB::transaction(function () use ($orderId, $correlationId) {


                $order = BookOrder::findOrFail($orderId);


                if ($order->payment_status !== 'completed') throw new \RuntimeException("Order not paid", 400);


                $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);


                $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'book_payout', ['correlation_id' => $correlationId, 'order_id' => $order->id]);


                Log::channel('audit')->info('Book order completed', ['order_id' => $order->id, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function cancelOrder(int $orderId, string $correlationId = ""): BookOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            return DB::transaction(function () use ($orderId, $correlationId) {


                $order = BookOrder::findOrFail($orderId);


                if ($order->status === 'completed') throw new \RuntimeException("Cannot cancel completed", 400);


                $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);


                if ($order->payment_status === 'completed') {


                    $this->wallet->credit(tenant()->id, $order->total_kopecks, 'book_refund', ['correlation_id' => $correlationId, 'order_id' => $order->id]);


                }


                Log::channel('audit')->info('Book order cancelled', ['order_id' => $order->id, 'correlation_id' => $correlationId]);


                return $order;


            });


        }


        public function getOrder(int $orderId): BookOrder { return BookOrder::findOrFail($orderId); }


        public function getUserOrders(int $clientId) { return BookOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get(); }
}
