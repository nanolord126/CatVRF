<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BooksService
{


    public function __construct(private readonly FraudControlService $fraud, private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        public function createOrder(array $items, string $correlationId = ""): BookOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            if ($this->rateLimiter->tooManyAttempts("books:order:".$this->guard->id(), 20)) throw new \RuntimeException("Too many orders", 429);
            $this->rateLimiter->hit("books:order:".$this->guard->id(), 3600);

            return $this->db->transaction(function () use ($items, $correlationId) {
                $total = 0;
                foreach ($items as $item) {
                    $book = Book::findOrFail($item['book_id']);
                    $total += $book->price_kopecks * $item['quantity'];
                }

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'book_order', amount: 0, correlationId: $correlationId ?? '');
                if ($fraud['decision'] === 'block') {
                    $this->logger->error('Book order blocked', ['user_id' => $this->guard->id(), 'score' => $fraud['score'], 'correlation_id' => $correlationId]);
                    throw new \RuntimeException("Security block", 403);
                }

                $order = BookOrder::create([
                    'uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'client_id' => $this->guard->id() ?? 0, 'correlation_id' => $correlationId,
                    'status' => 'pending_payment', 'total_kopecks' => $total, 'payout_kopecks' => $total - (int) ($total * 0.14),
                    'payment_status' => 'pending', 'items_json' => $items, 'tags' => ['books' => true],
                ]);

                $this->logger->info('Book order created', ['order_id' => $order->id, 'total_kopecks' => $total, 'correlation_id' => $correlationId]);
                return $order;
            });
        }

        public function completeOrder(int $orderId, string $correlationId = ""): BookOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            return $this->db->transaction(function () use ($orderId, $correlationId) {
                $order = BookOrder::findOrFail($orderId);
                if ($order->payment_status !== 'completed') throw new \RuntimeException("Order not paid", 400);
                $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);
                $this->wallet->credit(
                    tenantId: tenant()->id,
                    amount: $order->payout_kopecks,
                    type: \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,
                    meta: [
                        'order_id'       => $order->id,
                        'correlation_id' => $correlationId,
                    ],
                );
                return $order;
            });
        }

        public function cancelOrder(int $orderId, string $correlationId = ""): BookOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            return $this->db->transaction(function () use ($orderId, $correlationId) {
                $order = BookOrder::findOrFail($orderId);
                if ($order->status === 'completed') throw new \RuntimeException("Cannot cancel completed", 400);
                $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);
                if ($order->payment_status === 'completed') {
                    $this->wallet->credit(
                        tenantId: tenant()->id,
                        amount: $order->total_kopecks,
                        type: \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                        meta: [
                            'order_id'       => $order->id,
                            'correlation_id' => $correlationId,
                        ],
                    );
                }
                return $order;
            });
        }

        public function getOrder(int $orderId): BookOrder { return BookOrder::findOrFail($orderId); }
        public function getUserOrders(int $clientId) { return BookOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get(); }
}
