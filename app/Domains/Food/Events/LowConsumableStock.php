<?php

declare(strict_types=1);


namespace App\Domains\Food\Events;

use App\Domains\Food\Models\FoodConsumable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event при низком остатке ингредиента.
 * Production 2026.
 */
final class LowConsumableStock
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FoodConsumable $consumable,
        public string $correlationId = '',
    ) {}
}
