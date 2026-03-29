<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ProductInventoryChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $productId,
        public readonly string $vertical,
        public readonly int $oldQuantity,
        public readonly int $newQuantity,
        public readonly string $correlationId,
    ) {}
}
