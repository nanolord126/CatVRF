<?php

declare(strict_types=1);

namespace App\Services\Collectibles;

use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Collectibles\CollectibleItem;
use App\Models\Collectibles\CollectibleOrder;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Models\Collectibles\UserCollection;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class CollectibleService
{
    use WithAuditLogging;

    public function __construct(
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
    ) {}

    /**
     * Executes a direct purchase of a collectible item.
     *
     * @throws \Exception
     */
    public function purchase(int $userId, int $itemId, string $type = 'b2c'): CollectibleOrder
    {
        $item = CollectibleItem::with('store')->findOrFail($itemId);

        // 1. Pre-purchase Fraud Check
        $this->fraud->check((int) $userId, 'collectible_purchase', $this->request->ip());

        return $this->db->transaction(function () use ($userId, $item) {
            // 2. Financial settlement via WalletService
            $this->wallet->debit($userId, $item->price_cents, BalanceTransactionType::COMMISSION, $this, null, null, [
                'item_id' => $item->id,
                'store_id' => $item->store_id,
                'correlation_id' => $this->correlationId,
            ]);

            return $item;
        });
    }

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    private function getOrCreateUserCollection(int $userId, string $categoryName): int
    {
        $collection = UserCollection::firstOrCreate([
            'user_id' => $userId,
            'name' => "My {$categoryName} Collection",
        ]);

        return $collection->id;
    }
}
