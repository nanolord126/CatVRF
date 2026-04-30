<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\WarehouseDocument;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Document Created Event
 */
final class DocumentCreated
{
    use Dispatchable;

    public function __construct(
        public readonly WarehouseDocument $document
    ) {}
}
