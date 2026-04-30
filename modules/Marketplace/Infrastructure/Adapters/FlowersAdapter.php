<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Flowers\Domain\Entities\Bouquet;
use Modules\Flowers\Domain\Repositories\BouquetRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

final class FlowersAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly BouquetRepositoryInterface $bouquetRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Flowers bouquets', ['filters' => $filters, 'limit' => $limit]);

        $bouquets = $this->bouquetRepository->findActive($limit);

        $sources = [];
        foreach ($bouquets as $bouquet) {
            try {
                $sources[] = $this->convertBouquetToArray($bouquet);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert bouquet', [
                    'bouquet_id' => $bouquet->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $bouquet = $this->bouquetRepository->findById($sourceId);
        if ($bouquet === null) {
            return null;
        }

        return $this->convertBouquetToArray($bouquet);
    }

    public function countSources(array $filters = []): int
    {
        return $this->bouquetRepository->countActive();
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
        return ['product', 'bouquet'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'in_stock' => true,
            'auto_publish' => true,
        ];
    }

    private function convertBouquetToArray(Bouquet $bouquet): array
    {
        return [
            'id' => $bouquet->id,
            'title' => $bouquet->name,
            'description' => $bouquet->description ?? '',
            'price' => $bouquet->price,
            'currency' => 'RUB',
            'category' => $this->mapCategory($bouquet->category),
            'type' => 'product',
            'tags' => array_merge(
                $bouquet->flowers ?? [],
                $bouquet->isPremium ? ['premium'] : [],
                $bouquet->hasDelivery ? ['delivery'] : [],
            ),
            'images' => $bouquet->images ?? [],
            'attributes' => [
                'flower_shop_id' => $bouquet->flowerShopId,
                'flower_count' => $bouquet->flowerCount,
                'size' => $bouquet->size,
                'occasion' => $bouquet->occasion,
            ],
            'metadata' => [
                'vertical' => VerticalSource::FLOWERS->value,
                'source_type' => 'bouquet',
                'supports_delivery' => true,
            ],
            'tenant_id' => $bouquet->tenantId,
            'business_group_id' => $bouquet->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'roses' => 'roses',
            'tulips' => 'tulips',
            'mixed' => 'mixed_bouquets',
            'wedding' => 'wedding',
            'funeral' => 'funeral',
            'birthday' => 'birthday',
        ];

        return $mapping[$category ?? ''] ?? 'flowers';
    }
}
