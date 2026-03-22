<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use App\Domains\Flowers\Models\FlowerOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FlowerOrderCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly FlowerOrder $order,
        public readonly string $correlationId
    ) {}
}
