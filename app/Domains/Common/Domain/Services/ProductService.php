<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Services;

use App\Domains\Common\Domain\Entities\Product;
use App\Domains\Common\Domain\Repositories\ProductRepositoryInterface;
use App\Domains\Common\Domain\ValueObjects\Price;
use App\Domains\Common\Domain\ValueObjects\Quantity;
use App\Domains\Common\Domain\ValueObjects\SKU;
use Illuminate\Support\Collection;

final readonly class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function createProduct(
        SKU $sku,
        string $name,
        ?string $description,
        Price $price,
        Quantity $stock,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?array $metadata = null,
    ): Product {
        if ($this->productRepository->existsBySku($sku)) {
            throw new \InvalidArgumentException('Product with this SKU already exists');
        }

        $product = Product::create(
            sku: $sku,
            name: $name,
            description: $description,
            price: $price,
            stock: $stock,
            categoryId: $categoryId,
            brandId: $brandId,
            metadata: $metadata,
        );

        return $this->productRepository->save($product);
    }

    public function updatePrice(int $productId, Price $price): Product
    {
        $product = $this->getProductById($productId);

        $updatedProduct = $product->withPrice($price);

        return $this->productRepository->save($updatedProduct);
    }

    public function updateStock(int $productId, Quantity $stock): Product
    {
        $product = $this->getProductById($productId);

        $updatedProduct = $product->withStock($stock);

        return $this->productRepository->save($updatedProduct);
    }

    public function adjustStock(int $productId, Quantity $adjustment): Product
    {
        $product = $this->getProductById($productId);

        $newStock = $product->stock->add($adjustment);

        return $this->updateStock($productId, $newStock);
    }

    public function activateProduct(int $productId): Product
    {
        $product = $this->getProductById($productId);

        $activatedProduct = $product->activate();

        return $this->productRepository->save($activatedProduct);
    }

    public function deactivateProduct(int $productId): Product
    {
        $product = $this->getProductById($productId);

        $deactivatedProduct = $product->deactivate();

        return $this->productRepository->save($deactivatedProduct);
    }

    public function featureProduct(int $productId): Product
    {
        $product = $this->getProductById($productId);

        $featuredProduct = $product->feature();

        return $this->productRepository->save($featuredProduct);
    }

    public function unfeatureProduct(int $productId): Product
    {
        $product = $this->getProductById($productId);

        $unfeaturedProduct = $product->unfeature();

        return $this->productRepository->save($unfeaturedProduct);
    }

    public function getProductById(int $id): Product
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found');
        }

        return $product;
    }

    public function getProductBySku(SKU $sku): Product
    {
        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found');
        }

        return $product;
    }

    public function searchProducts(string $query, array $filters = [], int $limit = 50): Collection
    {
        return $this->productRepository->search($query, $filters, $limit);
    }

    public function getProductsByCategory(int $categoryId, array $filters = [], int $limit = 50): Collection
    {
        return $this->productRepository->findByCategory($categoryId, $filters, $limit);
    }

    public function getProductsByBrand(int $brandId, array $filters = [], int $limit = 50): Collection
    {
        return $this->productRepository->findByBrand($brandId, $filters, $limit);
    }

    public function getFeaturedProducts(array $filters = [], int $limit = 20): Collection
    {
        return $this->productRepository->findFeatured($filters, $limit);
    }

    public function getActiveProducts(array $filters = [], int $limit = 50): Collection
    {
        return $this->productRepository->findActive($filters, $limit);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function isProductInStock(int $productId): bool
    {
        $product = $this->getProductById($productId);

        return $product->isInStock();
    }
}
