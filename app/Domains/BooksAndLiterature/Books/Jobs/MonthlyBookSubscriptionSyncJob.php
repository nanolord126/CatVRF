<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class MonthlyBookSubscriptionSyncJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(private readonly string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function handle(): void
        {
            $this->logger->info('Monthly Book Box Sync Started', [
                'cid' => $this->correlationId,
                'timestamp' => Carbon::now()->toIso8601String(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            try {
                // Processing in chunks to maintain performance with thousands of subscribers
                BookSubscriptionBox::where('status', 'active')
                    ->where('is_paid', true)
                    ->where('next_delivery_at', '<=', Carbon::now())
                    ->chunk(100, function ($subscriptions) {
                        foreach ($subscriptions as $box) {
                            $this->processSubscriptionBox($box);
                        }
                    });

            } catch (\Throwable $e) {
                $this->logger->error('Monthly Book Box Sync CRITICAL FAILURE', [
                    'cid' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                throw $e;
            }

            $this->logger->info('Monthly Book Box Sync Completed', [
                'cid' => $this->correlationId,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
        }

        private function processSubscriptionBox(BookSubscriptionBox $box): void
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($box) {
                // Find inventory available for the box theme/genre
                $availableBooks = Book::where('genre_id', $box->genre_id)
                    ->where('stock_quantity', '>', 10) // Reserve minimum stock
                    ->where('is_active', true)
                    ->inRandomOrder()
                    ->limit(3) // Monthly box usually contains 2-3 books
                    ->get();

                if ($availableBooks->isEmpty()) {
                    $this->logger->warning('Subscription fulfillment deferred - No stock for genre', [
                        'cid' => $this->correlationId,
                        'box_id' => $box->id,
                        'genre_id' => $box->genre_id,
                        'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);
                    return;
                }

                // Create Fictional Shipment Order
                $order = BookOrder::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $box->tenant_id,
                    'user_id' => $box->user_id,
                    'store_id' => $availableBooks->first()->store_id, // Default to first matches bookstore
                    'total_amount' => 0, // Subscription already paid
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'correlation_id' => $this->correlationId,
                    'metadata' => [
                        'type' => 'subscription_box_monthly',
                        'subscription_id' => $box->id,
                        'items' => $availableBooks->map(fn($b) => [
                            'book_id' => $b->id,
                            'title' => $b->title,
                            'unit_price' => 0
                        ])->toArray()
                    ]
                ]);

                // Decrement Inventory
                foreach ($availableBooks as $book) {
                    $book->decrement('stock_quantity', 1);
                }

                // Update Box Status for next month
                $box->update([
                    'last_sent_at' => Carbon::now(),
                    'next_delivery_at' => Carbon::now()->addMonth(),
                    'metadata' => array_merge($box->metadata ?? [], [
                       'last_shipment_uuid' => $order->uuid,
                       'books_sent' => $availableBooks->pluck('id')->toArray()
                    ])
                ]);

                $this->logger->info('Book Subscription Fulfilled', [
                    'cid' => $this->correlationId,
                    'box_uuid' => $box->uuid,
                    'order_uuid' => $order->uuid,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            });
        }

        public function tags(): array
        {
            return ['books', 'billing', 'subscription', 'vertical_books'];
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(24);
        }
}
