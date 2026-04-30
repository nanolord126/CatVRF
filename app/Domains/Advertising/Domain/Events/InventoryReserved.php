<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Inventory Reserved Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class InventoryReserved
{
    use Dispatchable;

    public function __construct(
        public readonly int $inventoryId,
        public readonly int $impressions,
        public readonly int $publisherId,
        public readonly string $correlationId,
    ) {}
}
