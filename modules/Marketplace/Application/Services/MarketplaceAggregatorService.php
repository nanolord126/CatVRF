<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Services;

use Modules\Marketplace\Application\DTOs\AggregationConfigDTO;
use Modules\Marketplace\Domain\Entities\AggregationRule;
use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\Enums\ListingStatus;
use Modules\Marketplace\Domain\Enums\ListingType;
use Modules\Marketplace\Domain\Events\ListingAggregated;
use Modules\Marketplace\Domain\Events\ListingPublished;
use Modules\Marketplace\Domain\Interfaces\AggregationRuleRepositoryInterface;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\Money;
use Modules\Marketplace\Domain\ValueObjects\Rating;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Modules\Marketplace\Infrastructure\Adapters\VerticalAdapterFactoryInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Сервис агрегации товаров из вертикалей в маркетплейс
 * Основной оркестратор процесса агрегации
 */
final class MarketplaceAggregatorService
{
    private const BATCH_SIZE = 100;
    private const SYNC_TIMEOUT_SECONDS = 300;

    public function __construct(
        private readonly ListingRepositoryInterface $listingRepository,
        private readonly AggregationRuleRepositoryInterface $ruleRepository,
        private readonly VerticalAdapterFactoryInterface $adapterFactory,
        private readonly LoggerInterface $logger,
        private readonly Dispatcher $eventDispatcher,
    ) {}

    /**
     * Создать правило агрегации для вертикали
     */
    public function createAggregationRule(AggregationConfigDTO $config): AggregationRule
    {
        $config->validate();

        $rule = AggregationRule::create(
            source: $config->source,
            sourceEntityType: $config->sourceEntityType,
            filters: $config->filters,
            transformations: $config->transformations,
            priority: $config->priority,
            categoryMapping: $config->categoryMapping,
            attributeMapping: $config->attributeMapping,
            schedule: $config->schedule,
            batchSize: $config->batchSize,
            syncIntervalMinutes: $config->syncIntervalMinutes,
            realTimeSync: $config->realTimeSync,
        );

        $this->ruleRepository->save($rule);

        $this->logger->info('Aggregation rule created', [
            'uuid' => $rule->uuid->toString(),
            'source' => $rule->source->value,
            'entity_type' => $rule->sourceEntityType,
        ]);

        return $rule;
    }

    /**
     * Синхронизировать все вертикали по правилам агрегации
     */
    public function syncAllVerticals(): array
    {
        $this->logger->info('Starting full verticals synchronization');

        $rules = $this->ruleRepository->findActive();
        $results = [];

        foreach ($rules as $rule) {
            try {
                $result = $this->syncVerticalByRule($rule);
                $results[$rule->uuid->toString()] = $result;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to sync vertical', [
                    'rule_uuid' => $rule->uuid->toString(),
                    'source' => $rule->source->value,
                    'error' => $e->getMessage(),
                ]);
                $results[$rule->uuid->toString()] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logger->info('Verticals synchronization completed', [
            'total_rules' => count($rules),
            'successful' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
        ]);

        return $results;
    }

    /**
     * Синхронизировать конкретную вертикаль по правилу
     */
    public function syncVerticalByRule(AggregationRule $rule): array
    {
        $this->logger->info('Syncing vertical by rule', [
            'rule_uuid' => $rule->uuid->toString(),
            'source' => $rule->source->value,
        ]);

        if (!$rule->isActive) {
            return ['success' => false, 'error' => 'Rule is not active'];
        }

        // Получаем адаптер для вертикали
        $adapter = $this->adapterFactory->getAdapter($rule->source);
        if ($adapter === null) {
            return ['success' => false, 'error' => 'Adapter not found for vertical'];
        }

        // Получаем источники данных из вертикали
        $sources = $adapter->fetchSources($rule->filters, $rule->batchSize);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($sources as $source) {
            try {
                // Применяем трансформации
                $transformed = $this->applyTransformations($source, $rule->transformations);

                // Конвертируем в ProductListing
                $listing = $this->convertToListing($transformed, $rule);

                // Проверяем, существует ли уже позиция
                $existing = $this->listingRepository->findBySourceId(
                    $listing->sourceId,
                    $listing->sourceType
                );

                if ($existing !== null) {
                    // Обновляем существующую
                    $updatedListing = $this->updateListing($existing, $listing);
                    $this->listingRepository->save($updatedListing);
                    $updated++;
                } else {
                    // Создаем новую
                    if ($rule->filters['auto_publish'] ?? false) {
                        $listing = $listing->withStatus(ListingStatus::PUBLISHED);
                    }
                    $this->listingRepository->save($listing);
                    $created++;

                    // Диспатчим событие агрегации
                    $this->eventDispatcher->dispatch(
                        new ListingAggregated(
                            $listing->uuid,
                            $listing->source,
                            $listing->sourceId,
                            $listing->sourceType,
                            $listing->tenantId,
                            new \DateTimeImmutable(),
                        )
                    );
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process source item', [
                    'source_id' => $source['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        // Обновляем время последней синхронизации
        $rule = $rule->withLastSync();
        $this->ruleRepository->save($rule);

        $result = [
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($sources),
        ];

        $this->logger->info('Vertical sync completed', [
            'rule_uuid' => $rule->uuid->toString(),
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Синхронизировать только pendings правила
     */
    public function syncPending(): array
    {
        $rules = $this->ruleRepository->findPendingSync();
        $results = [];

        foreach ($rules as $rule) {
            $results[$rule->uuid->toString()] = $this->syncVerticalByRule($rule);
        }

        return $results;
    }

    /**
     * Реактивная синхронизация при изменении в вертикали
     */
    public function syncSingleItem(VerticalSource $source, int $sourceId, string $sourceType): ?ProductListing
    {
        $this->logger->info('Syncing single item', [
            'source' => $source->value,
            'source_id' => $sourceId,
            'source_type' => $sourceType,
        ]);

        // Находим правило для этой вертикали с real-time sync
        $rules = $this->ruleRepository->findRealTimeRules();
        $rule = null;

        foreach ($rules as $r) {
            if ($r->source === $source && $r->sourceEntityType === $sourceType) {
                $rule = $r;
                break;
            }
        }

        if ($rule === null) {
            $this->logger->warning('No real-time rule found for source', [
                'source' => $source->value,
                'source_type' => $sourceType,
            ]);
            return null;
        }

        // Получаем адаптер
        $adapter = $this->adapterFactory->getAdapter($source);
        if ($adapter === null) {
            return null;
        }

        // Получаем конкретный элемент
        $source = $adapter->fetchSingle($sourceId, $rule->filters);
        if ($source === null) {
            $this->logger->warning('Source item not found', ['source_id' => $sourceId]);
            return null;
        }

        // Применяем трансформации и конвертируем
        $transformed = $this->applyTransformations($source, $rule->transformations);
        $listing = $this->convertToListing($transformed, $rule);

        // Проверяем существование
        $existing = $this->listingRepository->findBySourceId($sourceId, $sourceType);

        if ($existing !== null) {
            $listing = $this->updateListing($existing, $listing);
        }

        $this->listingRepository->save($listing);

        return $listing;
    }

    /**
     * Применить трансформации к данным источника
     */
    private function applyTransformations(array $source, array $transformations): array
    {
        $result = $source;

        foreach ($transformations as $field => $transformation) {
            if (isset($result[$field])) {
                $result[$field] = $this->applyTransformation($result[$field], $transformation);
            }
        }

        return $result;
    }

    /**
     * Применить одиночную трансформацию
     */
    private function applyTransformation(mixed $value, array $transformation): mixed
    {
        $type = $transformation['type'] ?? 'pass';

        return match ($type) {
            'pass' => $value,
            'uppercase' => is_string($value) ? mb_strtoupper($value) : $value,
            'lowercase' => is_string($value) ? mb_strtolower($value) : $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'multiply' => is_numeric($value) ? $value * ($transformation['factor'] ?? 1) : $value,
            'map' => isset($transformation['mapping'][$value]) ? $transformation['mapping'][$value] : $value,
            'default' => $value ?? $transformation['default'] ?? null,
            'format' => is_string($value) ? sprintf($transformation['format'] ?? '%s', $value) : $value,
            default => $value,
        };
    }

    /**
     * Конвертировать данные источника в ProductListing
     */
    private function convertToListing(array $data, AggregationRule $rule): ProductListing
    {
        // Маппинг категорий
        $sourceCategory = $data['category'] ?? null;
        $category = $sourceCategory !== null ? $rule->mapCategory($sourceCategory) : $sourceCategory;
        $categories = $category !== null ? [$category] : [];

        // Маппинг атрибутов
        $attributes = [];
        foreach ($data['attributes'] ?? [] as $key => $value) {
            $mappedKey = $rule->mapAttribute($key) ?? $key;
            $attributes[$mappedKey] = $value;
        }

        return ProductListing::create(
            source: $rule->source,
            type: ListingType::tryFrom($data['type'] ?? 'product') ?? ListingType::PRODUCT,
            title: $data['title'] ?? 'Untitled',
            description: $data['description'] ?? '',
            price: Money::create((float) ($data['price'] ?? 0), $data['currency'] ?? 'RUB'),
            tenantId: (int) ($data['tenant_id'] ?? 1),
            businessGroupId: (int) ($data['business_group_id'] ?? 1),
            sourceId: (int) $data['id'],
            sourceType: $rule->sourceEntityType,
            categories: $categories,
            tags: $data['tags'] ?? [],
            images: $data['images'] ?? [],
            attributes: $attributes,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Обновить существующую позицию
     */
    private function updateListing(ProductListing $existing, ProductListing $new): ProductListing
    {
        // Обновляем только изменившиеся поля
        $data = $existing->toArray();
        $newData = $new->toArray();

        $updates = [
            'title' => $newData['title'],
            'description' => $newData['description'],
            'price' => $newData['price'],
            'categories' => $newData['categories'],
            'tags' => $newData['tags'],
            'images' => $newData['images'],
            'attributes' => $newData['attributes'],
            'stock_quantity' => $newData['stock_quantity'],
            'in_stock' => $newData['in_stock'],
        ];

        return new ProductListing(
            ...array_merge($data, $updates),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Опубликовать позицию на маркетплейсе
     */
    public function publishListing(\Ramsey\Uuid\UuidInterface $listingUuid): ProductListing
    {
        $listing = $this->listingRepository->findByUuid($listingUuid);
        if ($listing === null) {
            throw new \InvalidArgumentException('Listing not found');
        }

        if (!$listing->status->canBePublished()) {
            throw new \InvalidArgumentException('Listing cannot be published in current status');
        }

        $publishedListing = $listing->withStatus(ListingStatus::PUBLISHED);
        $this->listingRepository->save($publishedListing);

        $this->eventDispatcher->dispatch(
            new ListingPublished($listingUuid, $listing->tenantId, new \DateTimeImmutable())
        );

        return $publishedListing;
    }

    /**
     * Получить статистику агрегации
     */
    public function getAggregationStats(): array
    {
        $rules = $this->ruleRepository->findActive();
        $stats = [];

        foreach ($rules as $rule) {
            $verticalStats = $this->listingRepository->getStatsByVertical($rule->source);
            $stats[$rule->source->value] = [
                'rule_uuid' => $rule->uuid->toString(),
                'entity_type' => $rule->sourceEntityType,
                'is_active' => $rule->isActive,
                'real_time_sync' => $rule->realTimeSync,
                'last_sync_at' => $rule->lastSyncAt?->format('Y-m-d H:i:s'),
                'sync_interval_minutes' => $rule->syncIntervalMinutes,
                'listings' => $verticalStats,
            ];
        }

        return $stats;
    }
}
