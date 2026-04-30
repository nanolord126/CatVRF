<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class ImpressionTracked
{
    use Dispatchable;

    public function __construct(
        public int $tenantId,
        public int $userId,
        public int $itemId,
        public int $position,
        public string $scenario,
        public string $source,
        public string $correlationId,
    ) {}
}
