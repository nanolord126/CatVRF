<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Fashion\Domain\Entities\Product;
use Modules\Fashion\Domain\Repositories\ProductRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

final class FashionAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Fashion products', ['filters' => $filters, 'limit' => $limit]);

        $products = $this->productRepository->findActive($limit);

        $sources = [];
        foreach ($products as $product) {
            try {
                $sources[] = $this->convertProductToArray($product);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $product = $this->productRepository->findById($sourceId);
        if ($product === null) {
            return null;
        }

        return $this->convertProductToArray($product);
    }

    public function countSources(array $filters = []): int
    {
        return $this->productRepository->countActive();
    }

    public function validateSource(array $source): bool
    {
        $required = ['id', 'title', 'price', 'category'];
        foreach ($required as $field) {
            if (!isset($source[$field])) {
                return false;
            }
        }

        return !empty($source['title']) && $source['price'] > 0;
    }

    public function getSupportedEntityTypes(): array
    {
        return ['product'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'in_stock' => true,
            'auto_publish' => true,
        ];
    }

    private function convertProductToArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->name,
            'description' => $product->description ?? '',
            'price' => $product->price,
            'currency' => 'RUB',
            'category' => $this->mapCategory($product->category),
            'type' => 'product',
            'tags' => array_merge(
                $product->tags ?? [],
                $product->isNew ? ['new'] : [],
                $product->isSale ? ['sale'] : [],
            ),
            'images' => $product->images ?? [],
            'attributes' => [
                'brand' => $product->brand,
                'size' => $product->size,
                'color' => $product->color,
                'material' => $product->material,
                'gender' => $product->gender,
            ],
            'metadata' => [
                'vertical' => VerticalSource::FASHION->value,
                'source_type' => 'product',
                'supports_delivery' => true,
            ],
            'tenant_id' => $product->tenantId,
            'business_group_id' => $product->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'clothing' => 'clothing',
            'shoes' => 'footwear',
            'accessories' => 'accessories',
            'bags' => 'bags',
            'jewelry' => 'jewelry',
            'watches' => 'accessories',
        ];

        return $mapping[$category ?? ''] ?? 'fashion';
    }
}
