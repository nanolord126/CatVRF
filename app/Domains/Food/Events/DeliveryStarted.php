<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use App\Domains\Food\Models\DeliveryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DeliveryStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DeliveryOrder $delivery,
        public readonly string $correlationId
    ) {}
}
