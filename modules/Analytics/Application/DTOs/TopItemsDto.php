<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Top Items DTO
 *
 * Represents top-performing items (products, sellers, categories) by a metric.
 */
final readonly class TopItemsDto
{
    /**
     * @param TopItemDto[] $items
     */
    public function __construct(
        public readonly string $itemType, // product, seller, category
        public readonly string $metric,
        public readonly Period $period,
        public readonly array $items,
        public readonly ?int $tenantId = null,
    ) {
        if (empty($items)) {
            throw new \InvalidArgumentException('Top items must have at least one item');
        }
    }

    /**
     * @param TopItemDto[] $items
     */
    public static function create(
        string $itemType,
        string $metric,
        Period $period,
        array $items,
        ?int $tenantId = null,
    ): self {
        return new self(
            $itemType,
            $metric,
            $period,
            $items,
            $tenantId,
        );
    }

    public function toArray(): array
    {
        return [
            'item_type' => $this->itemType,
            'metric' => $this->metric,
            'period' => (string) $this->period,
            'period_from' => $this->period->from()->toIso8601String(),
            'period_to' => $this->period->to()->toIso8601String(),
            'items' => array_map(fn ($item) => $item->toArray(), $this->items),
            'tenant_id' => $this->tenantId,
        ];
    }
}

