<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class RecommendationServed
{
    use Dispatchable;

    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $scenario,
        public int $itemCount,
        public string $source,
        public string $correlationId,
        public ?string $modelVersion = null,
        public float $latencyMs = 0.0,
    ) {}
}
