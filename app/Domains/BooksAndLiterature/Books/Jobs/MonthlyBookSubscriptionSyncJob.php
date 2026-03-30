<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MonthlyBookSubscriptionSyncJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly string $correlationId = (string) Str::uuid()
        ) {}

        public function handle(): void
        {
            Log::channel('audit')->info('Monthly Book Box Sync Started', [
                'cid' => $this->correlationId,
                'timestamp' => now()->toIso8601String()
            ]);

            try {
                // Processing in chunks to maintain performance with thousands of subscribers
                BookSubscriptionBox::where('status', 'active')
                    ->where('is_paid', true)
                    ->where('next_delivery_at', '<=', now())
                    ->chunk(100, function ($subscriptions) {
                        foreach ($subscriptions as $box) {
                            $this->processSubscriptionBox($box);
                        }
                    });

            } catch (\Throwable $e) {
                Log::channel('audit')->error('Monthly Book Box Sync CRITICAL FAILURE', [
                    'cid' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }

            Log::channel('audit')->info('Monthly Book Box Sync Completed', [
                'cid' => $this->correlationId
            ]);
        }

        private function processSubscriptionBox(BookSubscriptionBox $box): void
        {
            DB::transaction(function () use ($box) {
                // Find inventory available for the box theme/genre
                $availableBooks = Book::where('genre_id', $box->genre_id)
                    ->where('stock_quantity', '>', 10) // Reserve minimum stock
                    ->where('is_active', true)
                    ->inRandomOrder()
                    ->limit(3) // Monthly box usually contains 2-3 books
                    ->get();

                if ($availableBooks->isEmpty()) {
                    Log::channel('audit')->warning('Subscription fulfillment deferred - No stock for genre', [
                        'cid' => $this->correlationId,
                        'box_id' => $box->id,
                        'genre_id' => $box->genre_id
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
                    'last_sent_at' => now(),
                    'next_delivery_at' => now()->addMonth(),
                    'metadata' => array_merge($box->metadata ?? [], [
                       'last_shipment_uuid' => $order->uuid,
                       'books_sent' => $availableBooks->pluck('id')->toArray()
                    ])
                ]);

                Log::channel('audit')->info('Book Subscription Fulfilled', [
                    'cid' => $this->correlationId,
                    'box_uuid' => $box->uuid,
                    'order_uuid' => $order->uuid
                ]);
            });
        }

        public function tags(): array
        {
            return ['books', 'billing', 'subscription', 'vertical_books'];
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(24);
        }
}
