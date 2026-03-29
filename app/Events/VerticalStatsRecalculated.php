<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class VerticalStatsRecalculated
{
    use Dispatchable;

    public function __construct(
        public readonly string $vertical,
        public readonly string $correlationId,
        public readonly array $stats = [],
    ) {}
}
