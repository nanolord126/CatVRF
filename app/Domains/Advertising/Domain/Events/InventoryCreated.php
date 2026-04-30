<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Inventory Created Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class InventoryCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $inventoryId,
        public readonly int $publisherId,
        public readonly string $correlationId,
    ) {}
}
