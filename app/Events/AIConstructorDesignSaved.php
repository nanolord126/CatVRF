<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AIConstructorDesignSaved
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $vertical,
        public readonly string $correlationId,
        public readonly array $designData = [],
    ) {}
}
