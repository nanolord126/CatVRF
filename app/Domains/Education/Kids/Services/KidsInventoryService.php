<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class KidsInventoryService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Add a new product with safety checks.
         */
        public function addProduct(KidsProductCreateDto $dto): KidsProduct
        {
            $this->logger->info('Attempting to add kids product', [
                'sku' => $dto->sku,
                'correlation_id' => $dto->correlation_id
            ]);

            // 1. Fraud control check before mutation
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'kids_inventory_add', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($dto) {
                $product = KidsProduct::create($dto->toArray());

                $this->logger->info('Kids product added successfully', [
                    'product_id' => $product->id,
                    'correlation_id' => $dto->correlation_id
                ]);

                return $product;
            });
        }

        /**
         * Attach specialized toy metadata.
         */
        public function attachToyMetadata(KidsToyCreateDto $dto): KidsToy
        {
            $this->logger->info('Attaching toy metadata', [
                'product_id' => $dto->product_id,
                'correlation_id' => $dto->correlation_id
            ]);

            return $this->db->transaction(function () use ($dto) {
                $toy = KidsToy::updateOrCreate(
                    ['product_id' => $dto->product_id],
                    $dto->toArray()
                );

                $this->logger->info('Toy metadata attached', [
                    'toy_id' => $toy->id,
                    'correlation_id' => $dto->correlation_id
                ]);

                return $toy;
            });
        }

        /**
         * Strict inventory deduction with hold support.
         */
        public function deductStock(int $productId, int $quantity, string $reason, string $correlationId): void
        {
            $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId) {
                $product = KidsProduct::where('id', $productId)->lockForUpdate()->firstOrFail();

                if ($product->stock_quantity < $quantity) {
                    throw new \RuntimeException("Insufficient stock for product ID: {$productId}");
                }

                $product->decrement('stock_quantity', $quantity);

                $this->logger->info('Stock deducted', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Verify safety certificates for a toy.
         */
        public function isToyCertified(int $productId): bool
        {
            $toy = KidsToy::where('product_id', $productId)->first();
            if (!$toy) return false;

            $certificates = $toy->safety_certificates ?? [];
            return in_array('EAC', $certificates) || in_array('GOST', $certificates) || in_array('ISO', $certificates);
        }
}
