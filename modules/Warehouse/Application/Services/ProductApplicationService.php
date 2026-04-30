<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\Product;
use Modules\Warehouse\Domain\Repositories\ProductRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use Illuminate\Support\Str;

/**
 * Product Application Service - Production Layer
 *
 * Orchestrates product catalog operations (product catalog, not stock)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ProductApplicationService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create a new product
     */
    public function createProduct(
        string $sku,
        string $name,
        int $tenantId,
        string $unit = 'шт',
        float $weight = 0.0,
        ?string $barcode = null,
        ?string $description = null,
        ?string $category = null,
        ?string $brand = null,
        bool $isHazardous = false,
        bool $isFragile = false,
        bool $requiresTemperatureControl = false,
        ?float $minTemperature = null,
        ?float $maxTemperature = null,
        ?array $dimensions = null,
        ?int $userId = null
    ): Product {
        if ($this->productRepository->skuExists($sku)) {
            throw new \InvalidArgumentException('Product SKU already exists');
        }

        if ($barcode && $this->productRepository->findByBarcode($barcode)) {
            throw new \InvalidArgumentException('Product barcode already exists');
        }

        $product = Product::create(
            sku: $sku,
            name: $name,
            unit: $unit,
            weight: $weight,
            barcode: $barcode,
            description: $description,
            category: $category,
            brand: $brand
        );

        $correlationId = Str::uuid()->toString();

        $this->productRepository->save($product);

        $this->auditService->logEvent(
            eventType: 'warehouse_product_created',
            context: [
                'entity_type' => 'warehouse_product',
                'entity_id' => $product->getId()->toString(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'barcode' => $barcode,
                'category' => $category,
                'brand' => $brand,
                'unit' => $unit,
                'weight' => $weight,
                'is_hazardous' => $isHazardous,
                'is_fragile' => $isFragile,
                'requires_temperature_control' => $requiresTemperatureControl,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse product created', [
            'product_id' => $product->getId()->toString(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $product;
    }

    /**
     * Get product by ID
     */
    public function getProduct(string $productId): ?Product
    {
        $id = ProductId::fromString($productId);
        return $this->productRepository->findById($id);
    }

    /**
     * Get product by SKU
     */
    public function getProductBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Get product by barcode
     */
    public function getProductByBarcode(string $barcode): ?Product
    {
        return $this->productRepository->findByBarcode($barcode);
    }

    /**
     * Get active products
     */
    public function getActiveProducts(): array
    {
        return $this->productRepository->findActive();
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(string $category): array
    {
        return $this->productRepository->findByCategory($category);
    }

    /**
     * Search products
     */
    public function searchProducts(string $query): array
    {
        return $this->productRepository->search($query);
    }

    /**
     * Delete product
     */
    public function deleteProduct(
        string $productId,
        int $tenantId,
        ?int $userId = null
    ): void {
        $id = ProductId::fromString($productId);
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $correlationId = Str::uuid()->toString();

        $this->productRepository->delete($id);

        $this->auditService->logEvent(
            eventType: 'warehouse_product_deleted',
            context: [
                'entity_type' => 'warehouse_product',
                'entity_id' => $productId,
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'category' => $product->getCategory(),
                'brand' => $product->getBrand(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Product deleted', [
            'product_id' => $productId,
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'correlation_id' => $correlationId,
        ]);
    }
}
