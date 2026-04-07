<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VeganProductService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Create a new vegan gadget or food product.
         * Includes: Inventory initialization, Audit logging, Validation scoping.
         */
        public function createProduct(VeganProductCreateDto $dto): VeganProduct
        {
            $correlationId = $dto->correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-3: Creating vegan product', [
                'sku' => $dto->sku,
                'name' => $dto->name,
                'correlation_id' => $correlationId,
            ]);

            // 1. Double registration check
            $exists = VeganProduct::where('sku', $dto->sku)->exists();
            if ($exists) {
                $this->logger->error('LAYER-3: SKU Conflict', ['sku' => $dto->sku, 'correlation_id' => $correlationId]);
                throw new \DomainException("Product SKU conflict: {$dto->sku}");
            }

            // 2. Fraud Check (check if user is allowed to mass upload)
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vegan_product_create', amount: 0, correlationId: $correlationId ?? '');

            // 3. Persist via transaction
            return $this->db->transaction(function () use ($dto, $correlationId) {
                $product = VeganProduct::create([
                    'name' => $dto->name,
                    'sku' => $dto->sku,
                    'brand' => $dto->brand,
                    'vegan_store_id' => $dto->storeId,
                    'vegan_category_id' => $dto->categoryId,
                    'price' => $dto->price,
                    'b2b_price' => $dto->b2bPrice,
                    'is_b2b_available' => $dto->b2bPrice !== null,
                    'nutrition_info' => $dto->nutritionInfo,
                    'allergen_info' => $dto->allergenInfo,
                    'ingredients' => $dto->ingredients,
                    'current_stock' => $dto->initialStock,
                    'shelf_life_days' => $dto->shelfLifeDays,
                    'weight_grams' => $dto->weightGrams,
                    'correlation_id' => $correlationId,
                    'tags' => $dto->tags,
                ]);

                $this->logger->info('LAYER-3: Product created successfully', [
                    'id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });
        }

        /**
         * Process a plant-based food order with transactional stock locking.
         */
        public function processOrder(VeganOrderProcessDto $dto): \App\Models\Order
        {
            $correlationId = $dto->correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-3: Processing vegan order', [
                'user' => $dto->userId,
                'product' => $dto->productId,
                'qty' => $dto->quantity,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use ($dto, $correlationId) {
                // 1. Find product with Row-level Lock
                $product = VeganProduct::where('id', $dto->productId)->lockForUpdate()->firstOrFail();

                // 2. Validate Inventory
                if ($product->current_stock < $dto->quantity) {
                    $this->logger->error('LAYER-3: Multi-tenant inventory deficit', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'correlation_id' => $correlationId,
                    ]);
                    throw new \DomainException("Insufficient stock for SKU: {$product->sku}");
                }

                // 3. Calculate Price (B2B or B2C)
                $totalAmount = $product->getActivePrice($dto->isB2B) * $dto->quantity;

                // 4. Wallet Debit (requires atomic balance update)
                $this->wallet->debit(
                    userId: $dto->userId,
                    amount: $totalAmount,
                    type: 'vegan_purchase',
                    correlationId: $correlationId
                );

                // 5. Update Stock
                $product->decrement('current_stock', $dto->quantity);
                $product->update(['availability_status' => $product->current_stock > 0 ? 'in_stock' : 'out_of_stock']);

                // 6. Create Meta Order Object (Mocked here for brevity, assume real Order model exists)
                $order = \App\Models\Order::create([
                    'user_id' => $dto->userId,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $correlationId, null, null, ['id' => $order->id, 'correlation_id' => $correlationId]);

                return $order;
            });
        }

        /**
         * Adjust stock levels for warehouse operations.
         */
        public function adjustStock(int $productId, int $delta, string $reason, string $correlationId = ''): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            $this->db->transaction(function () use ($productId, $delta, $reason, $correlationId) {
                $product = VeganProduct::where('id', $productId)->lockForUpdate()->firstOrFail();

                $product->increment('current_stock', $delta);

                $this->logger->info('LAYER-3: Stock Adjustment', [
                    'sku' => $product->sku,
                    'delta' => $delta,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Search products with allergen exclusion filters.
         */
        public function findSafeProducts(array $allergens): Collection
        {
            $query = VeganProduct::where('availability_status', 'in_stock');

            foreach ($allergens as $allergen) {
                $query->whereJsonDoesntContain('allergen_info', $allergen);
            }

            return $query->get();
        }
}
