<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\DTOs;

use Modules\Marketplace\Domain\ValueObjects\VerticalSource;

/**
 * DTO конфигурации агрегации из вертикали
 */
final readonly class AggregationConfigDTO
{
    private function __construct(
        public VerticalSource $source,
        public string $sourceEntityType,
        public array $filters,
        public array $transformations,
        public int $priority,
        public array $categoryMapping,
        public array $attributeMapping,
        public ?string $schedule,
        public int $batchSize,
        public int $syncIntervalMinutes,
        public bool $realTimeSync,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            source: VerticalSource::from($data['source']),
            sourceEntityType: $data['source_entity_type'],
            filters: $data['filters'] ?? [],
            transformations: $data['transformations'] ?? [],
            priority: (int) ($data['priority'] ?? 0),
            categoryMapping: $data['category_mapping'] ?? [],
            attributeMapping: $data['attribute_mapping'] ?? [],
            schedule: $data['schedule'] ?? null,
            batchSize: (int) ($data['batch_size'] ?? 100),
            syncIntervalMinutes: (int) ($data['sync_interval_minutes'] ?? 60),
            realTimeSync: (bool) ($data['real_time_sync'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
        );
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
        bool $isActive = true,
    ): self {
        return new self(
            source: $source,
            sourceEntityType: $sourceEntityType,
            filters: $filters,
            transformations: $transformations,
            priority: $priority,
            categoryMapping: $categoryMapping,
            attributeMapping: $attributeMapping,
            schedule: $schedule,
            batchSize: $batchSize,
            syncIntervalMinutes: $syncIntervalMinutes,
            realTimeSync: $realTimeSync,
            isActive: $isActive,
        );
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'source_entity_type' => $this->sourceEntityType,
            'filters' => $this->filters,
            'transformations' => $this->transformations,
            'priority' => $this->priority,
            'category_mapping' => $this->categoryMapping,
            'attribute_mapping' => $this->attributeMapping,
            'schedule' => $this->schedule,
            'batch_size' => $this->batchSize,
            'sync_interval_minutes' => $this->syncIntervalMinutes,
            'real_time_sync' => $this->realTimeSync,
            'is_active' => $this->isActive,
        ];
    }

    public function withFilters(array $filters): self
    {
        return new self(
            ...$this->toArray(),
            filters: $filters,
        );
    }

    public function withTransformations(array $transformations): self
    {
        return new self(
            ...$this->toArray(),
            transformations: $transformations,
        );
    }

    public function activate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: true,
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: false,
        );
    }

    public function validate(): void
    {
        if (empty($this->sourceEntityType)) {
            throw new \InvalidArgumentException('Source entity type cannot be empty');
        }

        if ($this->batchSize < 1 || $this->batchSize > 10000) {
            throw new \InvalidArgumentException('Batch size must be between 1 and 10000');
        }

        if ($this->syncIntervalMinutes < 1) {
            throw new \InvalidArgumentException('Sync interval must be at least 1 minute');
        }

        if ($this->priority < 0) {
            throw new \InvalidArgumentException('Priority cannot be negative');
        }
    }
}
