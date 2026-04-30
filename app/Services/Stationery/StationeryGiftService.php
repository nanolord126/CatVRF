<?php

declare(strict_types=1);

namespace App\Services\Stationery;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Stationery\StationeryProduct;
use App\Models\Stationery\StationeryGiftSet;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use Illuminate\Support\Collection;

final readonly class StationeryGiftService
{
    private const WRAPPING_FEE_CENTS = 10000; // 100 RUB wrapping fee in cents

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Composes a new gift set from individual products.
     * Calculates total price with optional discount.
     */
    public function createGiftSet(array $data, array $productIds): StationeryGiftSet
    {
        $this->logger->channel('audit')->info('Attempting to create stationery gift set', [
            'name' => $data['name'],
            'product_count' => count($productIds),
            'correlation_id' => $this->correlationId(),
        ]);

        return $this->db->transaction(function () use ($data, $productIds) {
            // Validate all products exist and belong to the same tenant
            $products = StationeryProduct::whereIn('id', $productIds)->get();

            if ($products->count() !== count($productIds)) {
                throw new \InvalidArgumentException('One or more stationery products not found.');
            }

            $totalPrice = $products->sum('price_cents');

            // Apply 10% discount for sets by default
            $setPrice = (int) ($totalPrice * 0.9);

            $giftSet = StationeryGiftSet::create(array_merge($data, [
                'price_cents' => $setPrice,
                'product_ids' => $productIds,
                'correlation_id' => $this->correlationId(),
            ]));

            $this->logger->channel('audit')->info('Stationery gift set created', [
                'uuid' => $giftSet->uuid,
                'set_price' => $setPrice,
                'correlation_id' => $this->correlationId(),
            ]);

            return $giftSet;
        });
    }

    /**
     * Handles order processing for a gift set with optional wrapping.
     */
    public function purchaseGiftSet(int $userId, int $giftSetId, bool $withWrapping = false): bool
    {
        $this->fraud->check((int) $this->guard->id(), 'gift_set_purchase', $this->request->ip());

        return $this->db->transaction(function () use ($userId, $giftSetId, $withWrapping) {
            $giftSet = StationeryGiftSet::findOrFail($giftSetId);

            $finalPrice = $giftSet->price_cents;

            if ($withWrapping) {
                // Fixed wrapping fee (100 RUB)
                $finalPrice += self::WRAPPING_FEE_CENTS;
            }

            // Wallet debit for the user
            $this->wallet->debit($userId, $finalPrice, BalanceTransactionType::WITHDRAWAL, $this, null, null, [
                'user_id' => $userId,
                'gift_set' => $giftSetId,
                'final_price' => $finalPrice,
                'wrapping' => $withWrapping,
                'correlation_id' => $this->correlationId(),
            ]);

            return true;
        });
    }

    /**
     * Retrieves seasonal gift collections for promotional displays.
     */
    public function getSeasonalCollections(int $tenantId): Collection
    {
        return StationeryGiftSet::where('tenant_id', $tenantId)
            ->where('is_seasonal', true)
            ->with('store')
            ->latest()
            ->limit(10)
            ->get();
    }

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }
}
