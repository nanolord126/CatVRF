<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Events;

use App\Domains\VeganProducts\Models\VeganProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VeganStockAlertEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly VeganProduct $product,
        public readonly int $currentStock,
        public readonly string $correlationId = '',
    ) {}
}
