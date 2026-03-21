<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event при завершении заказа в ресторане.
 * Production 2026.
 */
final class OrderCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RestaurantOrder $order,
        public string $correlationId = '',
    ) {}
}
