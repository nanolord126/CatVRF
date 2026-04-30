<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Entities;

use Modules\Marketplace\Domain\Enums\ListingType;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Правило агрегации товаров из вертикали в маркетплейс
 * Определяет, какие и как товары попадают в витрину
 */
final readonly class AggregationRule
{
    private function __construct(
        public UuidInterface $uuid,
        public VerticalSource $source,
        public string $sourceEntityType,
        public bool $isActive,
        public array $filters,
        public array $transformations,
        public int $priority,
        public array $categoryMapping,
        public array $attributeMapping,
        public ?string $schedule,
        public int $batchSize,
        public int $syncIntervalMinutes,
        public bool $realTimeSync,
        public array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $lastSyncAt,
    ) {
        $this->validate();
    }

    public static function create(
        VerticalSource $source,
        string $sourceEntityType,
        array $filters = [],
        array $transformations = [],
        int $priority = 0,
        array $categoryMapping = [],
        array $attributeMapping = [],
        ?string $schedule = null,
        int $batchSize = 100,
        int $syncIntervalMinutes = 60,
        bool $realTimeSync = false,
        array $metadata = [],
    ): self {
        return new self(
            uuid: \Ramsey\Uuid\Uuid::uuid4(),
            source: $source,
            sourceEntityType: $sourceEntityType,
            isActive: true,
            filters: $filters,
            transformations: $transformations,
            priority: $priority,
            categoryMapping: $categoryMapping,
            attributeMapping: $attributeMapping,
            schedule: $schedule,
            batchSize: $batchSize,
            syncIntervalMinutes: $syncIntervalMinutes,
            realTimeSync: $realTimeSync,
            metadata: $metadata,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastSyncAt: null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: \Ramsey\Uuid\Uuid::fromString($data['uuid']),
            source: VerticalSource::from($data['source']),
            sourceEntityType: $data['source_entity_type'],
            isActive: (bool) $data['is_active'],
            filters: (array) $data['filters'],
            transformations: (array) $data['transformations'],
            priority: (int) $data['priority'],
            categoryMapping: (array) $data['category_mapping'],
            attributeMapping: (array) $data['attribute_mapping'],
            schedule: $data['schedule'] ?? null,
            batchSize: (int) $data['batch_size'],
            syncIntervalMinutes: (int) $data['sync_interval_minutes'],
            realTimeSync: (bool) $data['real_time_sync'],
            metadata: (array) $data['metadata'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at']),
            lastSyncAt: isset($data['last_sync_at']) ? new \DateTimeImmutable($data['last_sync_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid->toString(),
            'source' => $this->source->value,
            'source_entity_type' => $this->sourceEntityType,
            'is_active' => $this->isActive,
            'filters' => $this->filters,
            'transformations' => $this->transformations,
            'priority' => $this->priority,
            'category_mapping' => $this->categoryMapping,
            'attribute_mapping' => $this->attributeMapping,
            'schedule' => $this->schedule,
            'batch_size' => $this->batchSize,
            'sync_interval_minutes' => $this->syncIntervalMinutes,
            'real_time_sync' => $this->realTimeSync,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'last_sync_at' => $this->lastSyncAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function activate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: true,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: false,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withLastSync(?\DateTimeImmutable $lastSyncAt = null): self
    {
        return new self(
            ...$this->toArray(),
            lastSyncAt: $lastSyncAt ?? new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withFilters(array $filters): self
    {
        return new self(
            ...$this->toArray(),
            filters: $filters,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withTransformations(array $transformations): self
    {
        return new self(
            ...$this->toArray(),
            transformations: $transformations,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withCategoryMapping(array $mapping): self
    {
        return new self(
            ...$this->toArray(),
            categoryMapping: $mapping,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withAttributeMapping(array $mapping): self
    {
        return new self(
            ...$this->toArray(),
            attributeMapping: $mapping,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withSchedule(?string $schedule): self
    {
        return new self(
            ...$this->toArray(),
            schedule: $schedule,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withPriority(int $priority): self
    {
        return new self(
            ...$this->toArray(),
            priority: $priority,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function shouldSyncNow(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->realTimeSync) {
            return true;
        }

        if ($this->lastSyncAt === null) {
            return true;
        }

        $elapsed = (new \DateTimeImmutable())->getTimestamp() - $this->lastSyncAt->getTimestamp();
        return $elapsed >= $this->syncIntervalMinutes * 60;
    }

    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    public function getTransformation(string $key): ?array
    {
        return $this->transformations[$key] ?? null;
    }

    public function mapCategory(string $sourceCategory): ?string
    {
        return $this->categoryMapping[$sourceCategory] ?? null;
    }

    public function mapAttribute(string $sourceAttribute): ?string
    {
        return $this->attributeMapping[$sourceAttribute] ?? null;
    }

    public function getSyncIntervalInSeconds(): int
    {
        return $this->syncIntervalMinutes * 60;
    }

    private function validate(): void
    {
        if (empty($this->sourceEntityType)) {
            throw new \InvalidArgumentException('Source entity type cannot be empty');
        }

        if ($this->priority < 0) {
            throw new \InvalidArgumentException('Priority cannot be negative');
        }

        if ($this->batchSize < 1 || $this->batchSize > 10000) {
            throw new \InvalidArgumentException('Batch size must be between 1 and 10000');
        }

        if ($this->syncIntervalMinutes < 1) {
            throw new \InvalidArgumentException('Sync interval must be at least 1 minute');
        }
    }
}
