<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\Product;
use Modules\Warehouse\Domain\Repositories\ProductRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Infrastructure\Models\ProductModel;
use Illuminate\Database\DatabaseManager;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(Product $product): void
    {
        ProductModel::updateOrCreate(
            ['id' => $product->getId()->toString()],
            $product->toArray()
        );
    }

    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::find($id->toString());
        return $model?->toDomain();
    }

    public function findBySku(string $sku): ?Product
    {
        $model = ProductModel::where('sku', $sku)->first();
        return $model?->toDomain();
    }

    public function findByBarcode(string $barcode): ?Product
    {
        $model = ProductModel::where('barcode', $barcode)->first();
        return $model?->toDomain();
    }

    public function findActive(): array
    {
        return ProductModel::where('is_active', true)
            ->get()
            ->map(fn (ProductModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByCategory(string $category): array
    {
        return ProductModel::where('category', $category)
            ->get()
            ->map(fn (ProductModel $model) => $model->toDomain())
            ->toArray();
    }

    public function search(string $query): array
    {
        return ProductModel::where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$query}%")
            ->get()
            ->map(fn (ProductModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(ProductId $id): void
    {
        ProductModel::destroy($id->toString());
    }

    public function exists(ProductId $id): bool
    {
        return ProductModel::where('id', $id->toString())->exists();
    }

    public function skuExists(string $sku): bool
    {
        return ProductModel::where('sku', $sku)->exists();
    }
}
