<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Inventory Released Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class InventoryReleased
{
    use Dispatchable;

    public function __construct(
        public readonly int $inventoryId,
        public readonly int $impressions,
        public readonly int $publisherId,
        public readonly string $correlationId,
    ) {}
}
