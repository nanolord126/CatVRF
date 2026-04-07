<?php declare(strict_types=1);

namespace App\Services\Stationery;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Stationery\StationeryProduct;
use App\Models\Stationery\StationeryGiftSet;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class StationeryGiftService
{

    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

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
                    $finalPrice += 10000;
                }

                // Wallet debit for the user
                $this->wallet->debit($userId, $finalPrice, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $this, null, null, [
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
        public function getSeasonalCollections(int $tenantId): \Illuminate\Support\Collection
        {
            return StationeryGiftSet::where('tenant_id', $tenantId)
                ->where('is_seasonal', true)
                ->with('store')
                ->latest()
                ->limit(10)
                ->get();
        }
}
