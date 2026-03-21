<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use App\Domains\Flowers\Models\FlowerDelivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FlowerDeliveryCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FlowerDelivery $delivery,
        public readonly string $correlationId,
    ) {}
}
