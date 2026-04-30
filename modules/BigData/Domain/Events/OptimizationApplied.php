<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Modules\BigData\Domain\Enums\OptimizationType;

/**
 * Optimization Applied Event
 *
 * Dispatched when a cost optimization recommendation is auto-applied or manually applied.
 */
final readonly class OptimizationApplied
{
    public function __construct(
        public string $recommendationId,
        public OptimizationType $type,
        public string $targetResource,
        public float $estimatedSavingsUsd,
        public float $actualSavingsUsd,
        public bool $autoApplied,
    ) {}

    public function toNotification(): array
    {
        return [
            'event' => 'optimization_applied',
            'recommendation_id' => $this->recommendationId,
            'type' => $this->type->value,
            'type_display' => $this->type->displayName(),
            'target_resource' => $this->targetResource,
            'estimated_savings_usd' => round($this->estimatedSavingsUsd, 2),
            'actual_savings_usd' => round($this->actualSavingsUsd, 2),
            'auto_applied' => $this->autoApplied,
            'message' => "Optimization applied: {$this->type->displayName()} on {$this->targetResource} (estimated savings: \${$this->estimatedSavingsUsd})",
        ];
    }
}
