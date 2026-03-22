<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class LowStockReached
{
    use Dispatchable;

    public function __construct(
        public readonly int $itemId,
        public readonly int $currentStock,
        public readonly string $correlationId
    ) {}
}
