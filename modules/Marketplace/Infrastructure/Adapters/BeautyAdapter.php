<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\BeautyMasters\Domain\Entities\Service;
use Modules\BeautyMasters\Domain\Repositories\ServiceRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

/**
 * Адаптер для интеграции с вертикалью Beauty Masters
 */
final class BeautyAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Beauty services', ['filters' => $filters, 'limit' => $limit]);

        $services = $this->serviceRepository->findActive($limit);

        $sources = [];
        foreach ($services as $service) {
            try {
                $sources[] = $this->convertServiceToArray($service);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert service', [
                    'service_id' => $service->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Fetched Beauty services', ['count' => count($sources)]);

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $this->logger->info('Fetching single Beauty service', ['service_id' => $sourceId]);

        $service = $this->serviceRepository->findById($sourceId);
        if ($service === null) {
            return null;
        }

        return $this->convertServiceToArray($service);
    }

    public function countSources(array $filters = []): int
    {
        return $this->serviceRepository->countActive();
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
        return ['service'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'is_published' => true,
            'auto_publish' => false,
        ];
    }

    private function convertServiceToArray(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->name,
            'description' => $service->description ?? '',
            'price' => $service->price,
            'currency' => 'RUB',
            'category' => $this->mapCategory($service->category),
            'type' => 'service',
            'tags' => $service->tags ?? [],
            'images' => $service->images ?? [],
            'attributes' => [
                'duration' => $service->durationMinutes,
                'salon_id' => $service->salonId,
                'staff_id' => $service->staffId,
            ],
            'metadata' => [
                'vertical' => VerticalSource::BEAUTY->value,
                'source_type' => 'service',
                'created_at' => $service->createdAt->format('Y-m-d H:i:s'),
            ],
            'tenant_id' => $service->tenantId,
            'business_group_id' => $service->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'haircut' => 'hairdressing',
            'coloring' => 'hairdressing',
            'styling' => 'hairdressing',
            'manicure' => 'nails',
            'pedicure' => 'nails',
            'makeup' => 'makeup',
            'facial' => 'skincare',
            'massage' => 'spa',
        ];

        return $mapping[$category ?? ''] ?? 'beauty';
    }
}
