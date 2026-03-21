<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Events;

use App\Domains\FreshProduce\Models\ProduceOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BoxDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProduceOrder $order,
        public readonly string $correlationId,
    ) {}
}
