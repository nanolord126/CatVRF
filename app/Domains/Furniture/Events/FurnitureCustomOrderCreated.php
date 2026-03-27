<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Events;

use App\Domains\Furniture\Models\FurnitureCustomOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * FurnitureCustomOrderCreated (Layer 8/9)
 * Event triggered when a custom interior project or furniture order is initiated.
 */
final class FurnitureCustomOrderCreated
{
    use Dispatchable, SerializesModels;

    /**
     * @param FurnitureCustomOrder $order
     * @param string|null $correlationId
     */
    public function __construct(
        public readonly FurnitureCustomOrder $order,
        public readonly ?string $correlationId = null
    ) {}
}
