<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Services;

use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Models\Book;
use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Models\BookOrder;
use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Models\BookReview;
use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\DTOs\BookSaveDto;
use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\DTOs\VolumeOrderDto;
use App\Domains\BooksAndLiterature\BooksAndLiterature\Books\DTOs\BookReviewDto;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * BooksDomainService (Layer 3/9)
 * Strategic service for books, authors, and B2B/B2C fulfillment.
 * Encapsulates transactions, fraud checks, and audit logging.
 */
class BooksDomainService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    /**
     * Create or update a book with bio-data and inventory tracking.
     */
    public function upsertBook(BookSaveDto $dto): Book
    {
        $correlationId = $dto->correlationId ?? (string) Str::uuid();

        // 1. Audit Entry
        Log::channel('audit')->info('Book Upsert Initiated', [
            'sku' => $dto->isbn,
            'cid' => $correlationId,
            'title' => $dto->title
        ]);

        return DB::transaction(function () use ($dto, $correlationId) {
            // 2. Fraud Check (Market manipulation)
            $this->fraudControl::check([
                'action' => 'book_upsert',
                'sku' => $dto->isbn,
                'price' => $dto->priceB2c,
                'cid' => $correlationId
            ]);

            $book = Book::updateOrCreate(
                ['isbn' => $dto->isbn, 'tenant_id' => filament()->getTenant()->id],
                [
                    'title' => $dto->title,
                    'author_id' => $dto->authorId,
                    'genre_id' => $dto->genreId,
                    'store_id' => $dto->storeId,
                    'format' => $dto->format,
                    'price_b2c' => $dto->priceB2c,
                    'price_b2b' => $dto->priceB2b,
                    'stock_quantity' => $dto->stockQuantity,
                    'description' => $dto->description,
                    'page_count' => $dto->pageCount,
                    'language' => $dto->language,
                    'metadata' => $dto->metadata,
                    'tags' => $dto->tags,
                    'is_active' => $dto->isActive,
                    'correlation_id' => $correlationId
                ]
            );

            Log::channel('audit')->info('Book Upsert Completed', ['id' => $book->id, 'cid' => $correlationId]);

            return $book;
        });
    }

    /**
     * Create B2B Volume/Corporate Order.
     * Special pricing and status flow for schools/libraries.
     */
    public function createCorporateOrder(VolumeOrderDto $dto): BookOrder
    {
        $correlationId = $dto->correlationId ?? (string) Str::uuid();

        return DB::transaction(function () use ($dto, $correlationId) {
            $totalAmount = 0;
            $orderItems = [];

            foreach ($dto->items as $item) {
                $book = Book::findOrFail($item['book_id']);
                
                // B2B Pricing logic: use price_b2b for volume
                $price = $book->price_b2b;
                $lineTotal = $price * $item['qty'];
                $totalAmount += $lineTotal;

                // Inventory update
                if ($book->stock_quantity < $item['qty']) {
                    throw new \Exception("Insufficient stock for ISBN: {$book->isbn}");
                }
                $book->decrement('stock_quantity', $item['qty']);

                $orderItems[] = [
                    'book_id' => $book->id,
                    'qty' => $item['qty'],
                    'price_at_buy' => $price,
                    'title' => $book->title
                ];
            }

            $order = BookOrder::create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'order_number' => 'B2B-' . Str::upper(Str::random(10)),
                'total_amount' => $totalAmount,
                'status' => 'processing',
                'shipping_address' => $dto->shippingAddress,
                'order_items' => $orderItems,
                'correlation_id' => $correlationId
            ]);

            Log::channel('audit')->info('Corporate Order Created', [
                'order_id' => $order->id,
                'type' => $dto->type,
                'total' => $totalAmount,
                'cid' => $correlationId
            ]);

            return $order;
        });
    }

    /**
     * Submit Literary Review with Mood Analysis.
     */
    public function submitReview(BookReviewDto $dto): BookReview
    {
        $correlationId = $dto->correlationId ?? (string) Str::uuid();

        return DB::transaction(function () use ($dto, $correlationId) {
            $review = BookReview::create([
                'tenant_id' => filament()->getTenant()->id,
                'user_id' => $dto->userId,
                'book_id' => $dto->bookId,
                'rating' => $dto->rating,
                'comment' => $dto->comment,
                'mood_tags' => $dto->moodTags,
                'is_verified_purchase' => $this->checkVerifiedPurchase($dto->userId, $dto->bookId),
                'correlation_id' => $correlationId
            ]);

            Log::channel('audit')->info('Book Review Submitted', ['id' => $review->id, 'cid' => $correlationId]);

            return $review;
        });
    }

    /**
     * Internal verification of purchase for review validity.
     */
    private function checkVerifiedPurchase(int $userId, int $bookId): bool
    {
        return BookOrder::where('user_id', $userId)
            ->whereJsonContains('order_items', [['book_id' => $bookId]])
            ->where('status', 'delivered')
            ->exists();
    }
}
