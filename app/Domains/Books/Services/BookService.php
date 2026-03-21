<?php declare(strict_types=1);

namespace App\Domains\Books\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Books\Models\Book;
use App\Domains\Books\Models\BookOrder;
use App\Services\WalletService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class BookService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderBook(int $bookId, int $quantity, array $data): BookOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderBook'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderBook', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderBook'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderBook', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderBook'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderBook', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($bookId, $quantity, $data) {
            $book = Book::lockForUpdate()->find($bookId);
            
            if (!$book) {
                throw new \Exception('Book not found');
            }

            $order = BookOrder::create([
                'tenant_id' => auth()->user()->tenant_id,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'book_id' => $bookId,
                'user_id' => auth()->id(),
                'quantity' => $quantity,
                'total_price' => $book->price * $quantity,
                'status' => 'pending',
            ]);

            $this->walletService->debit(
                auth()->user()->wallet_id,
                $book->price * $quantity,
                'book_purchase',
                $this->correlationId
            );

            Log::channel('audit')->info('Book order created', [
                'correlation_id' => $this->correlationId,
                'book_id' => $bookId,
                'quantity' => $quantity,
            ]);

            return $order;
        });
    }
}
