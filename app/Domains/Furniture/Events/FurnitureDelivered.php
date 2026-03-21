<?php declare(strict_types=1);

namespace App\Domains\Furniture\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FurnitureDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $furnitureOrderId,
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}
}
