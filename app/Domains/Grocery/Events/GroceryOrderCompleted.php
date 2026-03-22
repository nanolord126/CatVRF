<?php declare(strict_types=1);

namespace App\Domains\Grocery\Events;

use App\Domains\Grocery\Models\GroceryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class GroceryOrderCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly GroceryOrder $order,
        public readonly string $correlationId
    ) {}
}
