<?php
declare(strict_types=1);

namespace App\Events;

use App\Domains\Food\Models\FoodOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FoodOrderPlacedEvent
{
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        public readonly FoodOrder $order
    ) {}
}

