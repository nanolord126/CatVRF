<?php declare(strict_types=1);

namespace App\Services\Stationery;





use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Models\Stationery\StationeryProduct;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class StationeryService
{

    public function __construct(
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private FraudControlService $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

        /**
         * Creates a new stationery product with mandatory transaction and audit.
         *
         * @param array $data Validated product data
         * @return StationeryProduct
         * @throws \Throwable
         */
        public function createProduct(array $data): StationeryProduct
        {
            $correlationId = $this->correlationId();
            $userId = (int) ($this->authManager->id() ?? 0);

            $this->logger->channel('audit')->info('Attempting to create stationery product', [
                'sku' => $data['sku'] ?? 'unknown',
                'correlation_id' => $correlationId,
            ]);

            // Pre-mutation fraud check
            $this->fraud->check(
                userId: $userId,
                operationType: 'product_create',
                amount: (int) ($data['price_cents'] ?? 0),
                ipAddress: $this->request->ip(),
                deviceFingerprint: $this->request->header('X-Device-Id'),
                correlationId: $correlationId,
            );

            return $this->db->transaction(function () use ($data, $correlationId) {
                $product = StationeryProduct::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->channel('audit')->info('Stationery product created successfully', [
                    'uuid' => $product->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });
        }

        /**
         * Updates stock level via InventoryManagementService logic (simulated here for isolation).
         *
         * @param int $productId
         * @param int $adjustment Positive or negative
         * @param string $reason
         * @return bool
         */
        public function adjustStock(int $productId, int $adjustment, string $reason): bool
        {
            return $this->db->transaction(function () use ($productId, $adjustment, $reason) {
                $product = StationeryProduct::lockForUpdate()->findOrFail($productId);

                $newQuantity = $product->stock_quantity + $adjustment;

                if ($newQuantity < 0) {
                    $this->logger->channel('audit')->warning('Negative stock prevention triggered', [
                        'product_id' => $productId,
                        'adjustment' => $adjustment,
                        'correlation_id' => $this->correlationId(),
                    ]);
                    throw new \DomainException('Insufficient stock quality in stationery warehouse.');
                }

                $product->update([
                    'stock_quantity' => $newQuantity,
                    'correlation_id' => $this->correlationId(),
                ]);

                $this->logger->channel('audit')->info('Stock adjusted for stationery product', [
                    'product_id' => $productId,
                    'adjustment' => $adjustment,
                    'reason' => $reason,
                    'new_stock' => $newQuantity,
                    'correlation_id' => $this->correlationId(),
                ]);

                return true;
            });
        }

        /**
         * Retrieves pricing for a consumer.
         * B2B logic applies if business_group_id is active.
         */
        public function resolvePrice(int $productId, ?int $businessGroupId = null): int
        {
            $product = StationeryProduct::findOrFail($productId);

            if ($businessGroupId !== null) {
                // Check for B2B contract or specific business group rules
                return $product->b2b_price_cents ?? $product->price_cents;
            }

            return $product->price_cents;
        }

        /**
         * Fetches stores with low stock for reactive business management.
         */
        public function getLowStockProducts(int $tenantId): \Illuminate\Support\Collection
        {
            return StationeryProduct::where('tenant_id', $tenantId)
                ->whereRaw('stock_quantity <= min_stock_threshold')
                ->where('is_active', true)
                ->with('store')
                ->get();
        }
}
